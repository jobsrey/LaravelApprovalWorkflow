<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepUser;
use Illuminate\Database\Eloquent\Collection;

class FlowRepository
{
    /**
     * Get flow by type
     */
    public static function getByType(int $companyId, string $flowType): ?array
    {
        $flow = Flow::where('company_id', $companyId)
            ->where('type', $flowType)
            ->first();

        if (!$flow) {
            return null;
        }

        return [
            'id' => $flow->id,
            'company_id' => $flow->company_id,
            'type' => $flow->type,
            'name' => $flow->name,
            'is_active' => $flow->is_active,
        ];
    }

    /**
     * Get steps by flow ID
     */
    public static function getStepsById(int $flowId): array
    {
        return FlowStep::where('flow_id', $flowId)
            ->orderBy('order')
            ->get()
            ->map(function ($step) {
                return [
                    'id' => $step->id,
                    'flow_id' => $step->flow_id,
                    'order' => $step->order,
                    'name' => $step->name,
                    'condition' => $step->condition,
                ];
            })
            ->toArray();
    }

    /**
     * Get step users with system group resolution
     */
    public static function getStepUsers(int $flowStepId, ?array $parameters = []): array
    {
        $stepUsers = FlowStepUser::where('flow_step_id', $flowStepId)->get();
        $users = [];

        foreach ($stepUsers as $stepUser) {
            if ($stepUser->type === 'USER' && $stepUser->user_id) {
                // Direct user assignment
                $user = self::getUserById($stepUser->user_id);
                if ($user) {
                    $users[] = $user;
                }
            } elseif ($stepUser->type === 'SYSTEM_GROUP') {
                // System group resolution
                $groupUsers = self::resolveSystemGroup($stepUser->user_group_id, $parameters);
                $users = array_merge($users, $groupUsers);
            }
        }

        return array_unique($users, SORT_REGULAR);
    }

    /**
     * Resolve system group users
     */
    private static function resolveSystemGroup(string $groupType, array $parameters): array
    {
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        $users = [];

        switch ($groupType) {
            case 'department-manager':
                if (isset($parameters['departmentId'])) {
                    $departmentId = $parameters['departmentId'];
                    // Override check
                    if (isset($parameters['overrideManagerUserId'])) {
                        $user = self::getUserById($parameters['overrideManagerUserId']);
                        if ($user) $users[] = $user;
                    } else {
                        // Get department manager logic here
                        // This would depend on your user/department structure
                        $users = self::getDepartmentManagers($departmentId);
                    }
                }
                break;

            case 'department-head':
                if (isset($parameters['departmentId'])) {
                    $departmentId = $parameters['departmentId'];
                    // Override check
                    if (isset($parameters['overrideHeadUserId'])) {
                        $user = self::getUserById($parameters['overrideHeadUserId']);
                        if ($user) $users[] = $user;
                    } else {
                        // Get department head logic here
                        $users = self::getDepartmentHeads($departmentId);
                    }
                }
                break;

            case 'asset-coordinator':
                if (isset($parameters['assetCategoryId'])) {
                    $users = self::getAssetCoordinators($parameters['assetCategoryId']);
                }
                break;

            case 'origin-asset-user':
                if (isset($parameters['originAssetUserId'])) {
                    $user = self::getUserById($parameters['originAssetUserId']);
                    if ($user) $users[] = $user;
                }
                break;

            case 'destination-asset-user':
                if (isset($parameters['destinationAssetUserId'])) {
                    $user = self::getUserById($parameters['destinationAssetUserId']);
                    if ($user) $users[] = $user;
                }
                break;
        }

        return $users;
    }

    /**
     * Get user by ID
     */
    private static function getUserById(int $userId): ?array
    {
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'fcmToken' => $user->fcmToken ?? null,
        ];
    }

    /**
     * Get department managers - implement based on your structure
     */
    private static function getDepartmentManagers(int $departmentId): array
    {
        // Implement based on your department/user relationship
        // This is a placeholder - adjust according to your database structure
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return $userModel::where('department_id', $departmentId)
            ->where('role', 'manager')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fcmToken' => $user->fcmToken ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get department heads - implement based on your structure
     */
    private static function getDepartmentHeads(int $departmentId): array
    {
        // Implement based on your department/user relationship
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return $userModel::where('department_id', $departmentId)
            ->where('role', 'head')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fcmToken' => $user->fcmToken ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get asset coordinators - implement based on your structure
     */
    private static function getAssetCoordinators(int $assetCategoryId): array
    {
        // Implement based on your asset/user relationship
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return $userModel::where('asset_category_id', $assetCategoryId)
            ->where('role', 'coordinator')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fcmToken' => $user->fcmToken ?? null,
                ];
            })
            ->toArray();
    }
}
