<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;
use Illuminate\Http\UploadedFile;

class ApprovalHistoryRepository
{
    /**
     * Insert new approval history
     */
    public static function insert(
        int $approvalId,
        ?int $flowStepId,
        ?int $userId,
        string $title,
        string $flag,
        ?string $notes,
        $file = null
    ): ApprovalHistory {
        $history = ApprovalHistory::create([
            'approval_id' => $approvalId,
            'flow_step_id' => $flowStepId,
            'user_id' => $userId,
            'title' => $title,
            'flag' => $flag,
            'notes' => $notes,
            'file' => is_string($file) ? $file : null, // Legacy support
            'date_time' => now(),
        ]);

        // Handle file attachment with Spatie Media Library
        if ($file instanceof UploadedFile) {
            $history->addMediaFromRequest('file')
                ->toMediaCollection('files');
        } elseif (is_string($file) && file_exists($file)) {
            $history->addMedia($file)
                ->toMediaCollection('files');
        }

        return $history;
    }

    /**
     * Get all histories by approval ID
     */
    public static function getAllByApprovalId(int $approvalId): array
    {
        return ApprovalHistory::where('approval_id', $approvalId)
            ->leftJoin('users as u', 'u.id', '=', 'wf_approval_histories.user_id')
            ->leftJoin('wf_flow_steps as wfs', 'wfs.id', '=', 'wf_approval_histories.flow_step_id')
            ->select([
                'wf_approval_histories.id',
                'wf_approval_histories.approval_id',
                'wf_approval_histories.flow_step_id',
                'wfs.order as flow_step_order',
                'wfs.flow_id as flow_step_flow_id',
                'wfs.name as flow_step_name',
                'wfs.condition as flow_step_condition',
                'wf_approval_histories.user_id',
                'u.email as user_email',
                'u.username as user_username',
                'u.name as user_name',
                'wf_approval_histories.title',
                'wf_approval_histories.flag',
                'wf_approval_histories.notes',
                'wf_approval_histories.file',
                'wf_approval_histories.date_time',
            ])
            ->orderBy('wf_approval_histories.date_time')
            ->get()
            ->map(function ($history) {
                // Add media files information
                $historyModel = ApprovalHistory::find($history->id);
                $files = $historyModel ? $historyModel->getFileUrls() : collect();
                
                return array_merge($history->toArray(), [
                    'attached_files' => $files->toArray()
                ]);
            })
            ->toArray();
    }

    /**
     * Insert with file attachment
     */
    public static function insertWithFile(
        int $approvalId,
        ?int $flowStepId,
        ?int $userId,
        string $title,
        string $flag,
        ?string $notes,
        ?UploadedFile $file,
        ?string $fileName = null
    ): ApprovalHistory {
        $history = self::insert($approvalId, $flowStepId, $userId, $title, $flag, $notes);

        if ($file) {
            $mediaItem = $history->addMedia($file)
                ->toMediaCollection('files');
            
            if ($fileName) {
                $mediaItem->update(['name' => $fileName]);
            }
        }

        return $history;
    }

    /**
     * Insert with multiple files
     */
    public static function insertWithFiles(
        int $approvalId,
        ?int $flowStepId,
        ?int $userId,
        string $title,
        string $flag,
        ?string $notes,
        array $files = []
    ): ApprovalHistory {
        $history = self::insert($approvalId, $flowStepId, $userId, $title, $flag, $notes);

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $history->addMedia($file)
                    ->toMediaCollection('files');
            }
        }

        return $history;
    }
}
