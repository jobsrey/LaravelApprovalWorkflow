# Usage Guide - Laravel Approval Workflow

Complete guide with practical examples for using the approval workflow package.

## Table of Contents

1. [Basic Setup](#basic-setup)
2. [Creating Flows](#creating-flows)
3. [Department Structure](#department-structure)
4. [Approver Groups](#approver-groups)
5. [Starting Approvals](#starting-approvals)
6. [Processing Approvals](#processing-approvals)
7. [Advanced Features](#advanced-features)
8. [Real-World Examples](#real-world-examples)

## Basic Setup

### Step 1: Create a Flow

```php
use AsetKita\LaravelApprovalWorkflow\Models\Flow;

$flow = Flow::create([
    'type' => 'PR',  // Unique identifier
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Request Approval Flow',
]);
```

### Step 2: Add Steps to the Flow

```php
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;

// Step 1: Manager approval
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
    'condition' => null,  // Always execute
]);

// Step 2: Finance approval (conditional)
$step2 = FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'Finance Approval',
    'condition' => 'amount > 5000',  // Only if amount > 5000
]);

// Step 3: Director approval
$step3 = FlowStep::create([
    'order' => 3,
    'flow_id' => $flow->id,
    'name' => 'Director Approval',
    'condition' => 'amount > 10000',  // Only if amount > 10000
]);
```

### Step 3: Configure Approvers

```php
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepApprover;

// Step 1: Department Manager (System Group)
FlowStepApprover::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);

// Step 2: Specific User
FlowStepApprover::create([
    'flow_step_id' => $step2->id,
    'type' => 'USER',
    'data' => '5',  // Finance Manager User ID
]);

// Step 3: Approver Group
FlowStepApprover::create([
    'flow_step_id' => $step3->id,
    'type' => 'GROUP',
    'data' => '1',  // Directors Group ID
]);
```

## Creating Flows

### Example 1: Simple Sequential Approval

```php
// Purchase Order Flow: Manager -> Finance -> Director
$flow = Flow::create([
    'type' => 'PO',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Order',
]);

// All steps execute in order
FlowStep::create(['order' => 1, 'flow_id' => $flow->id, 'name' => 'Manager']);
FlowStep::create(['order' => 2, 'flow_id' => $flow->id, 'name' => 'Finance']);
FlowStep::create(['order' => 3, 'flow_id' => $flow->id, 'name' => 'Director']);
```

### Example 2: Conditional Approval

```php
// Leave Request: Manager -> HR (only if > 3 days)
$flow = Flow::create([
    'type' => 'LEAVE',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Leave Request',
]);

$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
]);

$step2 = FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'HR Approval',
    'condition' => 'days > 3',  // Only for leave > 3 days
]);
```

### Example 3: Complex Conditional Flow

```php
$flow = Flow::create([
    'type' => 'EXPENSE',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Expense Claim',
]);

// Step 1: Manager (always)
FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager',
]);

// Step 2: Finance (if amount > 1000)
FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'Finance',
    'condition' => 'amount > 1000',
]);

// Step 3: CFO (if amount > 10000 OR category is "Travel")
FlowStep::create([
    'order' => 3,
    'flow_id' => $flow->id,
    'name' => 'CFO',
    'condition' => 'amount > 10000 or category == "Travel"',
]);

// Step 4: CEO (if amount > 50000)
FlowStep::create([
    'order' => 4,
    'flow_id' => $flow->id,
    'name' => 'CEO',
    'condition' => 'amount > 50000',
]);
```

## Department Structure

### Set Up Departments

```php
use AsetKita\LaravelApprovalWorkflow\Models\DepartmentUser;

// Add users to departments
DepartmentUser::create([
    'department_id' => 1,  // IT Department
    'user_id' => 10,
    'job_level' => 'STAFF',
    'company_id' => 1,
]);

DepartmentUser::create([
    'department_id' => 1,
    'user_id' => 11,
    'job_level' => 'MANAGER',
    'company_id' => 1,
]);

DepartmentUser::create([
    'department_id' => 1,
    'user_id' => 12,
    'job_level' => 'HEAD',
    'company_id' => 1,
]);
```

### Use Department-Based Approvers

```php
// Department Manager
FlowStepApprover::create([
    'flow_step_id' => $step->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);

// Department Head
FlowStepApprover::create([
    'flow_step_id' => $step->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-head',
]);

// Department Staff
FlowStepApprover::create([
    'flow_step_id' => $step->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-staff',
]);
```

## Approver Groups

### Create Approver Groups

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApproverGroup;
use AsetKita\LaravelApprovalWorkflow\Models\ApproverGroupUser;

// Create group
$group = ApproverGroup::create([
    'name' => 'Directors',
    'company_id' => 1,
]);

// Add users to group
ApproverGroupUser::create([
    'approver_group_id' => $group->id,
    'user_id' => 20,
]);

ApproverGroupUser::create([
    'approver_group_id' => $group->id,
    'user_id' => 21,
]);
```

### Use Groups in Flows

```php
FlowStepApprover::create([
    'flow_step_id' => $step->id,
    'type' => 'GROUP',
    'data' => $group->id,
]);
```

## Starting Approvals

### Basic Approval Start

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler($companyId = 1);

$result = $handler->start(
    flowType: 'PR',
    userId: auth()->id(),
    parameters: [
        'departmentId' => 10,
        'amount' => 5000,
        'description' => 'Office supplies',
    ]
);

$approvalId = $result['id'];
$currentApprovers = $result['stakeholders']['currentApprovers'];
```

### Controller Example

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class PurchaseRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string',
        ]);

        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->start('PR', auth()->id(), [
                'departmentId' => auth()->user()->department_id,
                'amount' => $validated['amount'],
                'description' => $validated['description'],
            ]);

            return response()->json([
                'success' => true,
                'approval_id' => $result['id'],
                'status' => $result['status'],
                'current_approvers' => $result['stakeholders']['currentApprovers'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
```

## Processing Approvals

### Approve Action

```php
$handler = new ApprovalHandler(1);

try {
    $result = $handler->approve(
        approvalId: $approvalId,
        userId: auth()->id(),
        notes: 'Looks good, approved!',
        file: null
    );

    // Check if completed
    if ($result['status'] === 'APPROVED') {
        // All steps completed
        $allSteps = $result['stakeholders']['steps'];
    } else {
        // More steps remaining
        $nextApprovers = $result['stakeholders']['currentApprovers'];
    }
} catch (\Exception $e) {
    // Handle: EXC_PERMISSION_DENIED, EXC_APPROVAL_NOT_RUNNING, etc.
}
```

### Reject Action

```php
$result = $handler->reject(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Budget not available',
    file: null
);

// Status will be 'REJECTED'
```

### Reset/Resubmit

```php
// After rejection, resubmit with updated data
$result = $handler->reset(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Corrected amount',
    file: null,
    parameters: [
        'amount' => 4000,  // Updated amount
        'departmentId' => 10,
    ]
);
```

### Controller Example - Approve

```php
public function approve(Request $request, $approvalId)
{
    $validated = $request->validate([
        'notes' => 'nullable|string',
    ]);

    $handler = new ApprovalHandler(auth()->user()->company_id);

    try {
        $result = $handler->approve(
            approvalId: $approvalId,
            userId: auth()->id(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'next_approvers' => $result['stakeholders']['currentApprovers'] ?? [],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 400);
    }
}
```

## Advanced Features

### Get Approval History

```php
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo $history['title'];  // Action title
    echo $history['user_name'];  // Who performed action
    echo $history['flag'];  // approved, rejected, created, etc.
    echo $history['date_time'];  // Timestamp
}
```

### Get Approval Path

```php
$path = $handler->getApprovalPath($approvalId);

foreach ($path as $step) {
    echo $step['name'];  // Step name
    echo $step['type'];  // current, passed, approved, rejected, incoming
    echo $step['approver_name'] ?? 'N/A';  // Who approved
}
```

### Check User Permission

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

$approval = Approval::find($approvalId);

$canApprove = $approval->activeUsers()
    ->where('user_id', auth()->id())
    ->exists();

if ($canApprove) {
    // Show approve/reject buttons
}
```

### Query Approvals

```php
// Get all pending approvals for current user
$myPendingApprovals = Approval::onProgress()
    ->whereHas('activeUsers', function($q) {
        $q->where('user_id', auth()->id());
    })
    ->get();

// Get all approvals created by user
$myCreatedApprovals = Approval::where('user_id', auth()->id())->get();

// Get all approved PRs
$approvedPRs = Approval::approved()
    ->whereHas('flow', function($q) {
        $q->where('type', 'PR');
    })
    ->get();
```

## Real-World Examples

### Example 1: Purchase Request System

```php
// 1. Create flow in database seeder
class ApprovalFlowSeeder extends Seeder
{
    public function run()
    {
        $flow = Flow::create([
            'type' => 'PR',
            'company_id' => 1,
            'is_active' => 1,
            'label' => 'Purchase Request',
        ]);

        // Step 1: Department Manager
        $step1 = FlowStep::create([
            'order' => 1,
            'flow_id' => $flow->id,
            'name' => 'Department Manager Approval',
        ]);
        FlowStepApprover::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'data' => 'department-manager',
        ]);

        // Step 2: Finance (if > 5000)
        $step2 = FlowStep::create([
            'order' => 2,
            'flow_id' => $flow->id,
            'name' => 'Finance Approval',
            'condition' => 'amount > 5000',
        ]);
        FlowStepApprover::create([
            'flow_step_id' => $step2->id,
            'type' => 'USER',
            'data' => '10',  // Finance Manager
        ]);

        // Step 3: Director (if > 10000)
        $step3 = FlowStep::create([
            'order' => 3,
            'flow_id' => $flow->id,
            'name' => 'Director Approval',
            'condition' => 'amount > 10000',
        ]);
        FlowStepApprover::create([
            'flow_step_id' => $step3->id,
            'type' => 'USER',
            'data' => '20',  // Director
        ]);
    }
}

// 2. Controller
class PurchaseRequestController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $handler = new ApprovalHandler(auth()->user()->company_id);

        $result = $handler->start('PR', auth()->id(), [
            'departmentId' => auth()->user()->department_id,
            'amount' => $validated['amount'],
            'items' => $validated['items'],
            'notes' => $validated['notes'],
        ]);

        // Save PR in your database with approval_id
        PurchaseRequest::create([
            'user_id' => auth()->id(),
            'approval_id' => $result['id'],
            'items' => $validated['items'],
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        return redirect()->route('pr.show', $result['id']);
    }

    public function approve($approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);

        $result = $handler->approve(
            $approvalId,
            auth()->id(),
            request('notes')
        );

        // Update PR status if completed
        if ($result['status'] === 'APPROVED') {
            PurchaseRequest::where('approval_id', $approvalId)
                ->update(['status' => 'approved']);
        }

        return redirect()->back()->with('success', 'Approved successfully');
    }
}
```

### Example 2: Leave Request System

```php
// Controller
class LeaveRequestController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'required|string',
        ]);

        $days = Carbon::parse($validated['end_date'])
            ->diffInDays($validated['start_date']) + 1;

        $handler = new ApprovalHandler(auth()->user()->company_id);

        $result = $handler->start('LEAVE', auth()->id(), [
            'departmentId' => auth()->user()->department_id,
            'days' => $days,
            'startDate' => $validated['start_date'],
            'endDate' => $validated['end_date'],
            'reason' => $validated['reason'],
        ]);

        LeaveRequest::create([
            'user_id' => auth()->id(),
            'approval_id' => $result['id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Leave request submitted');
    }
}
```

### Example 3: Using with Livewire

```php
namespace App\Http\Livewire;

use Livewire\Component;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalCard extends Component
{
    public $approvalId;
    public $approval;
    public $histories;

    public function mount($approvalId)
    {
        $this->approvalId = $approvalId;
        $this->loadApproval();
    }

    public function loadApproval()
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $this->histories = $handler->getApprovalHistories($this->approvalId);
        // Load approval model data
    }

    public function approve($notes)
    {
        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            $handler->approve($this->approvalId, auth()->id(), $notes);
            
            $this->loadApproval();
            $this->emit('approvalUpdated');
            session()->flash('message', 'Approved successfully');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject($notes)
    {
        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            $handler->reject($this->approvalId, auth()->id(), $notes);
            
            $this->loadApproval();
            $this->emit('approvalUpdated');
            session()->flash('message', 'Rejected');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.approval-card');
    }
}
```

## Tips and Best Practices

1. **Always use try-catch** when calling approval methods
2. **Store approval_id** in your entity tables
3. **Update entity status** when approval completes
4. **Use conditions** for flexible workflows
5. **Create groups** for recurring approver sets
6. **Test flows** before activating
7. **Use parameters** to pass entity-specific data
8. **Log approval events** for audit trails
9. **Send notifications** to current approvers
10. **Check permissions** before showing approve buttons

## Next Steps

- Check [README.md](README.md) for complete API reference
- Review [INSTALLATION.md](INSTALLATION.md) for setup details
- Create your own approval flows
- Customize for your business needs
