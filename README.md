# Laravel Approval Workflow

A flexible and powerful approval workflow system for Laravel applications.

## Installation

### Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher
- Spatie Media Library v11.0 or higher

### Step 1: Install the Package

You can install the package via composer:

```bash
composer require setkita/laravel-approval-workflow
```

### Step 2: Publish and Run Migrations

Publish the migration files and run migrations to create the necessary database tables:

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider" --tag="migrations"
php artisan migrate
```

If you want to customize the configuration file, you can publish it as well:

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider" --tag="config"
```

## Basic Usage

### Setting Up Approval Flows

You can set up approval flows using migrations or seeders:

```php
use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepUser;

// Create a flow
$flow = Flow::create([
    'company_id' => 1, // Optional, for multi-company setup
    'type' => 'purchase-request', // Unique identifier for this flow type
    'name' => 'Purchase Request Approval Flow',
    'is_active' => true,
]);

// Add steps to the flow
$step1 = FlowStep::create([
    'flow_id' => $flow->id,
    'order' => 1,
    'name' => 'Department Manager Approval',
    'condition' => 'amount > 100000', // Optional condition
]);

// Assign approvers to the step
FlowStepUser::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP', // Can be USER, SYSTEM_GROUP, or USER_GROUP
    'user_group_id' => 'department-manager',
]);
```

### Starting an Approval Process

To start an approval process:

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

// IMPORTANT: Parameters must be an array, not an object
$result = ApprovalWorkflow::start(
    'purchase-request', // Flow type
    auth()->id(),       // User ID who initiates the approval
    [                   // Parameters as an array (not an object)
        'departmentId' => $request->department_id,
        'amount' => $request->amount,
        'description' => $request->description,
    ]
);
```

### Common Error: stdClass Parameters

If you encounter this error:
```
AsetKita\LaravelApprovalWorkflow\Services\ApprovalService::start(): Argument #3 ($parameters) must be of type ?array, stdClass given
```

Make sure you're passing an array for the parameters, not an object. For example:

```php
// INCORRECT - This will cause the error
$parameters = (object)[
    'departmentId' => $request->department_id,
    'amount' => $request->amount
];
ApprovalWorkflow::start('purchase-request', auth()->id(), $parameters);

// CORRECT - Use an array instead
$parameters = [
    'departmentId' => $request->department_id,
    'amount' => $request->amount
];
ApprovalWorkflow::start('purchase-request', auth()->id(), $parameters);

// ALSO CORRECT - Direct array syntax
ApprovalWorkflow::start('purchase-request', auth()->id(), [
    'departmentId' => $request->department_id,
    'amount' => $request->amount
]);
```

If you're receiving JSON data that's automatically converted to an stdClass object, convert it to an array first:

```php
// Convert stdClass to array
$parameters = json_decode(json_encode($objectParameters), true);
ApprovalWorkflow::start('purchase-request', auth()->id(), $parameters);
```

### Approving a Request

```php
$result = ApprovalWorkflow::approve(
    $approvalId,
    auth()->id(),
    'Approved with conditions',  // Optional notes
    $request->file('attachment') // Optional file attachment
);
```

### Rejecting a Request

```php
$result = ApprovalWorkflow::reject(
    $approvalId,
    auth()->id(),
    'Budget exceeded',           // Rejection notes
    $request->file('attachment') // Optional file attachment
);
```

### Getting Approval Information

```php
// Get approval path (steps)
$path = ApprovalWorkflow::getApprovalPath($approvalId);

// Get approval history
$histories = ApprovalWorkflow::getApprovalHistories($approvalId);
```

## Extending User Model

Add relationships to your User model:

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

class User extends Authenticatable
{
    // ... existing code
    
    /**
     * Approvals owned by this user
     */
    public function ownedApprovals()
    {
        return $this->hasMany(Approval::class, 'user_id');
    }

    /**
     * Approvals where this user is an active approver
     */
    public function pendingApprovals()
    {
        return $this->belongsToMany(Approval::class, 'wf_approval_active_users', 'user_id', 'approval_id');
    }

    /**
     * Check if user can approve specific approval
     */
    public function canApprove($approvalId)
    {
        return ApprovalActiveUser::where('approval_id', $approvalId)
            ->where('user_id', $this->id)
            ->exists();
    }
}
```

## Advanced Usage

### Conditional Steps

You can define conditions for approval steps using the Symfony Expression Language:

```php
$step = FlowStep::create([
    'flow_id' => $flow->id,
    'order' => 1,
    'name' => 'Finance Director Approval',
    'condition' => 'amount > 1000000 and departmentId == 5',
]);
```

### Dynamic Approvers

You can implement system groups to dynamically determine approvers based on your business logic:

```php
// In your service provider or middleware
app()->bind('approval.resolver.department-manager', function ($app, $parameters) {
    $departmentId = $parameters['departmentId'] ?? null;
    if (!$departmentId) return [];
    
    // Get the manager of the department
    $manager = Department::find($departmentId)->manager;
    return [$manager->id];
});
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
