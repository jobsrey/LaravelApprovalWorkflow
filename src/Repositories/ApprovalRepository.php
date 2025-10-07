<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalActiveUser;
use Illuminate\Database\Eloquent\Collection;

class ApprovalRepository
{
    /**
     * Get current status of an approval
     */
    public static function getCurrentStatus(int $approvalId): array
    {
        $approval = Approval::with(['currentStep', 'flow'])
            ->findOrFail($approvalId);

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
     * Get running approvals for a company
     */
    public static function getRunningApprovals(int $companyId): Collection
    {
        return Approval::running($companyId)
            ->with(['currentStep', 'flow'])
            ->get()
            ->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'flow_id' => $approval->flow_id,
                    'status' => $approval->status,
                    'flow_step_id' => $approval->flow_step_id,
                    'parameters' => $approval->parameters,
                ];
            });
    }

    /**
     * Create new approval
     */
    public static function insert(int $companyId, int $flowId, int $userId, ?array $parameters): int
    {
        $approval = Approval::create([
            'company_id' => $companyId,
            'flow_id' => $flowId,
            'user_id' => $userId,
            'status' => 'ON_PROGRESS',
            'parameters' => $parameters,
        ]);

        return $approval->id;
    }

    /**
     * Update approval
     */
    public static function update(int $approvalId, string $status, ?int $flowStepId, ?array $parameters): void
    {
        $updateData = [
            'status' => $status,
            'flow_step_id' => $flowStepId,
        ];

        if ($parameters !== null) {
            $updateData['parameters'] = $parameters;
        }

        Approval::where('id', $approvalId)->update($updateData);
    }

    /**
     * Check if user has permission for approval
     */
    public static function isUserHasPermission(int $approvalId, int $userId): bool
    {
        return ApprovalActiveUser::where('approval_id', $approvalId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get current approvers
     */
    public static function getCurrentApprovers(int $approvalId): array
    {
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return ApprovalActiveUser::where('approval_id', $approvalId)
            ->join('users', 'users.id', '=', 'wf_approval_active_users.user_id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.fcmToken'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get approval owner
     */
    public static function getOwner(int $approvalId): ?array
    {
        $approval = Approval::with('owner')->find($approvalId);
        
        if (!$approval || !$approval->owner) {
            return null;
        }

        return [
            'user_id' => $approval->owner->id,
            'name' => $approval->owner->name,
            'email' => $approval->owner->email,
            'fcmToken' => $approval->owner->fcmToken ?? null,
        ];
    }

    /**
     * Assign approvers to approval
     */
    public static function assignApprovers(int $approvalId, array $approvers): void
    {
        // Remove existing approvers
        ApprovalActiveUser::where('approval_id', $approvalId)->delete();

        // Add new approvers
        if (!empty($approvers)) {
            $approverData = collect($approvers)->map(function ($approver) use ($approvalId) {
                return [
                    'approval_id' => $approvalId,
                    'user_id' => $approver['id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            ApprovalActiveUser::insert($approverData);
        }
    }
}
