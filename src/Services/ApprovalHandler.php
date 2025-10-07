<?php

namespace AsetKita\LaravelApprovalWorkflow\Services;

use Exception;
use AsetKita\LaravelApprovalWorkflow\Repositories\ApprovalHistoryRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\ApprovalRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\FlowRepository;
use AsetKita\LaravelApprovalWorkflow\Repositories\UserRepository;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Illuminate\Http\UploadedFile;

class ApprovalHandler
{
    // Exception constants
    public const EXC_USER_NOT_FOUND = 'exc_user_not_found';
    public const EXC_FLOW_NOT_FOUND = 'exc_flow_not_found';
    public const EXC_PERMISSION_DENIED = 'exc_permission_denied';
    public const EXC_APPROVAL_NOT_RUNNING = 'exc_approval_not_running';
    public const EXC_APPROVAL_NOT_REJECTED = 'exc_approval_not_rejected';

    protected int $companyId;
    protected FlowRepository $flowRepository;
    protected ApprovalRepository $approvalRepository;
    protected ApprovalHistoryRepository $historyRepository;
    protected UserRepository $userRepository;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->flowRepository = new FlowRepository();
        $this->approvalRepository = new ApprovalRepository();
        $this->historyRepository = new ApprovalHistoryRepository();
        $this->userRepository = new UserRepository();
    }

    /**
     * Start a new approval workflow.
     *
     * @param string $flowType Identifier flow (e.g., 'PR', 'PO')
     * @param int $userId User ID who initiates the approval
     * @param array|null $parameters Additional parameters for the workflow
     * @return array Current status of the approval
     * @throws Exception
     */
    public function start(string $flowType, int $userId, ?array $parameters = null): array
    {
        // Check if user exists
        $user = $this->userRepository->getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get flow by type
        $flow = $this->flowRepository->getByType($this->companyId, $flowType);
        if ($flow === null) {
            throw new Exception(self::EXC_FLOW_NOT_FOUND);
        }

        // Insert approval
        $approvalId = $this->approvalRepository->insert(
            $this->companyId,
            $flow->id,
            $userId,
            $parameters
        );

        // Create history for approval creation
        $this->historyRepository->insert(
            $approvalId,
            null,
            $userId,
            'Permintaan persetujuan dibuat',
            ApprovalHistory::HFLAG_CREATED,
            null,
            null
        );

        // Process to next step
        $previousApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);
        
        if ($flow->is_active != 0) {
            $this->checkNextStep($approvalId);
        } else {
            // Mark as approved if flow is not active
            $this->approvalRepository->update($approvalId, 'APPROVED', null, null);
            
            $this->historyRepository->insert(
                $approvalId,
                null,
                $userId,
                'Persetujuan dianggap selesai karena flow persetujuan NON-AKTIF.',
                ApprovalHistory::HFLAG_DONE,
                null,
                null
            );
        }
        
        $nextApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);

        // Return current status
        $status = $this->approvalRepository->getCurrentStatus($approvalId);
        $status['stakeholders'] = [
            'owner' => $this->approvalRepository->getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => $nextApprovers,
        ];
        
        return $status;
    }

    /**
     * Approve current step.
     *
     * @param int $approvalId Approval ID
     * @param int $userId User ID who approves
     * @param string|null $notes Notes for approval (optional)
     * @param UploadedFile|array|null $file File attachment (optional) - UploadedFile or array of files
     * @return array Current status of the approval
     * @throws Exception
     */
    public function approve(int $approvalId, int $userId, ?string $notes = null, UploadedFile|array|null $file = null): array
    {
        // Check if user exists
        $user = $this->userRepository->getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = $this->approvalRepository->getCurrentStatus($approvalId);

        // Reject if document is closed
        if ($data['status'] !== 'ON_PROGRESS') {
            throw new Exception(self::EXC_APPROVAL_NOT_RUNNING);
        }

        // Validate permission
        if (!$this->approvalRepository->isUserHasPermission($approvalId, $userId)) {
            throw new Exception(self::EXC_PERMISSION_DENIED);
        }

        // Create approval history
        $this->historyRepository->insert(
            $approvalId,
            $data['flow_step_id'],
            $userId,
            "Persetujuan pada tahap {$data['flow_step_name']} disetujui oleh {$user['name']}.",
            ApprovalHistory::HFLAG_APPROVED,
            $notes,
            $file
        );

        // Process to next step
        $previousApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);
        $this->checkNextStep($approvalId);
        $nextApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);

        // Return current status
        $status = $this->approvalRepository->getCurrentStatus($approvalId);
        $status['stakeholders'] = [
            'owner' => $this->approvalRepository->getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => $nextApprovers,
        ];
        
        if (empty($nextApprovers)) {
            $status['stakeholders']['steps'] = $this->getAllStepInfo($approvalId);
        }
        
        return $status;
    }

    /**
     * Reject current step.
     *
     * @param int $approvalId Approval ID
     * @param int $userId User ID who rejects
     * @param string|null $notes Notes for rejection (optional)
     * @param UploadedFile|array|null $file File attachment (optional) - UploadedFile or array of files
     * @return array Current status of the approval
     * @throws Exception
     */
    public function reject(int $approvalId, int $userId, ?string $notes = null, UploadedFile|array|null $file = null): array
    {
        // Check if user exists
        $user = $this->userRepository->getById($userId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = $this->approvalRepository->getCurrentStatus($approvalId);

        // Reject if document is closed
        if ($data['status'] !== 'ON_PROGRESS') {
            throw new Exception(self::EXC_APPROVAL_NOT_RUNNING);
        }

        // Validate permission
        if (!$this->approvalRepository->isUserHasPermission($approvalId, $userId)) {
            throw new Exception(self::EXC_PERMISSION_DENIED);
        }

        // Update status to rejected
        $this->approvalRepository->update($approvalId, 'REJECTED', null, null);

        // Create rejection history
        $this->historyRepository->insert(
            $approvalId,
            $data['flow_step_id'],
            $userId,
            "Persetujuan pada tahap {$data['flow_step_name']} ditolak oleh {$user['name']}.",
            ApprovalHistory::HFLAG_REJECTED,
            $notes,
            $file
        );

        $previousApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);

        // Return current status
        $status = $this->approvalRepository->getCurrentStatus($approvalId);
        $status['stakeholders'] = [
            'owner' => $this->approvalRepository->getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => null,
        ];
        
        return $status;
    }

    /**
     * Reject approval by system (can reject at any status).
     *
     * @param int $approvalId Approval ID
     * @param int $relatedUserId User ID who triggered the system rejection
     * @param string|null $notes Notes for rejection (optional)
     * @param UploadedFile|array|null $file File attachment (optional) - UploadedFile or array of files
     * @return array Current status of the approval
     * @throws Exception
     */
    public function rejectBySystem(int $approvalId, int $relatedUserId, ?string $notes = null, UploadedFile|array|null $file = null): array
    {
        // Check if user exists
        $user = $this->userRepository->getById($relatedUserId);
        if ($user === null) {
            throw new Exception(self::EXC_USER_NOT_FOUND);
        }

        // Get current status
        $data = $this->approvalRepository->getCurrentStatus($approvalId);

        // Update status to rejected
        $this->approvalRepository->update($approvalId, 'REJECTED', null, null);

        // Create system rejection history
        $this->historyRepository->insert(
            $approvalId,
            $data['flow_step_id'],
            $relatedUserId,
            'Persetujuan ditolak dan direset oleh System.',
            ApprovalHistory::HFLAG_SYSTEM_REJECTED,
            $notes,
            $file
        );

        $previousApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);

        // Return current status
        $status = $this->approvalRepository->getCurrentStatus($approvalId);
        $status['stakeholders'] = [
            'owner' => $this->approvalRepository->getOwner($approvalId),
            'previousApprovers' => $previousApprovers,
            'currentApprovers' => null,
            'steps' => $this->getAllStepInfo($approvalId),
        ];
        
        return $status;
    }

    /**
     * Reset a rejected approval.
     *
     * @param int $approvalId Approval ID
     * @param int $userId User ID who resets
     * @param string|null $notes Notes for reset (optional)
     * @param UploadedFile|array|null $file File attachment (optional) - UploadedFile or array of files
     * @param array|null $parameters New parameters (optional)
     * @return array Current status of the approval
     * @throws Exception
     */
    public function reset(int $approvalId, int $userId, ?string $notes = null, UploadedFile|array|null $file = null, ?array $parameters = null): array
    {
        // Update status to on progress
        $this->approvalRepository->update($approvalId, 'ON_PROGRESS', null, $parameters);

        // Process to next step
        $this->checkNextStep($approvalId);

        // Create reset history
        $this->historyRepository->insert(
            $approvalId,
            null,
            $userId,
            'Pengajuan ulang persetujuan dimulai',
            ApprovalHistory::HFLAG_RESET,
            $notes,
            $file
        );

        $nextApprovers = $this->approvalRepository->getCurrentApprovers($approvalId);

        // Return current status
        $status = $this->approvalRepository->getCurrentStatus($approvalId);
        $status['stakeholders'] = [
            'owner' => $this->approvalRepository->getOwner($approvalId),
            'previousApprovers' => null,
            'currentApprovers' => $nextApprovers,
        ];
        
        return $status;
    }

    /**
     * Rebuild approvers for all running approvals.
     */
    public function rebuildApprovers(): void
    {
        $approvals = $this->approvalRepository->getRunningApprovals($this->companyId);

        foreach ($approvals as $approval) {
            $approvers = $this->flowRepository->getStepUsers(
                $approval['flow_step_id'],
                $approval['parameters']
            );

            $this->approvalRepository->assignApprovers($approval['id'], $approvers);
        }
    }

    /**
     * Get next steps for an approval.
     */
    public function getNextSteps(int $approvalId): mixed
    {
        $currentStatus = $this->approvalRepository->getCurrentStatus($approvalId);
        $steps = $this->getAllStepInfo($approvalId);

        $foundCurrent = false;
        foreach ($steps as $step) {
            if ($foundCurrent) {
                return $step;
            }
            if ($step['id'] === $currentStatus['flow_step_id']) {
                $foundCurrent = true;
            }
        }

        return null;
    }

    /**
     * Get approval path (all steps with status).
     */
    public function getApprovalPath(int $approvalId): array
    {
        // Get current status
        $data = $this->approvalRepository->getCurrentStatus($approvalId);

        // Get last histories
        $lastHistories = [];
        foreach ($this->historyRepository->getAllByApprovalId($approvalId) as $history) {
            if (in_array($history['flag'], [
                ApprovalHistory::HFLAG_APPROVED,
                ApprovalHistory::HFLAG_REJECTED,
                ApprovalHistory::HFLAG_SYSTEM_REJECTED
            ])) {
                $lastHistories[] = $history;
            }

            if (in_array($history['flag'], [
                ApprovalHistory::HFLAG_CREATED,
                ApprovalHistory::HFLAG_RESET
            ])) {
                $lastHistories = [];
            }
        }

        // Get all approval steps
        $approvalSteps = $this->getAllStepInfo($approvalId);

        // Determine current step index
        $currentStepIndex = -1;
        foreach ($approvalSteps as $index => $approvalStep) {
            if ($approvalStep['id'] == $data['flow_step_id']) {
                $currentStepIndex = $index;
                break;
            }
        }

        // Set step types and merge with history
        $result = [];
        foreach ($approvalSteps as $index => $approvalStep) {
            $approvalStep['type'] = 'unknown';
            $approvalStep['approver_id'] = null;
            $approvalStep['approver_email'] = null;
            $approvalStep['approver_name'] = null;
            $approvalStep['approval_notes'] = null;
            $approvalStep['approval_file'] = null;
            $approvalStep['approval_time'] = null;

            // Set step type
            if ($index < $currentStepIndex) {
                $approvalStep['type'] = 'passed';
            } elseif ($index == $currentStepIndex) {
                $approvalStep['type'] = 'current';
            } else {
                $approvalStep['type'] = 'incoming';
            }

            // Merge with history data
            foreach ($lastHistories as $history) {
                if ($history['flow_step_id'] == $approvalStep['id']) {
                    $approvalStep['type'] = $history['flag'];
                    $approvalStep['approver_id'] = $history['user_id'];
                    $approvalStep['approver_email'] = $history['user_email'];
                    $approvalStep['approver_name'] = $history['user_name'];
                    $approvalStep['approval_notes'] = $history['notes'];
                    $approvalStep['approval_file'] = $history['file'];
                    $approvalStep['approval_time'] = $history['date_time'];
                    break;
                }
            }

            $result[] = $approvalStep;
        }

        // If approved, remove incoming and current steps
        if ($data['status'] == 'APPROVED') {
            $result = array_filter($result, function ($item) {
                return !in_array($item['type'], ['incoming', 'current']);
            });
        }

        return array_values($result);
    }

    /**
     * Get approval histories.
     */
    public function getApprovalHistories(int $approvalId): array
    {
        return $this->historyRepository->getAllByApprovalId($approvalId);
    }

    /**
     * Check and process next step.
     */
    protected function checkNextStep(int $approvalId): void
    {
        // Get current approval status
        $approval = $this->approvalRepository->getCurrentStatus($approvalId);

        // Get all steps
        $steps = $this->flowRepository->getStepsById($approval['flow_id']);

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

                // Check condition
                $expressionLanguage = new ExpressionLanguage();
                $result = $expressionLanguage->evaluate($condition, $approval['parameters'] ?? []);
                
                if (is_bool($result) && $result === true) {
                    $nextStep = $step;
                    break;
                }
            }
        }

        if ($nextStep !== null) {
            // Save next step
            $this->approvalRepository->update($approvalId, 'ON_PROGRESS', $nextStep['id'], null);

            // Get approvers for next step
            $approvers = $this->flowRepository->getStepUsers($nextStep['id'], $approval['parameters']);

            // Assign approvers
            $this->approvalRepository->assignApprovers($approvalId, $approvers);

            if (count($approvers) <= 0) {
                // Skip step if no approvers
                $this->historyRepository->insert(
                    $approvalId,
                    $nextStep['id'],
                    null,
                    "Proses {$nextStep['name']} dilewati karena tidak ada pemberi persetujuan di tahap ini.",
                    ApprovalHistory::HFLAG_SKIP,
                    null,
                    null
                );

                $this->checkNextStep($approvalId);
            }
        } else {
            // No next step, approval is complete
            $this->approvalRepository->assignApprovers($approvalId, []);
            $this->approvalRepository->update($approvalId, 'APPROVED', null, null);

            $this->historyRepository->insert(
                $approvalId,
                null,
                null,
                'Proses persetujuan selesai.',
                ApprovalHistory::HFLAG_DONE,
                null,
                null
            );
        }
    }

    /**
     * Get all step info for an approval.
     */
    protected function getAllStepInfo(int $approvalId): array
    {
        // Get current approval
        $approval = $this->approvalRepository->getCurrentStatus($approvalId);

        // Get all steps
        $steps = $this->flowRepository->getStepsById($approval['flow_id']);

        $filteredSteps = [];
        foreach ($steps as $step) {
            $condition = trim($step['condition'] ?? '');

            // If no condition, include step
            if (empty($condition)) {
                $filteredSteps[] = $step;
                continue;
            }

            // Check condition
            $expressionLanguage = new ExpressionLanguage();
            $result = $expressionLanguage->evaluate($condition, $approval['parameters'] ?? []);
            
            if (is_bool($result) && $result === true) {
                $filteredSteps[] = $step;
            }
        }

        // Get approvers for each step
        foreach ($filteredSteps as $index => $step) {
            $filteredSteps[$index]['approvers'] = $this->flowRepository->getStepUsers(
                $step['id'],
                $approval['parameters']
            );
        }

        return $filteredSteps;
    }
}
