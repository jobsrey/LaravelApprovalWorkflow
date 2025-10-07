# Laravel Approval Workflow

A flexible and powerful approval workflow system for Laravel 12+ applications with Spatie Media Library support.

## Features

- ✅ Multi-step approval workflows
- ✅ Dynamic approver assignment (User, Group, System Group)
- ✅ Conditional workflow steps using Expression Language
- ✅ Department-based approvals (Staff, Manager, Head)
- ✅ Asset coordinator approvals
- ✅ Complete approval history tracking
- ✅ Spatie Media Library integration for file attachments
- ✅ System-level rejections
- ✅ Approval reset/resubmission
- ✅ Multi-company support
- ✅ Laravel 12 compatible

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0 or higher
- MySQL 5.7+ or PostgreSQL 9.6+

## Installation

### 1. Install via Composer

```bash
composer require asetkita/laravel-approval-workflow
```

### 2. Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
```

Or publish them separately:

```bash
# Publish config file
php artisan vendor:publish --tag=approval-workflow-config

# Publish migrations
php artisan vendor:publish --tag=approval-workflow-migrations
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Install Spatie Media Library (if not already installed)

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

## Configuration

Edit `config/approval-workflow.php`:

```php
return [
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', \App\Models\User::class),
    'media_disk' => env('APPROVAL_WORKFLOW_MEDIA_DISK', 'public'),
    'notifications_enabled' => env('APPROVAL_WORKFLOW_NOTIFICATIONS', false),
    'notification_channels' => ['mail', 'database'],
];
```

## Database Tables

The package creates the following tables:

| Table Name | Description |
|------------|-------------|
| `wf_department_users` | Maps users to departments with job levels |
| `wf_asset_coordinator_users` | Maps users to asset categories |
| `wf_approver_groups` | Custom approver groups |
| `wf_approver_group_users` | Users in approver groups |
| `wf_flows` | Approval flow definitions |
| `wf_flow_steps` | Steps in each flow |
| `wf_flow_step_approvers` | Approvers for each step |
| `wf_approvals` | Active approval instances |
| `wf_approval_active_users` | Current approvers for active approvals |
| `wf_approval_histories` | Complete history of approval actions |

## Usage

### Basic Usage

#### 1. Create an Approval Flow

First, create a flow in the `wf_flows` table:

```php
use AsetKita\LaravelApprovalWorkflow\Models\Flow;

$flow = Flow::create([
    'type' => 'PR', // Purchase Request
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Request Approval',
]);
```

#### 2. Add Flow Steps

```php
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;

// Step 1: Department Manager
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Department Manager Approval',
    'condition' => null, // No condition
]);

// Step 2: Department Head (only if amount > 10000)
$step2 = FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'Department Head Approval',
    'condition' => 'amount > 10000', // Expression Language
]);

// Step 3: Finance Manager
$step3 = FlowStep::create([
    'order' => 3,
    'flow_id' => $flow->id,
    'name' => 'Finance Approval',
    'condition' => null,
]);
```

#### 3. Configure Approvers for Each Step

```php
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepApprover;

// Step 1: Department Manager (System Group)
FlowStepApprover::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);

// Step 2: Department Head (System Group)
FlowStepApprover::create([
    'flow_step_id' => $step2->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-head',
]);

// Step 3: Specific User
FlowStepApprover::create([
    'flow_step_id' => $step3->id,
    'type' => 'USER',
    'data' => '5', // User ID
]);
```

### Starting an Approval

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler($companyId = 1);

$result = $handler->start(
    flowType: 'PR',
    userId: auth()->id(),
    parameters: [
        'departmentId' => 10,
        'amount' => 15000,
        'description' => 'Office supplies',
    ]
);

