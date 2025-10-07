<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Approval extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_approvals';

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
        'flow_id',
        'status',
        'flow_step_id',
        'user_id',
        'parameters',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'flow_id' => 'integer',
        'flow_step_id' => 'integer',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'parameters' => 'array',
    ];

    /**
     * Get the flow that owns the approval.
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    /**
     * Get the current flow step.
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    /**
     * Get the owner of the approval.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('approval-workflow.user_model', \App\Models\User::class), 'user_id');
    }

    /**
     * Get the histories for the approval.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approval_id')->orderBy('date_time');
    }

    /**
     * Get the active users for the approval.
     */
    public function activeUsersPivot(): HasMany
    {
        return $this->hasMany(ApprovalActiveUser::class, 'approval_id');
    }

    /**
     * Get the active users for the approval.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            config('approval-workflow.user_model', \App\Models\User::class),
            'wf_approval_active_users',
            'approval_id',
            'user_id'
        );
    }

    /**
     * Scope a query to only include approvals with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include on-progress approvals.
     */
    public function scopeOnProgress($query)
    {
        return $query->where('status', 'ON_PROGRESS');
    }

    /**
     * Scope a query to only include approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope a query to only include rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    /**
     * Scope a query to only include approvals for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
