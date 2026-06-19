# API Reference - Laravel Approval Workflow

Complete API reference for all public methods and classes.

## ApprovalHandler Service

Main service class for managing approval workflows.

### Constructor

```php
public function __construct(int $companyId)
```

**Parameters:**
- `$companyId` (int) - Company ID for the approval workflow

**Example:**
```php
$handler = new ApprovalHandler(1);
```

---

### start()

Start a new approval workflow.

```php
public function start(
    string $flowType, 
    int $userId, 
    ?array $parameters = null
): array
```

**Parameters:**
- `$flowType` (string) - Flow type identifier (e.g., 'PR', 'PO')
- `$userId` (int) - User ID who initiates the approval
- `$parameters` (array|null) - Optional parameters for workflow

**Returns:** Array with approval status and stakeholders

**Throws:**
- `Exception` - `EXC_USER_NOT_FOUND` if user doesn't exist
- `Exception` - `EXC_FLOW_NOT_FOUND` if flow doesn't exist

**Example:**
```php
$result = $handler->start('PR', 123, [
    'departmentId' => 10,
    'amount' => 5000,
]);
```

---

### approve()

Approve the current approval step.

```php
public function approve(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    ?string $file = null
): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID
- `$userId` (int) - User ID who approves
- `$notes` (string|null) - Optional approval notes
- `$file` (string|null) - Optional file attachment

**Returns:** Array with approval status

**Throws:**
- `Exception` - `EXC_USER_NOT_FOUND`
- `Exception` - `EXC_APPROVAL_NOT_RUNNING`
- `Exception` - `EXC_PERMISSION_DENIED`

**Example:**
```php
$result = $handler->approve(1, 123, 'Approved');
```

---

### reject()

Reject the current approval step.

```php
public function reject(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    ?string $file = null
): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID
- `$userId` (int) - User ID who rejects
- `$notes` (string|null) - Optional rejection notes
- `$file` (string|null) - Optional file attachment

**Returns:** Array with approval status

**Throws:**
- `Exception` - `EXC_USER_NOT_FOUND`
- `Exception` - `EXC_APPROVAL_NOT_RUNNING`
- `Exception` - `EXC_PERMISSION_DENIED`

**Example:**
```php
$result = $handler->reject(1, 123, 'Budget exceeded');
```

---

### rejectBySystem()

Reject approval by system (admin override).

```php
public function rejectBySystem(
    int $approvalId, 
    int $relatedUserId, 
    ?string $notes = null, 
    ?string $file = null
): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID
- `$relatedUserId` (int) - User ID who triggered rejection
- `$notes` (string|null) - Optional notes
- `$file` (string|null) - Optional file

**Returns:** Array with approval status

**Throws:**
- `Exception` - `EXC_USER_NOT_FOUND`

**Example:**
```php
$result = $handler->rejectBySystem(1, 123, 'Policy violation');
```

---

### reset()

Reset a rejected approval for resubmission.

```php
public function reset(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    ?string $file = null, 
    ?array $parameters = null
): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID
- `$userId` (int) - User ID who resets
- `$notes` (string|null) - Optional notes
- `$file` (string|null) - Optional file
- `$parameters` (array|null) - New parameters (optional)

**Returns:** Array with approval status

**Example:**
```php
$result = $handler->reset(1, 123, 'Corrected', null, [
    'amount' => 4000
]);
```

---

### rebuildApprovers()

Rebuild approvers for all running approvals.

```php
public function rebuildApprovers(): void
```

**Example:**
```php
$handler->rebuildApprovers();
```

---

### getNextSteps()

Get the next step in the approval workflow.

```php
public function getNextSteps(int $approvalId): mixed
```

**Parameters:**
- `$approvalId` (int) - Approval ID

**Returns:** Array of next step or null

**Example:**
```php
$nextStep = $handler->getNextSteps(1);
```

---

### getApprovalPath()

Get the complete approval path with status.

