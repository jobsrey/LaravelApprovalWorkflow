<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowStepUser extends Model
{
    protected $table = 'wf_flow_step_users';

    protected $fillable = [
        'flow_step_id',
        'type',
        'user_id',
        'user_group_id'
    ];

    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(FlowStep::class, 'flow_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }
}
