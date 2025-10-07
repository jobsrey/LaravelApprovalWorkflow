<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

class UserRepository
{
    /**
     * Get user by ID
     */
    public static function getById(int $userId): ?array
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
            'username' => $user->username ?? $user->email,
            'fcmToken' => $user->fcmToken ?? null,
        ];
    }

    /**
     * Get multiple users by IDs
     */
    public static function getByIds(array $userIds): array
    {
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return $userModel::whereIn('id', $userIds)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username ?? $user->email,
                    'fcmToken' => $user->fcmToken ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Search users by name or email
     */
    public static function search(string $query, int $limit = 10): array
    {
        $userModel = config('approval-workflow.user_model', 'App\\Models\\User');
        
        return $userModel::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username ?? $user->email,
                ];
            })
            ->toArray();
    }
}
