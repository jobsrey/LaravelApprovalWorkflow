<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowStep extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_flow_steps';

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
        'order',
        'flow_id',
        'name',
        'condition',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'flow_id' => 'integer',
    ];

    /**
     * Get the flow that owns the step.
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    /**
     * Get the approvers for the step.
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(FlowStepApprover::class, 'flow_step_id');
    }

    /**
     * Get the approval histories for the step.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'flow_step_id');
    }
}
