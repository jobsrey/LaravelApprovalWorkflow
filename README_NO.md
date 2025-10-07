# Laravel Approval Workflow

Package Laravel untuk mengelola workflow persetujuan dengan dukungan Spatie Media Library untuk file attachment.

## Fitur

- ✅ **Eloquent Models** - Menggunakan Eloquent ORM Laravel
- ✅ **Spatie Media Library** - Support file attachment di approval history
- ✅ **Flexible Flow Configuration** - Konfigurasi flow yang fleksibel dengan kondisi
- ✅ **System Groups** - Dukungan untuk grup sistem (department manager, head, dll)
- ✅ **Expression Language** - Kondisi step menggunakan Symfony Expression Language
- ✅ **Laravel Integration** - Service Provider, Facade, dan konfigurasi Laravel
- ✅ **Database Migrations** - Migrasi database yang lengkap

## Instalasi

### 1. Install Package

```bash
composer require asetkita/laravel-approval-workflow
```

### 2. Publish Konfigurasi dan Migrasi

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
```

Atau publish secara terpisah:

```bash
# Publish konfigurasi
php artisan vendor:publish --tag=approval-workflow-config

# Publish migrasi
php artisan vendor:publish --tag=approval-workflow-migrations
```

### 3. Jalankan Migrasi

```bash
php artisan migrate
```

### 4. Install Spatie Media Library (jika belum)

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan migrate
```

## Konfigurasi

Edit file `config/approval-workflow.php`:

```php
return [
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', 'App\\Models\\User'),
    
    'media' => [
        'collection_name' => 'files',
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            // ... mime types lainnya
        ],
        'max_file_size' => 10 * 1024 * 1024, // 10MB
    ],
    
    'system_groups' => [
        'department-manager' => 'Department Manager',
        'department-head' => 'Department Head',
        'asset-coordinator' => 'Asset Coordinator',
        // ... grup lainnya
    ],
];
```

## Penggunaan

### 1. Menggunakan Facade

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

// Memulai approval
$result = ApprovalWorkflow::start('purchase-request', $userId, [
    'departmentId' => 1,
    'amount' => 1000000,
]);

// Menyetujui
$result = ApprovalWorkflow::approve($approvalId, $userId, 'Disetujui', $file);

// Menolak
$result = ApprovalWorkflow::reject($approvalId, $userId, 'Ditolak karena...', $file);
```

### 2. Menggunakan Service Container

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

$approvalService = app(ApprovalService::class);
$result = $approvalService->start('purchase-request', $userId, $parameters);
```

### 3. Dependency Injection

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

class PurchaseController extends Controller
{
    public function submitForApproval(ApprovalService $approvalService)
    {
        $result = $approvalService->start('purchase-request', auth()->id(), [
            'departmentId' => auth()->user()->department_id,
            'amount' => $request->amount,
        ]);
        
        return response()->json($result);
    }
}
```

## File Attachment

### Upload File saat Approval

```php
// Dengan file upload
$result = ApprovalWorkflow::approve($approvalId, $userId, 'Approved with document', $request->file('attachment'));

// Multiple files
$files = $request->file('attachments'); // array of UploadedFile
foreach ($files as $file) {
    ApprovalWorkflow::approve($approvalId, $userId, 'Approved', $file);
}
```

### Mengakses File dari History

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find($historyId);

// Get all attached files
$files = $history->getAttachedFiles();

// Get file URLs
$fileUrls = $history->getFileUrls();

foreach ($fileUrls as $file) {
    echo "File: " . $file['name'] . "\n";
    echo "URL: " . $file['url'] . "\n";
    echo "Thumbnail: " . $file['thumb_url'] . "\n";
}
```

## Model Relationships

### Approval Model

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

$approval = Approval::find(1);

// Relationships
$approval->flow;           // Flow model
$approval->currentStep;    // FlowStep model
$approval->owner;          // User model
$approval->histories;      // Collection of ApprovalHistory
$approval->activeUsers;    // Collection of ApprovalActiveUser

// Scopes
Approval::running()->get();                    // Running approvals
Approval::byCompany($companyId)->get();        // By company
```

### ApprovalHistory Model

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find(1);

// Relationships
$history->approval;    // Approval model
$history->flowStep;    // FlowStep model
$history->user;        // User model

// Media Library methods
$history->addFile($uploadedFile, 'Document Name');
$history->getAttachedFiles();
$history->getFileUrls();

// Scopes
ApprovalHistory::byFlag('approved')->get();
ApprovalHistory::approvalActions()->get();
ApprovalHistory::resetActions()->get();
```

## API Methods

### ApprovalService Methods

#### `start(string $flowType, int $userId, ?array $parameters = []): array`

Memulai workflow approval baru.