```php
public function getApprovalPath(int $approvalId): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID

**Returns:** Array of steps with status

**Example:**
```php
$path = $handler->getApprovalPath(1);
```

---

### getApprovalHistories()

Get all approval histories with media attachments.

```php
public function getApprovalHistories(int $approvalId): array
```

**Parameters:**
- `$approvalId` (int) - Approval ID

**Returns:** Array of history records with the following fields:
- `id` - History ID
- `approval_id` - Approval ID
- `flow_step_id` - Flow step ID
- `flow_step_order` - Step order
- `flow_step_flow_id` - Flow ID
- `flow_step_name` - Step name
- `flow_step_condition` - Step condition
- `user_id` - User ID
- `user_email` - User email
- `user_name` - User name
- `title` - Action title
- `flag` - History flag (created, approved, rejected, etc.)
- `notes` - Notes
- `file` - Legacy file field (deprecated)
- `date_time` - Timestamp
- `media_url` - URL of the last uploaded media file (null if no media)

**Example:**
```php
$histories = $handler->getApprovalHistories(1);

foreach ($histories as $history) {
    echo "{$history['title']} by {$history['user_name']}\n";
    
    // Display media URL if exists
    if ($history['media_url']) {
        echo "Attachment: {$history['media_url']}\n";
    }
}
```

---

## Models

### Flow

```php
namespace AsetKita\LaravelApprovalWorkflow\Models;

class Flow extends Model
```

**Attributes:**
- `id` (int)
- `type` (string) - Flow type identifier
- `company_id` (int)
- `is_active` (int) - 0 or 1
- `label` (string|null)

**Relationships:**
- `steps()` - HasMany FlowStep
- `approvals()` - HasMany Approval

**Scopes:**
- `active()` - Only active flows
- `ofType(string $type)` - Filter by type
- `forCompany(int $companyId)` - Filter by company

---

### FlowStep

```php
class FlowStep extends Model
```

**Attributes:**
- `id` (int)
- `order` (int)
- `flow_id` (int)
- `name` (string|null)
- `condition` (string|null) - Expression Language syntax

**Relationships:**
- `flow()` - BelongsTo Flow
- `approvers()` - HasMany FlowStepApprover
- `histories()` - HasMany ApprovalHistory

---

### Approval

```php
class Approval extends Model
```

**Attributes:**
- `id` (int)
- `flow_id` (int)
- `status` (enum) - ON_PROGRESS, APPROVED, REJECTED
- `flow_step_id` (int|null)
- `user_id` (int)
- `parameters` (array)
- `company_id` (int)

**Relationships:**
- `flow()` - BelongsTo Flow
- `currentStep()` - BelongsTo FlowStep
- `owner()` - BelongsTo User
- `histories()` - HasMany ApprovalHistory
- `activeUsers()` - BelongsToMany User

**Scopes:**
- `onProgress()` - Status = ON_PROGRESS
- `approved()` - Status = APPROVED
- `rejected()` - Status = REJECTED
- `withStatus(string $status)` - Filter by status
- `forCompany(int $companyId)` - Filter by company

---

### ApprovalHistory

```php
class ApprovalHistory extends Model implements HasMedia
```

**Attributes:**
- `id` (int)
- `approval_id` (int)
- `user_id` (int|null)
- `flow_step_id` (int|null)
- `title` (string|null)
- `flag` (string|null)
- `notes` (string|null)
- `file` (string|null)
- `date_time` (int|null)

**Constants:**
- `HFLAG_CREATED` = 'created'
- `HFLAG_RESET` = 'reset'
- `HFLAG_APPROVED` = 'approved'
- `HFLAG_REJECTED` = 'rejected'
- `HFLAG_SYSTEM_REJECTED` = 'system_rejected'
- `HFLAG_DONE` = 'done'
- `HFLAG_SKIP` = 'skip'

**Relationships:**
- `approval()` - BelongsTo Approval
- `user()` - BelongsTo User
- `flowStep()` - BelongsTo FlowStep

**Media:**
- Collection: `attachments`
- `getAttachmentsAttribute()` - Get all attachments

**Scopes:**
- `withFlag(string $flag)` - Filter by flag
- `forApproval(int $approvalId)` - Filter by approval

---

## Facade

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

ApprovalWorkflow::start($flowType, $userId, $parameters);
ApprovalWorkflow::approve($approvalId, $userId, $notes, $file);
ApprovalWorkflow::reject($approvalId, $userId, $notes, $file);
ApprovalWorkflow::rejectBySystem($approvalId, $userId, $notes, $file);
ApprovalWorkflow::reset($approvalId, $userId, $notes, $file, $parameters);
ApprovalWorkflow::rebuildApprovers();
ApprovalWorkflow::getNextSteps($approvalId);
ApprovalWorkflow::getApprovalPath($approvalId);
ApprovalWorkflow::getApprovalHistories($approvalId);
```

