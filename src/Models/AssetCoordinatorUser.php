<?php

namespace AsetKita\LaravelApprovalWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCoordinatorUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wf_asset_coordinator_users';

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
        'asset_category_id',
        'user_id',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'asset_category_id' => 'integer',
        'user_id' => 'integer',
        'company_id' => 'integer',
    ];

    /**
     * Get the user that owns the asset coordinator user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-workflow.user_model', \App\Models\User::class), 'user_id');
    }
}
