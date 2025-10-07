<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    protected $table = 'wf_approvals';

    protected $fillable = [
        'company_id',
        'flow_id',
        'flow_step_id',
        'user_id',
        'status',
        'parameters'
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approval_id')->orderBy('created_at');
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(ApprovalActiveUser::class, 'approval_id');
    }

    public function currentApprovers()
    {
        return $this->activeUsers()->with('user');
    }

    public function scopeRunning($query, $companyId = null)
    {
        $query->where('status', 'ON_PROGRESS');
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query;
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
