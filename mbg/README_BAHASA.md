# Laravel Approval Workflow - Panduan Lengkap (Bahasa Indonesia)

Sistem approval workflow yang fleksibel dan powerful untuk aplikasi Laravel 12+ dengan dukungan Spatie Media Library.

## 🚀 Fitur Utama

- ✅ Multi-step approval workflows
- ✅ Dynamic approver assignment (User, Group, System Group)
- ✅ Conditional workflow steps menggunakan Expression Language
- ✅ Department-based approvals (Staff, Manager, Head)
- ✅ Asset coordinator approvals
- ✅ Complete approval history tracking
- ✅ Spatie Media Library integration untuk file attachments
- ✅ System-level rejections
- ✅ Approval reset/resubmission
- ✅ Multi-company support
- ✅ Laravel 12 compatible

## 📋 Kebutuhan Sistem

- PHP 8.2 atau lebih tinggi
- Laravel 11.0 atau 12.0 atau lebih tinggi
- MySQL 5.7+ atau PostgreSQL 9.6+

## 📦 Instalasi

### 1. Install via Composer

```bash
composer require asetkita/laravel-approval-workflow
```

### 2. Publish Configuration dan Migrations

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
```

### 3. Jalankan Migrations

```bash
php artisan migrate
```

Ini akan membuat 10 tabel:
- `wf_department_users`
- `wf_asset_coordinator_users`
- `wf_approver_groups`
- `wf_approver_group_users`
- `wf_flows`
- `wf_flow_steps`
- `wf_flow_step_approvers`
- `wf_approvals`
- `wf_approval_active_users`
- `wf_approval_histories`

### 4. Install Spatie Media Library

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

## 🎯 Quick Start

### 1. Buat Flow Approval

```php
use AsetKita\LaravelApprovalWorkflow\Models\{Flow, FlowStep, FlowStepApprover};

// Buat flow
$flow = Flow::create([
    'type' => 'PR',  // Purchase Request
    'company_id' => 1,
    'is_active' => 1,
    'label' => 'Purchase Request Approval',
]);

// Tambah step
$step1 = FlowStep::create([
    'order' => 1,
    'flow_id' => $flow->id,
    'name' => 'Manager Approval',
]);

// Konfigurasi approver
FlowStepApprover::create([
    'flow_step_id' => $step1->id,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);
```

### 2. Mulai Approval

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler($companyId = 1);

$result = $handler->start('PR', auth()->id(), [
    'departmentId' => 10,
    'amount' => 5000,
    'description' => 'Pembelian alat tulis kantor',
]);

$approvalId = $result['id'];
```

### 3. Approve/Reject

```php
// Approve
$handler->approve($approvalId, auth()->id(), 'Disetujui');

// Reject
$handler->reject($approvalId, auth()->id(), 'Ditolak karena budget tidak cukup');
```

### 4. Lihat History

```php
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo "{$history['title']} oleh {$history['user_name']}\n";
}
```

## 📚 Dokumentasi Lengkap

### Bahasa Indonesia
- **README_BAHASA.md** (file ini) - Panduan dasar
- Lihat file-file di bawah untuk detail lengkap

### English Documentation
- **README.md** - Complete documentation
- **INSTALLATION.md** - Installation guide
- **USAGE.md** - Usage guide with examples
- **API_REFERENCE.md** - Complete API reference
- **EXAMPLES.md** - Copy-paste code examples
- **QUICKSTART.md** - 5-minute quick start
- **MIGRATION_SUMMARY.md** - Migration details
- **PROJECT_COMPLETE.md** - Project completion summary

## 🔧 Konfigurasi

Edit `config/approval-workflow.php`:

```php
return [
    // Company ID default
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),
    
    // Model User Anda
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', \App\Models\User::class),
    
    // Disk untuk media storage
    'media_disk' => env('APPROVAL_WORKFLOW_MEDIA_DISK', 'public'),
];
```

## 💡 Contoh Penggunaan

### Purchase Request Controller

```php
namespace App\Http\Controllers;

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

        $result = $handler->start('PR', auth()->id(), [
            'departmentId' => auth()->user()->department_id,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
        ]);

        return response()->json([
            'success' => true,
            'approval_id' => $result['id'],
        ]);
    }

    public function approve($approvalId)
    {
        $handler = new ApprovalHandler(auth()->user()->company_id);
        
        $result = $handler->approve(
            $approvalId,
            auth()->id(),
            request('notes')
        );

        return response()->json(['success' => true]);
    }
}
```

## 🎨 Tipe Approver

### 1. USER - User Langsung
Assign user tertentu sebagai approver.

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'USER',
    'data' => '123', // User ID
]);
```

### 2. GROUP - Custom Group
Assign group approver yang sudah dibuat.

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'GROUP',
    'data' => '5', // Approver Group ID
]);
```

