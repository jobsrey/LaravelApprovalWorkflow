<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowStep extends Model
{
    protected $table = 'wf_flow_steps';

    protected $fillable = [
        'flow_id',
        'order',
        'name',
        'condition'
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(FlowStepUser::class, 'flow_step_id');
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'flow_step_id');
    }
}
