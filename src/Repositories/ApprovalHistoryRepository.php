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
        return DB::table('wf_approval_histories as wah')
            ->leftJoin('users as u', 'u.id', '=', 'wah.user_id')
            ->leftJoin('wf_flow_steps as wfs', 'wfs.id', '=', 'wah.flow_step_id')
            ->where('wah.approval_id', $approvalId)
            ->select([
                'wah.id',
                'wah.approval_id',
                'wah.flow_step_id',
                'wfs.order as flow_step_order',
                'wfs.flow_id as flow_step_flow_id',
                'wfs.name as flow_step_name',
                'wfs.condition as flow_step_condition',
                'wah.user_id',
                'u.email as user_email',
                'u.name as user_name',
                'wah.title',
                'wah.flag',
                'wah.notes',
                'wah.file',
                'wah.date_time',
            ])
            ->orderBy('wah.date_time', 'asc')
            ->get()
            ->map(fn($history) => (array) $history)
            ->toArray();
    }
}
