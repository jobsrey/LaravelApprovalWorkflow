<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flow extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_flows';

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
        'type',
        'company_id',
        'is_active',
        'label',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'company_id' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * Get the steps for the flow.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(FlowStep::class, 'flow_id')->orderBy('order');
    }

    /**
     * Get the approvals for the flow.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'flow_id');
    }

    /**
     * Scope a query to only include active flows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope a query to only include flows of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include flows for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
