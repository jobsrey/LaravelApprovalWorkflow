<?php

namespace AsetKita\LaravelApprovalWorkflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array start(string $flowType, int $userId, ?array $parameters = [])
 * @method static array approve(int $approvalId, int $userId, ?string $notes = null, $file = null)
 * @method static array reject(int $approvalId, int $userId, ?string $notes = null, $file = null)
 * @method static array reset(int $approvalId, int $userId, ?string $notes = null, $file = null, ?array $parameters = null)
 * @method static array rejectBySystem(int $approvalId, int $relatedUserId, ?string $notes = null, $file = null)
 * @method static array getApprovalPath(int $approvalId)
 * @method static array getApprovalHistories(int $approvalId)
 * @method static void rebuildApprovers()
 * @method static \AsetKita\LaravelApprovalWorkflow\Services\ApprovalService setCompanyId(int $companyId)
 * @method static int getCompanyId()
 */
class ApprovalWorkflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'approval-workflow';
    }
}
