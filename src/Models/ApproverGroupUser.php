<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApproverGroupUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_approver_group_users';

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
        'approver_group_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approver_group_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the approver group that owns the group user.
     */
    public function approverGroup(): BelongsTo
    {
        return $this->belongsTo(ApproverGroup::class, 'approver_group_id');
    }

    /**
     * Get the user that owns the group user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-workflow.user_model', \App\Models\User::class), 'user_id');
    }
}