$approvalId = $result['id'];
```

### Approving a Step

```php
$result = $handler->approve(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Approved by manager',
    file: null
);
```

### Rejecting a Step

```php
$result = $handler->reject(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Budget exceeded',
    file: null
);
```

### Resetting a Rejected Approval

```php
$result = $handler->reset(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Resubmitting with corrected amount',
    file: null,
    parameters: ['amount' => 8000] // Updated parameters
);
```

### System Rejection (Admin Override)

```php
$result = $handler->rejectBySystem(
    approvalId: $approvalId,
    relatedUserId: auth()->id(),
    notes: 'System auto-rejected due to policy violation',
    file: null
);
```

### Getting Approval History

```php
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo "{$history['title']} by {$history['user_name']} at {$history['date_time']}\n";
}
```

### Getting Approval Path

```php
$path = $handler->getApprovalPath($approvalId);

foreach ($path as $step) {
    echo "Step: {$step['name']} - Status: {$step['type']}\n";
}
```

### Using Facade

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

$result = ApprovalWorkflow::start('PR', auth()->id(), [
    'departmentId' => 10,
    'amount' => 5000,
]);
```

## Approver Types

### 1. USER
Direct user assignment by user ID.

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'USER',
    'data' => '123', // User ID
]);
```

### 2. GROUP
Custom approver group (all users in the group).

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'GROUP',
    'data' => '5', // Approver Group ID
]);
```

### 3. SYSTEM_GROUP
Dynamic system groups based on parameters.

#### Available System Groups:

- `department-manager` - Requires `departmentId` parameter
- `department-head` - Requires `departmentId` parameter
- `department-staff` - Requires `departmentId` parameter
- `asset-coordinator` - Requires `assetCategoryId` parameter
- `origin-asset-user` - Requires `originAssetUserId` parameter
- `destination-asset-user` - Requires `destinationAssetUserId` parameter

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);
```

## Parameters

### Standard Parameters

| Parameter | Description |
|-----------|-------------|
| `departmentId` | Required for department-based approvers |
| `overrideManagerUserId` | Override department manager approver |
| `overrideHeadUserId` | Override department head approver |
| `assetCategoryId` | Required for asset-coordinator |
| `originAssetUserId` | Required for origin-asset-user |
| `destinationAssetUserId` | Required for destination-asset-user |

### Custom Parameters

You can pass any custom parameters for conditional steps:

```php
$handler->start('PR', auth()->id(), [
    'departmentId' => 10,
    'amount' => 15000,
    'category' => 'IT',
    'priority' => 'high',
    'requestType' => 'urgent',
]);
```

Use them in conditions:

```php
// Only if amount > 5000 AND priority is high
'condition' => 'amount > 5000 and priority == "high"'

// Only if category is IT
'condition' => 'category == "IT"'

// Complex condition
'condition' => '(amount > 10000 or priority == "urgent") and category in ["IT", "Finance"]'
```

## Conditional Steps (Expression Language)

The package uses Symfony Expression Language for conditional steps.

### Examples:

```php
// Simple comparison
'amount > 10000'

// Multiple conditions
'amount > 10000 and category == "IT"'

// Using OR
'priority == "high" or amount > 50000'

// Using IN
'category in ["IT", "Finance", "HR"]'

// Complex expression
'(amount > 10000 or priority == "urgent") and status == "pending"'
```

## Spatie Media Library Integration

The `ApprovalHistory` model supports file attachments via Spatie Media Library with **automatic upload** feature.

### 🆕 Automatic File Upload (New!)

Files are automatically uploaded to Media Library when you approve/reject:

```php
// Approve with single file - file automatically saved!
$handler->approve(
    $approvalId, 
    $userId, 
    'Approved', 
    $request->file('attachment')
);

// Approve with multiple files - all files automatically saved!
$handler->approve(
    $approvalId, 
    $userId, 
    'Approved', 
    $request->file('attachments') // Array of files
);

