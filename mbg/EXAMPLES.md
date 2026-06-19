# Code Examples - Laravel Approval Workflow

Practical copy-paste examples for common scenarios.

## Example 1: Purchase Request System

### Database Seeder

```php
use AsetKita\LaravelApprovalWorkflow\Models\{Flow, FlowStep, FlowStepApprover};

// Create Purchase Request Flow
$flow = Flow::create([
    'type' => 'PR',
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Request',
]);

// Step 1: Manager
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
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
    'data' => '10',
]);
```

### Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class PurchaseRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->start('PR', auth()->id(), [
                'departmentId' => auth()->user()->department_id,
                'amount' => $validated['amount'],
                'items' => $validated['items'],
            ]);

            // Save to your PR table
            PurchaseRequest::create([
                'user_id' => auth()->id(),
                'approval_id' => $result['id'],
                'items' => $validated['items'],
                'amount' => $validated['amount'],
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'approval_id' => $result['id'],
                'current_approvers' => $result['stakeholders']['currentApprovers'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function approve(Request $request, $approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->approve(
                $approvalId,
                auth()->id(),
                $request->input('notes')
            );

            // Update PR status if completed
            if ($result['status'] === 'APPROVED') {
                PurchaseRequest::where('approval_id', $approvalId)
                    ->update(['status' => 'approved']);
            }

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, $approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->reject(
                $approvalId,
                auth()->id(),
                $request->input('notes')
            );

            PurchaseRequest::where('approval_id', $approvalId)
                ->update(['status' => 'rejected']);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function history($approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        $histories = $handler->getApprovalHistories($approvalId);

        // The histories now include media_url field automatically
        // media_url contains the URL of the last uploaded media file
        return response()->json(['histories' => $histories]);
    }
}
```

### Routes

```php
Route::middleware(['auth'])->group(function () {
    Route::post('purchase-requests', [PurchaseRequestController::class, 'store']);
    Route::post('purchase-requests/{approvalId}/approve', [PurchaseRequestController::class, 'approve']);
    Route::post('purchase-requests/{approvalId}/reject', [PurchaseRequestController::class, 'reject']);
    Route::get('purchase-requests/{approvalId}/history', [PurchaseRequestController::class, 'history']);
});
```

## Example 2: Leave Request with Livewire

### Livewire Component

```php
namespace App\Http\Livewire;

use Livewire\Component;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use Carbon\Carbon;

class LeaveRequestForm extends Component
{
    public $startDate;
    public $endDate;
    public $reason;
    public $days;

    protected $rules = [
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate',
        'reason' => 'required|string|min:10',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        if ($this->startDate && $this->endDate) {
            $this->days = Carbon::parse($this->endDate)
                ->diffInDays($this->startDate) + 1;
        }
    }

    public function submit()
    {
        $this->validate();

        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->start('LEAVE', auth()->id(), [
                'departmentId' => auth()->user()->department_id,
                'days' => $this->days,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'reason' => $this->reason,
            ]);

            LeaveRequest::create([
                'user_id' => auth()->id(),
                'approval_id' => $result['id'],
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'days' => $this->days,
                'reason' => $this->reason,
                'status' => 'pending',
            ]);

            session()->flash('message', 'Leave request submitted successfully!');
            $this->reset();
            $this->emit('leaveRequestCreated');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.leave-request-form');
    }
}
```

### Approval Card Component

```php
namespace App\Http\Livewire;

use Livewire\Component;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

class ApprovalCard extends Component
{
    public $approvalId;
    public $approval;
    public $histories;
    public $path;
    public $notes = '';
    public $canApprove = false;

    public function mount($approvalId)
    {
        $this->approvalId = $approvalId;
        $this->loadApproval();
    }

    public function loadApproval()
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        
        $this->approval = Approval::with(['flow', 'owner', 'activeUsers'])->find($this->approvalId);
        $this->histories = $handler->getApprovalHistories($this->approvalId);
        $this->path = $handler->getApprovalPath($this->approvalId);
        
        $this->canApprove = $this->approval->activeUsers()
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function approve()
    {
        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            $handler->approve($this->approvalId, auth()->id(), $this->notes);
            
            $this->notes = '';
            $this->loadApproval();
            $this->emit('approvalUpdated');
            session()->flash('message', 'Approved successfully!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject()
    {
        $this->validate(['notes' => 'required|string|min:5']);

        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            $handler->reject($this->approvalId, auth()->id(), $this->notes);
            
            $this->notes = '';
            $this->loadApproval();
            $this->emit('approvalUpdated');
            session()->flash('message', 'Rejected successfully!');
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

## Example 3: API Controller

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

class ApprovalController extends Controller
{
    protected $handler;

    public function __construct()
    {
        $this->handler = new ApprovalHandler(auth()->user()->company_id ?? 1);
    }

    /**
     * Get my pending approvals
     */
    public function myPending()
    {
        $approvals = Approval::onProgress()
            ->whereHas('activeUsers', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['flow', 'owner'])
            ->get();

        return response()->json(['data' => $approvals]);
    }

    /**
     * Get my created approvals
     */
    public function myCreated()
    {
        $approvals = Approval::where('user_id', auth()->id())
            ->with(['flow', 'currentStep'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['data' => $approvals]);
    }

    /**
     * Get approval detail
     */
    public function show($id)
    {
        $approval = Approval::with(['flow', 'owner', 'activeUsers', 'currentStep'])
            ->findOrFail($id);

        $histories = $this->handler->getApprovalHistories($id);
        $path = $this->handler->getApprovalPath($id);

        $canApprove = $approval->activeUsers()
            ->where('user_id', auth()->id())
            ->exists();

        return response()->json([
            'approval' => $approval,
            'histories' => $histories,
            'path' => $path,
            'can_approve' => $canApprove,
        ]);
    }

    /**
     * Approve
     */
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->handler->approve(
                $id,
                auth()->id(),
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Approved successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:5',
        ]);

        try {
            $result = $this->handler->reject(
                $id,
                auth()->id(),
                $validated['notes']
            );

            return response()->json([
                'success' => true,
                'message' => 'Rejected successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset/Resubmit
     */
    public function reset(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'parameters' => 'nullable|array',
        ]);

        try {
            $result = $this->handler->reset(
                $id,
                auth()->id(),
                $validated['notes'] ?? null,
                null,
                $validated['parameters'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Resubmitted successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### API Routes

```php
Route::middleware(['auth:sanctum'])->prefix('api/approvals')->group(function () {
    Route::get('my-pending', [ApprovalController::class, 'myPending']);
    Route::get('my-created', [ApprovalController::class, 'myCreated']);
    Route::get('{id}', [ApprovalController::class, 'show']);
    Route::post('{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('{id}/reset', [ApprovalController::class, 'reset']);
});
```

## Example 4: Using Facade

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

// Start approval
$result = ApprovalWorkflow::start('PR', auth()->id(), [
    'departmentId' => 10,
    'amount' => 5000,
]);

// Approve
$result = ApprovalWorkflow::approve($approvalId, auth()->id(), 'Approved');

// Reject
$result = ApprovalWorkflow::reject($approvalId, auth()->id(), 'Rejected');

// Get histories
$histories = ApprovalWorkflow::getApprovalHistories($approvalId);

// Get path
$path = ApprovalWorkflow::getApprovalPath($approvalId);
```

## Example 5: Custom Query

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

// Get all on-progress approvals for my department
$approvals = Approval::onProgress()
    ->whereHas('owner', function($q) {
        $q->where('department_id', auth()->user()->department_id);
    })
    ->with(['flow', 'owner', 'activeUsers'])
    ->get();

// Get completed approvals this month
$approvals = Approval::approved()
    ->whereHas('histories', function($q) {
        $q->where('flag', 'done')
          ->where('date_time', '>=', now()->startOfMonth()->timestamp);
    })
    ->get();

// Get rejected approvals by specific user
$approvals = Approval::rejected()
    ->whereHas('histories', function($q) use ($userId) {
        $q->where('flag', 'rejected')
          ->where('user_id', $userId);
    })
    ->get();
```

## Example 6: Add File Attachment

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

// Get history record
$history = ApprovalHistory::find($historyId);

// Add single file
$history->addMedia($request->file('attachment'))
    ->toMediaCollection('attachments');

// Add multiple files
foreach ($request->file('attachments') as $file) {
    $history->addMedia($file)
        ->toMediaCollection('attachments');
}

// Get attachments
$attachments = $history->getMedia('attachments');

// Get URLs
foreach ($attachments as $media) {
    echo $media->getUrl();
}
```

## Example 7: Department Setup

```php
use AsetKita\LaravelApprovalWorkflow\Models\DepartmentUser;

// Add users to IT Department
$users = [
    ['user_id' => 10, 'job_level' => 'STAFF'],
    ['user_id' => 11, 'job_level' => 'STAFF'],
    ['user_id' => 12, 'job_level' => 'MANAGER'],
    ['user_id' => 13, 'job_level' => 'HEAD'],
];

foreach ($users as $user) {
    DepartmentUser::create([
        'department_id' => 1, // IT Department
        'user_id' => $user['user_id'],
        'job_level' => $user['job_level'],
        'company_id' => 1,
    ]);
}
```

## Example 8: Exception Handling

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler(1);

try {
    $result = $handler->approve($approvalId, $userId, $notes);
    
    // Success
    return response()->json(['success' => true, 'result' => $result]);
    
} catch (\Exception $e) {
    // Handle specific exceptions
    switch ($e->getMessage()) {
        case ApprovalHandler::EXC_USER_NOT_FOUND:
            return response()->json(['error' => 'User not found'], 404);
            
        case ApprovalHandler::EXC_PERMISSION_DENIED:
            return response()->json(['error' => 'Permission denied'], 403);
            
        case ApprovalHandler::EXC_APPROVAL_NOT_RUNNING:
            return response()->json(['error' => 'Approval is not running'], 400);
            
        default:
            return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

## Example 9: Notification

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalPendingNotification extends Notification
{
    protected $approval;

    public function __construct($approval)
    {
        $this->approval = $approval;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Pending Approval Required')
            ->line('You have a pending approval to review.')
            ->action('Review Approval', url('/approvals/'.$this->approval->id))
            ->line('Thank you!');
    }

    public function toArray($notifiable)
    {
        return [
            'approval_id' => $this->approval->id,
            'type' => $this->approval->flow->type,
            'owner' => $this->approval->owner->name,
        ];
    }
}

// Usage in controller
foreach ($result['stakeholders']['currentApprovers'] as $approver) {
    $user = User::find($approver['user_id']);
    $user->notify(new ApprovalPendingNotification($approval));
}
```

These examples cover most common use cases! Copy and adapt them for your needs.
