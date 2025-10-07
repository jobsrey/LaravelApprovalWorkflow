<?php

namespace AsetKita\LaravelApprovalWorkflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array start(string $flowType, int $userId, ?array $parameters = null)
 * @method static array approve(int $approvalId, int $userId, ?string $notes = null, ?string $file = null)
 * @method static array reject(int $approvalId, int $userId, ?string $notes = null, ?string $file = null)
 * @method static array rejectBySystem(int $approvalId, int $relatedUserId, ?string $notes = null, ?string $file = null)
 * @method static array reset(int $approvalId, int $userId, ?string $notes = null, ?string $file = null, ?array $parameters = null)
 * @method static void rebuildApprovers()
 * @method static mixed getNextSteps(int $approvalId)
 * @method static array getApprovalPath(int $approvalId)
 * @method static array getApprovalHistories(int $approvalId)
 *
 * @see \AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler
 */
class ApprovalWorkflow extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'approval-workflow';
    }
}