**Parameters:**
- `$flowType`: Tipe flow yang akan digunakan
- `$userId`: ID user yang memulai approval
- `$parameters`: Parameter tambahan untuk flow

**Returns:** Array dengan informasi approval dan stakeholders

#### `approve(int $approvalId, int $userId, ?string $notes = null, $file = null): array`

Menyetujui step yang sedang aktif.

**Parameters:**
- `$approvalId`: ID approval
- `$userId`: ID user yang menyetujui
- `$notes`: Catatan approval (opsional)
- `$file`: File attachment (UploadedFile atau path, opsional)

#### `reject(int $approvalId, int $userId, ?string $notes = null, $file = null): array`

Menolak step yang sedang aktif.

#### `reset(int $approvalId, int $userId, ?string $notes = null, $file = null, ?array $parameters = null): array`

Reset approval yang sudah ditolak untuk dimulai ulang.

#### `rejectBySystem(int $approvalId, int $relatedUserId, ?string $notes = null, $file = null): array`

Menolak approval oleh sistem.

#### `getApprovalPath(int $approvalId): array`

Mendapatkan path approval dari awal hingga selesai.

#### `getApprovalHistories(int $approvalId): array`

Mendapatkan semua history approval.

## Flow Configuration

### Membuat Flow

```php
use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepUser;

// Create flow
$flow = Flow::create([
    'company_id' => 1,
    'type' => 'purchase-request',
    'name' => 'Purchase Request Approval',
    'is_active' => true,
]);

// Create steps
$step1 = FlowStep::create([
    'flow_id' => $flow->id,
    'order' => 1,
    'name' => 'Department Manager Approval',
    'condition' => 'amount > 100000', // Symfony Expression Language
]);

$step2 = FlowStep::create([
    'flow_id' => $flow->id,
    'order' => 2,
    'name' => 'Department Head Approval',
    'condition' => 'amount > 500000',
]);

// Assign users to steps
FlowStepUser::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP',
    'user_group_id' => 'department-manager',
]);

FlowStepUser::create([
    'flow_step_id' => $step2->id,
    'type' => 'USER',
    'user_id' => 1, // Specific user
]);
```

### System Groups

System groups memungkinkan assignment approver secara dinamis:

- `department-manager`: Manager dari department
- `department-head`: Head dari department  
- `asset-coordinator`: Koordinator asset
- `origin-asset-user`: User asal asset
- `destination-asset-user`: User tujuan asset

### Expression Language

Kondisi step menggunakan Symfony Expression Language:

```php
// Contoh kondisi
'amount > 100000'                           // Jumlah lebih dari 100rb
'amount > 100000 and departmentId == 1'     // Jumlah > 100rb DAN dept ID = 1
'type == "urgent"'                          // Tipe urgent
'amount > 500000 or priority == "high"'     // Jumlah > 500rb ATAU prioritas tinggi
```

## Exception Handling

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;

try {
    $result = ApprovalWorkflow::start('purchase-request', $userId, $parameters);
} catch (Exception $e) {
    switch ($e->getMessage()) {
        case ApprovalService::EXC_USER_NOT_FOUND:
            // Handle user not found
            break;
        case ApprovalService::EXC_FLOW_NOT_FOUND:
            // Handle flow not found
            break;
        case ApprovalService::EXC_PERMISSION_DENIED:
            // Handle permission denied
            break;
        case ApprovalService::EXC_APPROVAL_NOT_RUNNING:
            // Handle approval not running
            break;
    }
}
```

## Database Schema

Package ini membuat tabel-tabel berikut:

- `wf_flows` - Konfigurasi flow
- `wf_flow_steps` - Step dalam flow
- `wf_flow_step_users` - Assignment user ke step
- `wf_approvals` - Instance approval yang berjalan
- `wf_approval_active_users` - User yang bisa approve step saat ini
- `wf_approval_histories` - History semua aksi approval

## Migrasi dari Versi Lama

Jika Anda sudah menggunakan versi non-Laravel sebelumnya:

1. Backup database existing
2. Install package Laravel ini
3. Jalankan migrasi (akan membuat tabel baru dengan struktur yang sama)
4. Migrate data dari tabel lama ke tabel baru jika diperlukan
5. Update kode untuk menggunakan service dan model baru

## Testing

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

class ApprovalWorkflowTest extends TestCase
{
    public function test_can_start_approval()
    {
        $service = app(ApprovalService::class);
        
        $result = $service->start('test-flow', 1, ['amount' => 100000]);
        
        $this->assertEquals('ON_PROGRESS', $result['status']);
        $this->assertDatabaseHas('wf_approvals', [
            'id' => $result['id'],
            'status' => 'ON_PROGRESS'
        ]);
    }
}
```

## Lisensi

MIT License

## Support

Untuk pertanyaan dan dukungan, silakan buat issue di repository ini.
