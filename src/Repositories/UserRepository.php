<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

class UserRepository
{
    /**
     * Get user by ID.
     */
    public function getById(int $id): ?array
    {
        $userModel = config('approval-workflow.user_model', \App\Models\User::class);
        
        $user = $userModel::find($id);
        
        if (!$user) {
            return null;
        }
        
        return [
            'id' => $user->id,
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ];
    }

    /**
     * Get users by IDs.
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $userModel = config('approval-workflow.user_model', \App\Models\User::class);
        
        return $userModel::whereIn('id', $ids)
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
}