// Same for reject and reset
$handler->reject($approvalId, $userId, 'Rejected', $request->file('document'));
$handler->reset($approvalId, $userId, 'Reset', $request->file('files'));
```

**No need to find the history record first!** Just pass the `UploadedFile` instance directly.

See [FILE_UPLOAD_GUIDE.md](FILE_UPLOAD_GUIDE.md) for complete examples.

### Getting Attachments

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find($historyId);

// Get all attachments
$attachments = $history->getMedia('attachments');

// Get first attachment URL
$url = $history->getFirstMediaUrl('attachments');

// Get all attachment URLs
foreach ($history->getMedia('attachments') as $media) {
    echo $media->getUrl();
    echo $media->file_name;
    echo $media->size;
}
```

## History Flags

The package tracks different types of history events:

| Flag | Constant | Description |
|------|----------|-------------|
| `created` | `HFLAG_CREATED` | Approval was created |
| `reset` | `HFLAG_RESET` | Approval was reset/resubmitted |
| `approved` | `HFLAG_APPROVED` | Step was approved |
| `rejected` | `HFLAG_REJECTED` | Step was rejected |
| `system_rejected` | `HFLAG_SYSTEM_REJECTED` | Rejected by system |
| `done` | `HFLAG_DONE` | Approval completed |
| `skip` | `HFLAG_SKIP` | Step was skipped (no approvers) |

## Advanced Features

### Rebuilding Approvers

Useful when department assignments or groups change:

```php
$handler->rebuildApprovers();
```

### Getting Next Steps

```php
$nextStep = $handler->getNextSteps($approvalId);
```

### Querying Approvals

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

// Get all on-progress approvals
$approvals = Approval::onProgress()->get();

// Get approved approvals for company
$approved = Approval::approved()->forCompany(1)->get();

// Get all approvals for a specific flow
$prApprovals = Approval::whereHas('flow', function($q) {
    $q->where('type', 'PR');
})->get();
```

## Exception Handling

The package throws the following exceptions:

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

try {
    $handler->approve($approvalId, $userId);
} catch (\Exception $e) {
    switch ($e->getMessage()) {
        case ApprovalHandler::EXC_USER_NOT_FOUND:
            // Handle user not found
            break;
        case ApprovalHandler::EXC_FLOW_NOT_FOUND:
            // Handle flow not found
            break;
        case ApprovalHandler::EXC_PERMISSION_DENIED:
            // Handle permission denied
            break;
        case ApprovalHandler::EXC_APPROVAL_NOT_RUNNING:
            // Handle approval not running
            break;
        case ApprovalHandler::EXC_APPROVAL_NOT_REJECTED:
            // Handle approval not rejected
            break;
    }
}
```

## Complete Example

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepApprover;

// 1. Create flow
$flow = Flow::create([
    'type' => 'LEAVE_REQUEST',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Leave Request Approval',
]);

// 2. Create steps
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
]);

$step2 = FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'HR Approval',
    'condition' => 'days > 3', // Only if more than 3 days
]);

// 3. Configure approvers
FlowStepApprover::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);

FlowStepApprover::create([
    'flow_step_id' => $step2->id,
    'type' => 'USER',
    'data' => '10', // HR Manager User ID
]);

// 4. Start approval
$handler = new ApprovalHandler(1);

$result = $handler->start('LEAVE_REQUEST', auth()->id(), [
    'departmentId' => auth()->user()->department_id,
    'days' => 5,
    'startDate' => '2024-12-01',
    'endDate' => '2024-12-05',
    'reason' => 'Family vacation',
]);

$approvalId = $result['id'];

// 5. Manager approves
$handler->approve($approvalId, $managerId, 'Approved');

// 6. HR approves (automatic if condition met)
$handler->approve($approvalId, $hrManagerId, 'Approved by HR');

// 7. Check history
$histories = $handler->getApprovalHistories($approvalId);
```

## Testing

```bash
composer test
```

## License

MIT License

## Author

**Rey**  
Email: kireniusdena@gmail.com

## Support

For issues and feature requests, please use the GitHub issue tracker.