### 3. SYSTEM_GROUP - System Group Dinamis
System group yang otomatis berdasarkan parameters:

- `department-manager` - Manager departemen
- `department-head` - Head departemen
- `department-staff` - Staff departemen
- `asset-coordinator` - Coordinator asset
- `origin-asset-user` - User origin asset
- `destination-asset-user` - User destination asset

```php
FlowStepApprover::create([
    'flow_step_id' => $stepId,
    'type' => 'SYSTEM_GROUP',
    'data' => 'department-manager',
]);
```

## 📊 Conditional Steps

Gunakan Expression Language untuk conditional steps:

```php
// Step hanya jika amount > 5000
FlowStep::create([
    'order' => 2,
    'flow_id' => $flow->id,
    'name' => 'Finance Approval',
    'condition' => 'amount > 5000',
]);

// Step hanya jika amount > 10000 ATAU priority tinggi
FlowStep::create([
    'order' => 3,
    'flow_id' => $flow->id,
    'name' => 'Director Approval',
    'condition' => 'amount > 10000 or priority == "high"',
]);
```

## 📝 Parameters

Kirim parameters saat start approval:

```php
$handler->start('PR', $userId, [
    'departmentId' => 10,        // Required untuk department-based approvers
    'amount' => 5000,            // Custom parameter
    'category' => 'IT',          // Custom parameter
    'priority' => 'high',        // Custom parameter
    'assetCategoryId' => 5,      // Required untuk asset-coordinator
]);
```

## 📋 History Flags

System otomatis mencatat setiap aksi:

- `created` - Approval dibuat
- `reset` - Approval di-reset
- `approved` - Step disetujui
- `rejected` - Step ditolak
- `system_rejected` - Ditolak oleh system
- `done` - Approval selesai
- `skip` - Step dilewati (no approvers)

## 📁 File Attachments

Menggunakan Spatie Media Library:

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find($historyId);

// Tambah file
$history->addMedia($request->file('attachment'))
    ->toMediaCollection('attachments');

// Get files
$attachments = $history->getMedia('attachments');
foreach ($attachments as $media) {
    echo $media->getUrl();
}
```

## 🔍 Query Approvals

```php
use AsetKita\LaravelApprovalWorkflow\Models\Approval;

// Approval yang sedang berjalan
$approvals = Approval::onProgress()->get();

// Approval yang sudah disetujui
$approvals = Approval::approved()->get();

// Approval yang ditolak
$approvals = Approval::rejected()->get();

// Approval untuk company tertentu
$approvals = Approval::forCompany(1)->get();

// Approval yang menunggu saya approve
$myPendingApprovals = Approval::onProgress()
    ->whereHas('activeUsers', function($q) {
        $q->where('user_id', auth()->id());
    })
    ->get();
