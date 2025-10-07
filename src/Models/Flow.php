<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flow extends Model
{
    protected $table = 'wf_flows';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(FlowStep::class, 'flow_id')->orderBy('order');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'flow_id');
    }
}
