<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ApprovalHistory extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'wf_approval_histories';

    protected $fillable = [
        'approval_id',
        'flow_step_id',
        'user_id',
        'title',
        'flag',
        'notes',
        'file',
        'date_time'
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    // History flags constants
    public const FLAG_CREATED = 'created';
    public const FLAG_RESET = 'reset';
    public const FLAG_APPROVED = 'approved';
    public const FLAG_REJECTED = 'rejected';
    public const FLAG_SYSTEM_REJECTED = 'system_rejected';
    public const FLAG_DONE = 'done';
    public const FLAG_SKIP = 'skip';

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }

    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Register media collections for file attachments
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain'
            ]);
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('files')
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->performOnCollections('files')
            ->nonQueued();
    }

    /**
     * Get all attached files
     */
    public function getAttachedFiles()
    {
        return $this->getMedia('files');
    }

    /**
     * Add file attachment
     */
    public function addFile($file, $name = null)
    {
        $mediaItem = $this->addMediaFromRequest('file')
            ->toMediaCollection('files');

        if ($name) {
            $mediaItem->update(['name' => $name]);
        }

        return $mediaItem;
    }

    /**
     * Add file from path
     */
    public function addFileFromPath($path, $name = null)
    {
        $mediaItem = $this->addMedia($path)
            ->toMediaCollection('files');

        if ($name) {
            $mediaItem->update(['name' => $name]);
        }

        return $mediaItem;
    }

    /**
     * Get file URLs
     */
    public function getFileUrls()
    {
        return $this->getMedia('files')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                'preview_url' => $media->hasGeneratedConversion('preview') ? $media->getUrl('preview') : null,
            ];
        });
    }

    /**
     * Scope for filtering by flag
     */
    public function scopeByFlag($query, $flag)
    {
        return $query->where('flag', $flag);
    }

    /**
     * Scope for approval actions (approved/rejected)
     */
    public function scopeApprovalActions($query)
    {
        return $query->whereIn('flag', [
            self::FLAG_APPROVED,
            self::FLAG_REJECTED,
            self::FLAG_SYSTEM_REJECTED
        ]);
    }

    /**
     * Scope for reset actions
     */
    public function scopeResetActions($query)
    {
        return $query->whereIn('flag', [
            self::FLAG_CREATED,
            self::FLAG_RESET
        ]);
    }
}
