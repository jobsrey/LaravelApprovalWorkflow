# Contoh Penggunaan Laravel Approval Workflow

## 1. Setup Dasar

### Controller untuk Purchase Request

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

class PurchaseRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            // Start approval workflow
            $result = ApprovalWorkflow::start('purchase-request', auth()->id(), [
                'departmentId' => $request->department_id,
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase request submitted for approval',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function approve(Request $request, $approvalId)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            $result = ApprovalWorkflow::approve(
                $approvalId,
                auth()->id(),
                $request->notes,
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval submitted successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function reject(Request $request, $approvalId)
    {
        $request->validate([
            'notes' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            $result = ApprovalWorkflow::reject(
                $approvalId,
                auth()->id(),
                $request->notes,
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Rejection submitted successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($approvalId)
    {
        try {
            $path = ApprovalWorkflow::getApprovalPath($approvalId);
            $histories = ApprovalWorkflow::getApprovalHistories($approvalId);

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $path,
                    'histories' => $histories
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

## 2. Setup Flow dengan Seeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepUser;

class ApprovalFlowSeeder extends Seeder
{
    public function run()
    {
        // Purchase Request Flow
        $flow = Flow::create([
            'company_id' => 1,
            'type' => 'purchase-request',
            'name' => 'Purchase Request Approval Flow',
            'is_active' => true,
        ]);

        // Step 1: Department Manager (untuk amount > 100,000)
        $step1 = FlowStep::create([
            'flow_id' => $flow->id,
            'order' => 1,
            'name' => 'Department Manager Approval',
            'condition' => 'amount > 100000',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'department-manager',
        ]);

        // Step 2: Department Head (untuk amount > 500,000)
        $step2 = FlowStep::create([
            'flow_id' => $flow->id,
            'order' => 2,
            'name' => 'Department Head Approval',
            'condition' => 'amount > 500000',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $step2->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'department-head',
        ]);

        // Step 3: Finance Director (untuk amount > 2,000,000)
        $step3 = FlowStep::create([
            'flow_id' => $flow->id,
            'order' => 3,
            'name' => 'Finance Director Approval',
            'condition' => 'amount > 2000000',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $step3->id,
            'type' => 'USER',
            'user_id' => 1, // Finance Director user ID
        ]);

        // Asset Transfer Flow
        $assetFlow = Flow::create([
            'company_id' => 1,
            'type' => 'asset-transfer',
            'name' => 'Asset Transfer Approval Flow',
            'is_active' => true,
        ]);

        // Step 1: Origin Asset User
        $assetStep1 = FlowStep::create([
            'flow_id' => $assetFlow->id,
            'order' => 1,
            'name' => 'Origin User Confirmation',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $assetStep1->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'origin-asset-user',
        ]);

        // Step 2: Asset Coordinator
        $assetStep2 = FlowStep::create([
            'flow_id' => $assetFlow->id,
            'order' => 2,
            'name' => 'Asset Coordinator Approval',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $assetStep2->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'asset-coordinator',
        ]);

        // Step 3: Destination Asset User
        $assetStep3 = FlowStep::create([
            'flow_id' => $assetFlow->id,
            'order' => 3,
            'name' => 'Destination User Acceptance',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $assetStep3->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'destination-asset-user',
        ]);
    }
}
```

## 3. Model Extension untuk User

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalActiveUser;

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
     * Approval histories created by this user
     */
    public function approvalHistories()
    {
        return $this->hasMany(ApprovalHistory::class, 'user_id');
    }

    /**
     * Get pending approvals count
     */
    public function getPendingApprovalsCountAttribute()
    {
        return $this->pendingApprovals()->where('status', 'ON_PROGRESS')->count();
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

## 4. Blade Template untuk Approval

```blade
{{-- resources/views/approvals/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Approval Details #{{ $approval['id'] }}</h5>
                    <span class="badge badge-{{ $approval['status'] == 'APPROVED' ? 'success' : ($approval['status'] == 'REJECTED' ? 'danger' : 'warning') }}">
                        {{ $approval['status'] }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Approval Path -->
                    <h6>Approval Path</h6>
                    <div class="approval-path">
                        @foreach($approvalPath as $step)
                        <div class="step-item {{ $step['type'] }}">
                            <div class="step-header">
                                <strong>{{ $step['name'] }}</strong>
                                @if($step['type'] == 'current')
                                    <span class="badge badge-primary">Current</span>
                                @elseif($step['type'] == 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($step['type'] == 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @elseif($step['type'] == 'incoming')
                                    <span class="badge badge-secondary">Pending</span>
                                @endif
                            </div>
                            
                            @if($step['approver_name'])
                            <div class="step-approver">
                                <small>
                                    By: {{ $step['approver_name'] }} ({{ $step['approver_email'] }})
                                    <br>
                                    Time: {{ $step['approval_time'] }}
                                </small>
                            </div>
                            @endif

                            @if($step['approval_notes'])
                            <div class="step-notes">
                                <small><strong>Notes:</strong> {{ $step['approval_notes'] }}</small>
                            </div>
                            @endif

                            @if(isset($step['attached_files']) && count($step['attached_files']) > 0)
                            <div class="step-files">
                                <small><strong>Attachments:</strong></small>
                                @foreach($step['attached_files'] as $file)
                                <div class="file-item">
                                    <a href="{{ $file['url'] }}" target="_blank">
                                        {{ $file['name'] }}
                                    </a>
                                    @if($file['thumb_url'])
                                    <img src="{{ $file['thumb_url'] }}" alt="Thumbnail" class="file-thumb">
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Action Buttons -->
                    @if($canApprove && $approval['status'] == 'ON_PROGRESS')
                    <div class="mt-4">
                        <h6>Actions</h6>
                        <form id="approvalForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="attachment">Attachment (Optional)</label>
                                <input type="file" class="form-control-file" id="attachment" name="attachment">
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-success" onclick="submitApproval('approve')">
                                    Approve
                                </button>
                                <button type="button" class="btn btn-danger" onclick="submitApproval('reject')">
                                    Reject
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6>History</h6>
                </div>
                <div class="card-body">
                    @foreach($histories as $history)
                    <div class="history-item">
                        <div class="history-header">
                            <strong>{{ $history['title'] }}</strong>
                            <small class="text-muted">{{ $history['date_time'] }}</small>
                        </div>
                        @if($history['user_name'])
                        <div class="history-user">
                            <small>By: {{ $history['user_name'] }}</small>
                        </div>
                        @endif
                        @if($history['notes'])
                        <div class="history-notes">
                            <small>{{ $history['notes'] }}</small>
                        </div>
                        @endif
                        @if(isset($history['attached_files']) && count($history['attached_files']) > 0)
                        <div class="history-files">
                            @foreach($history['attached_files'] as $file)
                            <a href="{{ $file['url'] }}" target="_blank" class="file-link">
                                {{ $file['name'] }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <hr>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function submitApproval(action) {
    const form = document.getElementById('approvalForm');
    const formData = new FormData(form);
    
    const url = action === 'approve' 
        ? `/approvals/{{ $approval['id'] }}/approve`
        : `/approvals/{{ $approval['id'] }}/reject`;
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
@endsection
```

## 5. API Resource untuk JSON Response

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'status' => $this['status'],
            'flow_step_name' => $this['flow_step_name'],
            'parameters' => $this['parameters'],
            'stakeholders' => [
                'owner' => $this['stakeholders']['owner'],
                'current_approvers' => $this['stakeholders']['currentApprovers'],
                'previous_approvers' => $this['stakeholders']['previousApprovers'] ?? [],
            ],
            'created_at' => $this['created_at'] ?? null,
            'updated_at' => $this['updated_at'] ?? null,
        ];
    }
}

class ApprovalHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'flag' => $this['flag'],
            'notes' => $this['notes'],
            'user' => [
                'id' => $this['user_id'],
                'name' => $this['user_name'],
                'email' => $this['user_email'],
            ],
            'flow_step' => [
                'id' => $this['flow_step_id'],
                'name' => $this['flow_step_name'],
            ],
            'attached_files' => $this['attached_files'] ?? [],
            'date_time' => $this['date_time'],
        ];
    }
}
```

## 6. Notification untuk Approval

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ApprovalNotification extends Notification
{
    use Queueable;

    protected $approval;
    protected $type;

    public function __construct($approval, $type = 'pending')
    {
        $this->approval = $approval;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->getSubject();
        $message = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->action('View Approval', url('/approvals/' . $this->approval['id']))
            ->line('Please review and take action on this approval.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'approval_id' => $this->approval['id'],
            'type' => $this->type,
            'message' => $this->getMessage(),
            'url' => '/approvals/' . $this->approval['id'],
        ];
    }

    private function getSubject()
    {
        switch ($this->type) {
            case 'pending':
                return 'New Approval Required';
            case 'approved':
                return 'Approval Completed';
            case 'rejected':
                return 'Approval Rejected';
            default:
                return 'Approval Update';
        }
    }

    private function getMessage()
    {
        $stepName = $this->approval['flow_step_name'] ?? 'Unknown Step';
        
        switch ($this->type) {
            case 'pending':
                return "You have a new approval request waiting for your action at step: {$stepName}";
            case 'approved':
                return "Your approval request has been approved at step: {$stepName}";
            case 'rejected':
                return "Your approval request has been rejected at step: {$stepName}";
            default:
                return "There's an update on your approval request";
        }
    }
}
```

## 7. Command untuk Rebuild Approvers

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

class RebuildApproversCommand extends Command
{
    protected $signature = 'approval:rebuild-approvers {--company-id=}';
    protected $description = 'Rebuild approvers for all running approvals';

    public function handle()
    {
        $companyId = $this->option('company-id');
        
        $service = app(ApprovalService::class);
        
        if ($companyId) {
            $service->setCompanyId($companyId);
        }
        
        $service->rebuildApprovers();
        
        $this->info('Approvers rebuilt successfully for company ID: ' . $service->getCompanyId());
    }
}
```

## 8. Event Listener untuk Notifications

```php
<?php

namespace App\Listeners;

use App\Notifications\ApprovalNotification;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;
use Illuminate\Support\Facades\Notification;

class SendApprovalNotifications
{
    public function handle($event)
    {
        // Assuming you have custom events for approval actions
        $approval = $event->approval;
        $type = $event->type;

        // Get current approvers
        if ($type === 'pending' && !empty($approval['stakeholders']['currentApprovers'])) {
            $approvers = collect($approval['stakeholders']['currentApprovers']);
            $userIds = $approvers->pluck('user_id');
            
            $users = \App\Models\User::whereIn('id', $userIds)->get();
            
            Notification::send($users, new ApprovalNotification($approval, 'pending'));
        }

        // Notify owner on completion
        if (in_array($type, ['approved', 'rejected']) && $approval['stakeholders']['owner']) {
            $owner = \App\Models\User::find($approval['stakeholders']['owner']['user_id']);
            
            if ($owner) {
                $owner->notify(new ApprovalNotification($approval, $type));
            }
        }
    }
}
```

Contoh-contoh di atas menunjukkan implementasi lengkap dari Laravel Approval Workflow package dengan berbagai fitur seperti file upload, notification, dan UI yang user-friendly.
