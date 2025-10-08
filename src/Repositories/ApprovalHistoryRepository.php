<?php

namespace AsetKita\LaravelApprovalWorkflow\Repositories;

use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class ApprovalHistoryRepository
{
    /**
     * Insert a new approval history.
     * 
     * @param int $approvalId
     * @param int|null $flowStepId
     * @param int|null $userId
     * @param string $title
     * @param string $flag
     * @param string|null $notes
     * @param UploadedFile|array|null $file - UploadedFile instance, array of files, or null
     * @return ApprovalHistory
     */
    public function insert(
        int $approvalId,
        ?int $flowStepId,
        ?int $userId,
        string $title,
        string $flag,
        ?string $notes,
        UploadedFile|array|null $file
    ): ApprovalHistory {
        // Create approval history record
        $history = ApprovalHistory::create([
            'approval_id' => $approvalId,
            'flow_step_id' => $flowStepId,
            'user_id' => $userId,
            'title' => $title,
            'flag' => $flag,
            'notes' => $notes,
            'file' => null, // Will be set by media library
            'date_time' => time(),
        ]);

        // Handle file upload to media library if file exists
        if ($file) {
            if (is_array($file)) {
                // Multiple files
                foreach ($file as $uploadedFile) {
                    if ($uploadedFile instanceof UploadedFile) {
                        $history->addMedia($uploadedFile)
                            ->toMediaCollection('attachments');
                    }
                }
            } elseif ($file instanceof UploadedFile) {
                // Single file
                $history->addMedia($file)
                    ->toMediaCollection('attachments');
            }
        }

        return $history->fresh(); // Reload to include media relations
    }

    /**
     * Get all histories for an approval.
     */
    public function getAllByApprovalId(int $approvalId): array
    {
        $histories = ApprovalHistory::with(['user', 'flowStep'])
            ->where('approval_id', $approvalId)
            ->orderBy('date_time', 'desc')
            ->get();

        return $histories->map(function ($history) {
            // Get the last media item from attachments collection
            $lastMedia = $history->getMedia('attachments')->last();
            $mediaUrl = $lastMedia ? $lastMedia->getUrl() : null;

            return [
                'id' => $history->id,
                'hashed_id' => $history->hashed_id,
                'approval_id' => $history->approval_id,
                'flow_step_id' => $history->flow_step_id,
                'flow_step_order' => $history->flowStep?->order,
                'flow_step_flow_id' => $history->flowStep?->flow_id,
                'flow_step_name' => $history->flowStep?->name,
                'flow_step_condition' => $history->flowStep?->condition,
                'user_id' => $history->user_id,
                'user_email' => $history->user?->email,
                'user_name' => $history->user?->name,
                'title' => $history->title,
                'flag' => $history->flag,
                'notes' => $history->notes,
                'file' => $history->file,
                'date_time' => $history->date_time,
                'media_url' => $mediaUrl,
            ];
        })->toArray();
    }
}
