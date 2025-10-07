<?php

namespace AsetKita\LaravelApprovalWorkflow\Services;

use Exception;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;
use AsetKita\LaravelApprovalWorkflow\Repositories\ApprovalHistoryRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\ApprovalRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\FlowRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\UserRepository;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Illuminate\Http\UploadedFile;

class ApprovalService
{
    // Exception constants
    public const EXC_USER_NOT_FOUND = 'exc_user_not_found';
    public const EXC_FLOW_NOT_FOUND = 'exc_flow_not_found';
    public const EXC_PERMISSION_DENIED = 'exc_permission_denied';
    public const EXC_APPROVAL_NOT_RUNNING = 'exc_approval_not_running';
    public const EXC_APPROVAL_NOT_REJECTED = 'exc_approval_not_rejected';

    private int $companyId;

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? config('approval-workflow.default_company_id', 1);
    }

    /**
     * Start approval workflow
     */
    public function start(string $flowType, int $userId, ?array $parameters = []): array
    {
        // Check if user exists
        $user = UserRepository::getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get flow by type
        $flow = FlowRepository::getByType($this->companyId, $flowType);
        if ($flow === null) {
            throw new Exception(self::EXC_FLOW_NOT_FOUND);
        }

        // Create approval
        $approvalId = ApprovalRepository::insert($this->companyId, $flow['id'], $userId, $parameters);

        // Create initial history
        ApprovalHistoryRepository::insert(
            $approvalId,
            null,
            $userId,
            'Permintaan persetujuan dibuat',
            ApprovalHistory::FLAG_CREATED,
            null
        );

        // Process next step
        $previousApprovers = ApprovalRepository::getCurrentApprovers($approvalId);
        
        if ($flow['is_active']) {
            $this->checkNextStep($approvalId);
        } else {
            // Mark as approved if flow is inactive
            ApprovalRepository::update($approvalId, 'APPROVED', null, null);
            
            ApprovalHistoryRepository::insert(
                $approvalId,
                null,
                $userId,
                "Persetujuan dianggap selesai karena flow persetujuan NON-AKTIF.",
                ApprovalHistory::FLAG_DONE,
                null
            );
        }

        $nextApprovers = ApprovalRepository::getCurrentApprovers($approvalId);

        // Return current status
        $result = ApprovalRepository::getCurrentStatus($approvalId);
        $result['stakeholders'] = [
            'owner' => ApprovalRepository::getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => $nextApprovers,
        ];

        return $result;
    }

    /**
     * Approve current step
     */
    public function approve(int $approvalId, int $userId, ?string $notes = null, $file = null): array
    {
        // Check if user exists
        $user = UserRepository::getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = ApprovalRepository::getCurrentStatus($approvalId);

        // Check if approval is running
        if ($data['status'] !== 'ON_PROGRESS') {
            throw new Exception(self::EXC_APPROVAL_NOT_RUNNING);
        }

        // Check user permission
        if (!ApprovalRepository::isUserHasPermission($approvalId, $userId)) {
            throw new Exception(self::EXC_PERMISSION_DENIED);
        }

        // Create approval history
        if ($file instanceof UploadedFile) {
            ApprovalHistoryRepository::insertWithFile(
                $approvalId,
                $data['flow_step_id'],
                $userId,
                "Persetujuan pada tahap {$data['flow_step_name']} disetujui oleh {$user['name']}.",
                ApprovalHistory::FLAG_APPROVED,
                $notes,
                $file
            );
        } else {
            ApprovalHistoryRepository::insert(
                $approvalId,
                $data['flow_step_id'],
                $userId,
                "Persetujuan pada tahap {$data['flow_step_name']} disetujui oleh {$user['name']}.",
                ApprovalHistory::FLAG_APPROVED,
                $notes,
                $file
            );
        }

        // Process next step
        $previousApprovers = ApprovalRepository::getCurrentApprovers($approvalId);
        $this->checkNextStep($approvalId);
        $nextApprovers = ApprovalRepository::getCurrentApprovers($approvalId);

        // Return updated status
        $result = ApprovalRepository::getCurrentStatus($approvalId);
        $result['stakeholders'] = [
            'owner' => ApprovalRepository::getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => $nextApprovers,
        ];

        if (empty($nextApprovers)) {
            $result['stakeholders']['steps'] = $this->getAllStepInfo($approvalId);
        }

        return $result;
    }

    /**
     * Reject current step
     */
    public function reject(int $approvalId, int $userId, ?string $notes = null, $file = null): array
    {
        // Check if user exists
        $user = UserRepository::getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = ApprovalRepository::getCurrentStatus($approvalId);

        // Check if approval is running
        if ($data['status'] !== 'ON_PROGRESS') {
            throw new Exception(self::EXC_APPROVAL_NOT_RUNNING);
        }

        // Check user permission
        if (!ApprovalRepository::isUserHasPermission($approvalId, $userId)) {
            throw new Exception(self::EXC_PERMISSION_DENIED);
        }

        // Update status to rejected
        ApprovalRepository::update($approvalId, 'REJECTED', null, null);

        // Create rejection history
        if ($file instanceof UploadedFile) {
            ApprovalHistoryRepository::insertWithFile(
                $approvalId,
                $data['flow_step_id'],
                $userId,
                "Persetujuan pada tahap {$data['flow_step_name']} ditolak oleh {$user['name']}.",
                ApprovalHistory::FLAG_REJECTED,
                $notes,
                $file
            );
        } else {
            ApprovalHistoryRepository::insert(
                $approvalId,
                $data['flow_step_id'],
                $userId,
                "Persetujuan pada tahap {$data['flow_step_name']} ditolak oleh {$user['name']}.",
                ApprovalHistory::FLAG_REJECTED,
                $notes,
                $file
            );
        }

        $previousApprovers = ApprovalRepository::getCurrentApprovers($approvalId);

        // Return updated status
        $result = ApprovalRepository::getCurrentStatus($approvalId);
        $result['stakeholders'] = [
            'owner' => ApprovalRepository::getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => null,
        ];

        return $result;
    }

    /**
     * Reset approval workflow
     */
    public function reset(int $approvalId, int $userId, ?string $notes = null, $file = null, ?array $parameters = null): array
    {
        // Update status and parameters
        ApprovalRepository::update($approvalId, 'ON_PROGRESS', null, $parameters);

        // Process next step
        $this->checkNextStep($approvalId);

        // Create reset history
        if ($file instanceof UploadedFile) {
            ApprovalHistoryRepository::insertWithFile(
                $approvalId,
                null,
                $userId,
                "Pengajuan ulang persetujuan dimulai",
                ApprovalHistory::FLAG_RESET,
                $notes,
                $file
            );
        } else {
            ApprovalHistoryRepository::insert(
                $approvalId,
                null,
                $userId,
                "Pengajuan ulang persetujuan dimulai",
                ApprovalHistory::FLAG_RESET,
                $notes,
                $file
            );
        }

        $nextApprovers = ApprovalRepository::getCurrentApprovers($approvalId);

        // Return updated status
        $result = ApprovalRepository::getCurrentStatus($approvalId);
        $result['stakeholders'] = [
            'owner' => ApprovalRepository::getOwner($approvalId),
            'previousApprovers' => null,
            'currentApprovers' => $nextApprovers,
        ];

        return $result;
    }

    /**
     * System reject approval
     */
    public function rejectBySystem(int $approvalId, int $relatedUserId, ?string $notes = null, $file = null): array
    {
        // Check if user exists
        $user = UserRepository::getById($relatedUserId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = ApprovalRepository::getCurrentStatus($approvalId);

        // Update status to rejected
        ApprovalRepository::update($approvalId, 'REJECTED', null, null);

        // Create system rejection history
        if ($file instanceof UploadedFile) {
            ApprovalHistoryRepository::insertWithFile(
                $approvalId,
                $data['flow_step_id'],
                $relatedUserId,
                "Persetujuan ditolak dan direset oleh System.",
                ApprovalHistory::FLAG_SYSTEM_REJECTED,
                $notes,
                $file
            );
        } else {
            ApprovalHistoryRepository::insert(
                $approvalId,
                $data['flow_step_id'],
                $relatedUserId,
                "Persetujuan ditolak dan direset oleh System.",
                ApprovalHistory::FLAG_SYSTEM_REJECTED,
                $notes,
                $file
            );
        }

        $previousApprovers = ApprovalRepository::getCurrentApprovers($approvalId);

        // Return updated status
        $result = ApprovalRepository::getCurrentStatus($approvalId);
        $result['stakeholders'] = [
            'owner' => ApprovalRepository::getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => null,
            'steps' => $this->getAllStepInfo($approvalId),
        ];

        return $result;
    }

    /**
     * Get approval path
     */
    public function getApprovalPath(int $approvalId): array
    {
        // Get current status
        $data = ApprovalRepository::getCurrentStatus($approvalId);

        // Get last histories for current approval cycle
        $lastHistories = [];
        $allHistories = ApprovalHistoryRepository::getAllByApprovalId($approvalId);
        
        foreach ($allHistories as $history) {
            if (in_array($history['flag'], [
                ApprovalHistory::FLAG_APPROVED,
                ApprovalHistory::FLAG_REJECTED,
                ApprovalHistory::FLAG_SYSTEM_REJECTED
            ])) {
                $lastHistories[] = $history;
            }

            if (in_array($history['flag'], [
                ApprovalHistory::FLAG_CREATED,
                ApprovalHistory::FLAG_RESET
            ])) {
                $lastHistories = [];
            }
        }

        // Get approval steps
        $approvalSteps = $this->getAllStepInfo($approvalId);

        // Determine current step index
        $currentStepIndex = -1;
        foreach ($approvalSteps as $index => $step) {
            if ($step['id'] == $data['flow_step_id']) {
                $currentStepIndex = $index;
                break;
            }
        }

        // Process steps with history data
        $processedSteps = [];
        foreach ($approvalSteps as $index => $step) {
            $step['type'] = 'unknown';
            $step['approver_id'] = null;
            $step['approver_email'] = null;
            $step['approver_username'] = null;
            $step['approver_name'] = null;
            $step['approval_notes'] = null;
            $step['approval_file'] = null;
            $step['approval_time'] = null;

            // Set step type
            if ($index < $currentStepIndex) {
                $step['type'] = 'passed';
            } elseif ($index == $currentStepIndex) {
                $step['type'] = 'current';
            } else {
                $step['type'] = 'incoming';
            }

            // Match with history
            foreach ($lastHistories as $history) {
                if ($history['flow_step_id'] == $step['id']) {
                    $step['type'] = $history['flag'];
                    $step['approver_id'] = $history['user_id'];
                    $step['approver_email'] = $history['user_email'];
                    $step['approver_username'] = $history['user_username'];
                    $step['approver_name'] = $history['user_name'];
                    $step['approval_notes'] = $history['notes'];
                    $step['approval_file'] = $history['file'];
                    $step['approval_time'] = $history['date_time'];
                    $step['attached_files'] = $history['attached_files'] ?? [];
                    break;
                }
            }

            $processedSteps[] = $step;
        }

        return $processedSteps;
    }

    /**
     * Get approval histories
     */
    public function getApprovalHistories(int $approvalId): array
    {
        return ApprovalHistoryRepository::getAllByApprovalId($approvalId);
    }

    /**
     * Rebuild approvers for running approvals
     */
    public function rebuildApprovers(): void
    {
        $approvals = ApprovalRepository::getRunningApprovals($this->companyId);

        foreach ($approvals as $approval) {
            $approvers = FlowRepository::getStepUsers($approval['flow_step_id'], $approval['parameters']);
            ApprovalRepository::assignApprovers($approval['id'], $approvers);
        }
    }

    /**
     * Check and process next step
     */
    private function checkNextStep(int $approvalId): void
    {
        // Get current approval data
        $approval = ApprovalRepository::getCurrentStatus($approvalId);

        // Get flow steps
        $steps = FlowRepository::getStepsById($approval['flow_id']);

        // Find next step
        $currentStepId = $approval['flow_step_id'];
        $currentStep = null;
        $currentStepOrder = -1;

        if ($currentStepId) {
            foreach ($steps as $step) {
                if ($step['id'] == $currentStepId) {
                    $currentStep = $step;
                    $currentStepOrder = $step['order'];
                    break;
                }
            }
        }

        $nextStep = null;
        foreach ($steps as $step) {
            if ($step['order'] > $currentStepOrder) {
                $condition = trim($step['condition'] ?? '');

                // If no condition, use this step
                if (empty($condition)) {
                    $nextStep = $step;
                    break;
                }

                // Evaluate condition
                $expressionLanguage = new ExpressionLanguage();
                $result = $expressionLanguage->evaluate($condition, $approval['parameters'] ?? []);
                
                if (is_bool($result) && $result === true) {
                    $nextStep = $step;
                    break;
                }
            }
        }

        if ($nextStep !== null) {
            // Update to next step
            ApprovalRepository::update($approvalId, 'ON_PROGRESS', $nextStep['id'], null);

            // Get approvers for next step
            $approvers = FlowRepository::getStepUsers($nextStep['id'], $approval['parameters'] ?? []);

            // Assign approvers
            ApprovalRepository::assignApprovers($approvalId, $approvers);

            if (empty($approvers)) {
                // Skip step if no approvers
                ApprovalHistoryRepository::insert(
                    $approvalId,
                    $nextStep['id'],
                    null,
                    "Proses {$nextStep['name']} dilewati karena tidak ada pemberi persetujuan di tahap ini.",
                    ApprovalHistory::FLAG_SKIP,
                    null
                );

                // Check next step recursively
                $this->checkNextStep($approvalId);
            }
        } else {
            // No more steps, mark as approved
            ApprovalRepository::assignApprovers($approvalId, []);
            ApprovalRepository::update($approvalId, 'APPROVED', null, null);

            ApprovalHistoryRepository::insert(
                $approvalId,
                null,
                null,
                'Proses persetujuan selesai.',
                ApprovalHistory::FLAG_DONE,
                null
            );
        }
    }

    /**
     * Get all step info with conditions evaluated
     */
    private function getAllStepInfo(int $approvalId): array
    {
        // Get current approval data
        $approval = ApprovalRepository::getCurrentStatus($approvalId);

        // Get flow steps
        $steps = FlowRepository::getStepsById($approval['flow_id']);

        $filteredSteps = [];
        foreach ($steps as $step) {
            $condition = trim($step['condition'] ?? '');

            // If no condition, include step
            if (empty($condition)) {
                $filteredSteps[] = $step;
                continue;
            }

            // Evaluate condition
            $expressionLanguage = new ExpressionLanguage();
            $result = $expressionLanguage->evaluate($condition, $approval['parameters'] ?? []);
            
            if (is_bool($result) && $result === true) {
                $filteredSteps[] = $step;
            }
        }

        // Add approvers to each step
        foreach ($filteredSteps as &$step) {
            $step['approvers'] = FlowRepository::getStepUsers($step['id'], $approval['parameters'] ?? []);
        }

        return $filteredSteps;
    }

    /**
     * Set company ID
     */
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * Get company ID
     */
    public function getCompanyId(): int
    {
        return $this->companyId;
    }
}
