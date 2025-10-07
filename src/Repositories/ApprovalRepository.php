<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalActiveUser;
use Illuminate\Support\Facades\DB;

class ApprovalRepository
{
    /**
     * Get current status of an approval.
     */
    public function getCurrentStatus(int $approvalId): array
    {
        $approval = Approval::with('currentStep')->find($approvalId);
        
        if (!$approval) {
            throw new \Exception("Approval with ID '$approvalId' not found!");
        }
        
        return [
            'id' => $approval->id,
            'flow_id' => $approval->flow_id,
            'status' => $approval->status,
            'flow_step_id' => $approval->flow_step_id,
            'flow_step_name' => $approval->currentStep?->name,
            'parameters' => $approval->parameters,
        ];
    }

    /**
     * Get running approvals for a company.
     */
    public function getRunningApprovals(int $companyId): array
    {
        return Approval::where('company_id', $companyId)
            ->where('status', 'ON_PROGRESS')
            ->get()
            ->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'flow_id' => $approval->flow_id,
                    'status' => $approval->status,
                    'flow_step_id' => $approval->flow_step_id,
                    'parameters' => $approval->parameters,
                ];
            })
            ->toArray();
    }

    /**
     * Insert a new approval.
     */
    public function insert(int $companyId, int $flowId, int $userId, ?array $parameters): int
    {
        $approval = Approval::create([
            'company_id' => $companyId,
            'flow_id' => $flowId,
            'status' => 'ON_PROGRESS',
            'user_id' => $userId,
            'parameters' => $parameters,
        ]);
        
        return $approval->id;
    }

    /**
     * Update an approval.
     */
    public function update(int $approvalId, string $status, ?int $flowStepId, ?array $parameters): void
    {
        $data = [
            'status' => $status,
            'flow_step_id' => $flowStepId,
        ];
        
        if ($parameters !== null) {
            $data['parameters'] = $parameters;
        }
        
        Approval::where('id', $approvalId)->update($data);
    }

    /**
     * Check if user has permission to approve.
     */
    public function isUserHasPermission(int $approvalId, int $userId): bool
    {
        return ApprovalActiveUser::where('approval_id', $approvalId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get current approvers for an approval.
     */
    public function getCurrentApprovers(int $approvalId): array
    {
        $userModel = config('approval-workflow.user_model', \App\Models\User::class);
        
        return DB::table('wf_approval_active_users as waau')
            ->join('users as u', 'u.id', '=', 'waau.user_id')
            ->where('waau.approval_id', $approvalId)
            ->select('u.id as user_id', 'u.name', 'u.email')
            ->get()
            ->map(fn($user) => (array) $user)
            ->toArray();
    }

    /**
     * Get owner of an approval.
     */
    public function getOwner(int $approvalId): ?array
    {
        $approval = Approval::with('owner')->find($approvalId);
        
        if (!$approval || !$approval->owner) {
            return null;
        }
        
        return [
            'user_id' => $approval->owner->id,
            'name' => $approval->owner->name ?? null,
            'email' => $approval->owner->email ?? null,
        ];
    }

    /**
     * Assign approvers to an approval.
     */
    public function assignApprovers(int $approvalId, array $approvers): void
    {
        // Delete existing approvers
        ApprovalActiveUser::where('approval_id', $approvalId)->delete();
        
        // Assign new approvers
        if (!empty($approvers)) {
            foreach ($approvers as $approver) {
                ApprovalActiveUser::create([
                    'approval_id' => $approvalId,
                    'user_id' => $approver['id'],
                ]);
            }
        }
    }
}
