<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepApprover;
use AsetKita\LaravelApprovalWorkflow\Models\DepartmentUser;
use AsetKita\LaravelApprovalWorkflow\Models\AssetCoordinatorUser;
use AsetKita\LaravelApprovalWorkflow\Models\ApproverGroupUser;

class FlowRepository
{
    /**
     * Get flow by type and company ID.
     */
    public function getByType(int $companyId, string $type): ?Flow
    {
        return Flow::where('company_id', $companyId)
            ->where('type', $type)
            ->first();
    }

    /**
     * Get all steps for a flow.
     */
    public function getStepsById(int $flowId): array
    {
        return FlowStep::where('flow_id', $flowId)
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    /**
     * Get step users based on step ID and approval parameters.
     */
    public function getStepUsers(int $stepId, ?array $approvalParameters): array
    {
        $approvers = FlowStepApprover::where('flow_step_id', $stepId)->get();
        
        $userIds = [];
        
        foreach ($approvers as $approver) {
            // Handle Type USER
            if ($approver->type === 'USER') {
                $userIds[] = $approver->data;
            }
            // Handle Type GROUP
            elseif ($approver->type === 'GROUP') {
                $groupUserIds = ApproverGroupUser::where('approver_group_id', $approver->data)
                    ->pluck('user_id')
                    ->toArray();
                $userIds = array_merge($userIds, $groupUserIds);
            }
            // Handle Type SYSTEM_GROUP
            elseif ($approver->type === 'SYSTEM_GROUP') {
                $data = $approver->data;
                
                // Handle department-manager
                if ($data === 'department-manager') {
                    $overrideUserId = $this->getParamValue($approvalParameters, 'overrideManagerUserId');
                    if ($overrideUserId) {
                        $userIds[] = $overrideUserId;
                    } else {
                        $departmentUserIds = DepartmentUser::where('department_id', $this->getParamValue($approvalParameters, 'departmentId'))
                            ->where('job_level', 'MANAGER')
                            ->pluck('user_id')
                            ->toArray();
                        $userIds = array_merge($userIds, $departmentUserIds);
                    }
                }
                // Handle department-head
                elseif ($data === 'department-head') {
                    $overrideUserId = $this->getParamValue($approvalParameters, 'overrideHeadUserId');
                    if ($overrideUserId) {
                        $userIds[] = $overrideUserId;
                    } else {
                        $departmentUserIds = DepartmentUser::where('department_id', $this->getParamValue($approvalParameters, 'departmentId'))
                            ->where('job_level', 'HEAD')
                            ->pluck('user_id')
                            ->toArray();
                        $userIds = array_merge($userIds, $departmentUserIds);
                    }
                }
                // Handle department-staff
                elseif ($data === 'department-staff') {
                    $departmentUserIds = DepartmentUser::where('department_id', $this->getParamValue($approvalParameters, 'departmentId'))
                        ->where('job_level', 'STAFF')
                        ->pluck('user_id')
                        ->toArray();
                    $userIds = array_merge($userIds, $departmentUserIds);
                }
                // Handle asset-coordinator
                elseif ($data === 'asset-coordinator') {
                    $assetUserIds = AssetCoordinatorUser::where('asset_category_id', $this->getParamValue($approvalParameters, 'assetCategoryId'))
                        ->pluck('user_id')
                        ->toArray();
                    $userIds = array_merge($userIds, $assetUserIds);
                }
                // Handle origin-asset-user
                elseif ($data === 'origin-asset-user') {
                    $userId = $this->getParamValue($approvalParameters, 'originAssetUserId');
                    if ($userId) {
                        $userIds[] = $userId;
                    }
                }
                // Handle destination-asset-user
                elseif ($data === 'destination-asset-user') {
                    $userId = $this->getParamValue($approvalParameters, 'destinationAssetUserId');
                    if ($userId) {
                        $userIds[] = $userId;
                    }
                }
            }
        }
        
        if (empty($userIds)) {
            return [];
        }
        
        // Get unique user IDs
        $userIds = array_unique($userIds);
        
        // Return users with their details
        $userModel = config('approval-workflow.user_model', \App\Models\User::class);
        return $userModel::whereIn('id', $userIds)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email ?? null,
                    'name' => $user->name ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get parameter value from array.
     */
    private function getParamValue(?array $params, string $key)
    {
        if (!$params) {
            return null;
        }
        return $params[$key] ?? null;
    }
}
