# Quick Start Guide

Get started with Laravel Approval Workflow in 5 minutes!

## Installation

```bash
composer require asetkita/laravel-approval-workflow
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
php artisan migrate
```

## Create Your First Flow

```php
use AsetKita\LaravelApprovalWorkflow\Models\{Flow, FlowStep, FlowStepApprover};

// 1. Create flow
$flow = Flow::create([
    'type' => 'PR',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Request',
]);

// 2. Add steps
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
]);

// 3. Configure approver
FlowStepApprover::create([
    'flow_step_id' => $step1->id,
    'type' => 'USER',
    'data' => '5', // Manager's user ID
]);
```

## Start an Approval

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler(1);

$result = $handler->start('PR', auth()->id(), [
    'amount' => 5000,
    'description' => 'Office supplies',
]);

$approvalId = $result['id'];
```

## Approve/Reject

```php
// Approve
$handler->approve($approvalId, auth()->id(), 'Approved!');

// Reject
$handler->reject($approvalId, auth()->id(), 'Not approved');
```

## View History

```php
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo "{$history['title']} by {$history['user_name']}\n";
    
    // Display attachment URL if exists
    if ($history['media_url']) {
        echo "Attachment: {$history['media_url']}\n";
    }
}
```

That's it! 🎉

For more details, see [README.md](README.md) and [USAGE.md](USAGE.md)
