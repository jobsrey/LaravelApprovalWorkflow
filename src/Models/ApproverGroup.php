<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApproverGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_approver_groups';

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
        'name',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'company_id' => 'integer',
    ];

    /**
     * Get the group users for the approver group.
     */
    public function groupUsers(): HasMany
    {
        return $this->hasMany(ApproverGroupUser::class, 'approver_group_id');
    }

    /**
     * Get the users that belong to the approver group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('approval-workflow.user_model', \App\Models\User::class),
            'wf_approver_group_users',
            'approver_group_id',
            'user_id'
        );
    }
}
