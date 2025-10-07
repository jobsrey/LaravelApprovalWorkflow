<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ApprovalHistory extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_approval_histories';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'approval_id',
        'user_id',
        'flow_step_id',
        'title',
        'flag',
        'notes',
        'file',
        'date_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approval_id' => 'integer',
        'user_id' => 'integer',
        'flow_step_id' => 'integer',
        'date_time' => 'integer',
    ];

    /**
     * History flag constants
     */
    public const HFLAG_CREATED = 'created';
    public const HFLAG_RESET = 'reset';
    public const HFLAG_APPROVED = 'approved';
    public const HFLAG_REJECTED = 'rejected';
    public const HFLAG_SYSTEM_REJECTED = 'system_rejected';
    public const HFLAG_DONE = 'done';
    public const HFLAG_SKIP = 'skip';

    /**
     * Get the approval that owns the history.
     */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    /**
     * Get the user that owns the history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-workflow.user_model', \App\Models\User::class), 'user_id');
    }

    /**
     * Get the flow step that owns the history.
     */
    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('approval-workflow.media_disk', 'public'));
    }

    /**
     * Get all attachments.
     */
    public function getAttachmentsAttribute()
    {
        return $this->getMedia('attachments');
    }

    /**
     * Scope a query to only include histories with a specific flag.
     */
    public function scopeWithFlag($query, string $flag)
    {
        return $query->where('flag', $flag);
    }

    /**
     * Scope a query to only include histories for approval.
     */
    public function scopeForApproval($query, int $approvalId)
    {
        return $query->where('approval_id', $approvalId);
    }
}