```

## 🎯 Method-Method Utama

### start()
Mulai approval workflow baru.

```php
$result = $handler->start($flowType, $userId, $parameters);
```

### approve()
Setujui step saat ini.

```php
$result = $handler->approve($approvalId, $userId, $notes, $file);
```

### reject()
Tolak step saat ini.

```php
$result = $handler->reject($approvalId, $userId, $notes, $file);
```

### reset()
Reset approval yang ditolak untuk diajukan ulang.

```php
$result = $handler->reset($approvalId, $userId, $notes, $file, $newParameters);
```

### rejectBySystem()
Reject oleh system (admin override).

```php
$result = $handler->rejectBySystem($approvalId, $relatedUserId, $notes, $file);
```

### getApprovalHistories()
Ambil semua history approval beserta URL media attachment terakhir.

```php
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo "{$history['title']} oleh {$history['user_name']}\n";
    
    // Tampilkan URL media jika ada
    if ($history['media_url']) {
        echo "File: {$history['media_url']}\n";
    }
}
```

**Catatan:** Field `media_url` berisi URL file media terakhir yang diupload untuk setiap history record. Jika tidak ada media, nilainya `null`.

### getApprovalPath()
Ambil path approval dengan status.

```php
$path = $handler->getApprovalPath($approvalId);
```

## ⚠️ Exception Handling

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

try {
    $result = $handler->approve($approvalId, $userId);
} catch (\Exception $e) {
    if ($e->getMessage() === ApprovalHandler::EXC_PERMISSION_DENIED) {
        return response()->json(['error' => 'Anda tidak punya izin'], 403);
    }
    if ($e->getMessage() === ApprovalHandler::EXC_APPROVAL_NOT_RUNNING) {
        return response()->json(['error' => 'Approval sudah selesai'], 400);
    }
}
```

## 🗂️ Struktur Database

Package ini membuat 10 tabel dengan struktur yang sama persis dengan versi lama:

1. **wf_department_users** - Mapping user ke department
2. **wf_asset_coordinator_users** - Mapping coordinator asset
3. **wf_approver_groups** - Group approver custom
4. **wf_approver_group_users** - Member dari group
5. **wf_flows** - Definisi flow approval
6. **wf_flow_steps** - Step-step dalam flow
7. **wf_flow_step_approvers** - Approver untuk setiap step
8. **wf_approvals** - Instance approval yang berjalan
9. **wf_approval_active_users** - Current approvers
10. **wf_approval_histories** - History semua aksi

## 🔗 Facade

Gunakan facade untuk akses lebih mudah:

```php
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;

ApprovalWorkflow::start('PR', $userId, $parameters);
ApprovalWorkflow::approve($approvalId, $userId, $notes);
ApprovalWorkflow::reject($approvalId, $userId, $notes);
ApprovalWorkflow::getApprovalHistories($approvalId);
```

## 📦 File-File yang Tersedia

### Dokumentasi (11 file)
- README.md - English documentation
- README_BAHASA.md - Dokumentasi Bahasa Indonesia
- INSTALLATION.md - Panduan instalasi
- USAGE.md - Panduan penggunaan lengkap
- API_REFERENCE.md - API reference
- EXAMPLES.md - Contoh code
- QUICKSTART.md - Quick start
- MIGRATION_SUMMARY.md - Summary migrasi
- PROJECT_COMPLETE.md - Summary proyek
- PACKAGE_CONTENTS.md - Daftar isi
- CHANGELOG.md - Version history

### Source Code
- 10 Migration files
- 10 Model files (dengan Spatie Media Library)
- 4 Repository files
- 1 Service class (ApprovalHandler)
- 1 Service Provider
- 1 Facade
- 1 Config file
- 1 Seeder example

**Total: 41 files**

## ✅ Feature Parity dengan Versi Lama

Semua fitur dari versi lama sudah ada di versi baru:

✅ Semua 10 tabel (struktur sama)
✅ Semua method ApprovalHandler
✅ Semua approver types
✅ Semua system groups
✅ Semua parameters
✅ Semua history flags
✅ Semua exception handling
➕ Plus: Spatie Media Library support
➕ Plus: Laravel 12 integration
➕ Plus: Comprehensive documentation

## 🚀 Production Ready

Package ini siap digunakan untuk production dengan:
- ✅ Complete feature parity
- ✅ Laravel 12 compatible
- ✅ PHP 8.2 type safety
- ✅ Eloquent ORM
- ✅ Comprehensive documentation
- ✅ Example code
- ✅ Seeder examples

## 📞 Support

Untuk pertanyaan dan issue, silakan buka issue di GitHub repository.

## 📄 License

MIT License

## 👨‍💻 Author

**Rey**  
Email: kireniusdena@gmail.com

---

**Selamat menggunakan Laravel Approval Workflow!** 🎉