---

## Constants

### Approver Types

```php
'USER'         // Direct user ID
'GROUP'        // Approver group ID
'SYSTEM_GROUP' // System-defined groups
```

### System Groups

```php
'department-manager'
'department-head'
'department-staff'
'asset-coordinator'
'origin-asset-user'
'destination-asset-user'
```

### Approval Status

```php
'ON_PROGRESS' // Approval in progress
'APPROVED'    // All steps completed
'REJECTED'    // Approval rejected
```

### History Flags

```php
ApprovalHistory::HFLAG_CREATED          // 'created'
ApprovalHistory::HFLAG_RESET            // 'reset'
ApprovalHistory::HFLAG_APPROVED         // 'approved'
ApprovalHistory::HFLAG_REJECTED         // 'rejected'
ApprovalHistory::HFLAG_SYSTEM_REJECTED  // 'system_rejected'
ApprovalHistory::HFLAG_DONE             // 'done'
ApprovalHistory::HFLAG_SKIP             // 'skip'
```

### Exception Constants

```php
ApprovalHandler::EXC_USER_NOT_FOUND        // 'exc_user_not_found'
ApprovalHandler::EXC_FLOW_NOT_FOUND        // 'exc_flow_not_found'
ApprovalHandler::EXC_PERMISSION_DENIED     // 'exc_permission_denied'
ApprovalHandler::EXC_APPROVAL_NOT_RUNNING  // 'exc_approval_not_running'
ApprovalHandler::EXC_APPROVAL_NOT_REJECTED // 'exc_approval_not_rejected'
```

---

## Configuration Options

```php
// config/approval-workflow.php

return [
    'default_company_id' => 1,
    'user_model' => \App\Models\User::class,
    'media_disk' => 'public',
    'notifications_enabled' => false,
    'notification_channels' => ['mail', 'database'],
];
```

---

## Expression Language Syntax

Used in FlowStep `condition` field:

```php
// Comparisons
'amount > 5000'
'category == "IT"'
'priority != "low"'

// Logical operators
'amount > 5000 and category == "IT"'
'priority == "high" or amount > 10000'

// IN operator
'category in ["IT", "Finance", "HR"]'
'status not in ["draft", "cancelled"]'

// Complex expressions
'(amount > 10000 or priority == "urgent") and status == "pending"'
```

---

## Response Structures

### Start/Approve/Reject/Reset Response

```php
[
    'id' => 1,
    'flow_id' => 2,
    'status' => 'ON_PROGRESS',
    'flow_step_id' => 5,
    'flow_step_name' => 'Manager Approval',
    'parameters' => [
        'departmentId' => 10,
        'amount' => 5000,
    ],
    'stakeholders' => [
        'owner' => [
            'user_id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
        'previousApprovers' => [
            ['user_id' => 456, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ],
        'currentApprovers' => [
            ['user_id' => 789, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ],
        'steps' => [...], // Only when completed
    ]
]
```

### History Response

```php
[
    [
        'id' => 1,
        'approval_id' => 10,
        'user_id' => 5,
        'user_email' => 'user@example.com',
        'user_name' => 'John Doe',
        'flow_step_id' => 3,
        'flow_step_order' => 1,
        'flow_step_flow_id' => 2,
        'flow_step_name' => 'Manager Approval',
        'flow_step_condition' => null,
        'title' => 'Approved',
        'flag' => 'approved',
        'notes' => 'Looks good',
        'file' => null,
        'date_time' => 1234567890,
    ],
    // ... more history records
]
```

### Approval Path Response

```php
[
    [
        'id' => 5,
        'order' => 1,
        'flow_id' => 2,
        'name' => 'Manager Approval',
        'condition' => null,
        'approvers' => [...],
        'type' => 'approved',  // current, passed, approved, rejected, incoming
        'approver_id' => 123,
        'approver_email' => 'manager@example.com',
        'approver_name' => 'Manager Name',
        'approval_notes' => 'Approved',
        'approval_file' => null,
        'approval_time' => 1234567890,
    ],
    // ... more steps
]
```
