# Panduan Instalasi Laravel Approval Workflow

## Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- Laravel 9.x, 10.x, atau 11.x
- MySQL 5.7+ atau PostgreSQL 9.6+
- Composer

## Instalasi Step by Step

### 1. Install Package via Composer

```bash
composer require asetkita/laravel-approval-workflow
```

### 2. Install Spatie Media Library (Dependency)

Jika belum terinstall, install Spatie Media Library:

```bash
composer require spatie/laravel-medialibrary
```

### 3. Publish Assets

Publish konfigurasi dan migrasi:

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
```

Atau publish secara terpisah:

```bash
# Publish konfigurasi saja
php artisan vendor:publish --tag=approval-workflow-config

# Publish migrasi saja
php artisan vendor:publish --tag=approval-workflow-migrations
```

### 4. Publish Spatie Media Library Migrations

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
```

### 5. Konfigurasi Environment

Tambahkan ke file `.env`:

```env
# Approval Workflow Configuration
APPROVAL_WORKFLOW_COMPANY_ID=1
APPROVAL_WORKFLOW_USER_MODEL="App\Models\User"
APPROVAL_WORKFLOW_NOTIFICATIONS=true
APPROVAL_WORKFLOW_EXPRESSION_CACHE=true
```

### 6. Konfigurasi Database

Pastikan konfigurasi database di `.env` sudah benar:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 7. Jalankan Migrasi

```bash
php artisan migrate
```

### 8. Konfigurasi User Model (Opsional)

Jika menggunakan User model custom, update di `config/approval-workflow.php`:

```php
'user_model' => 'App\\Models\\CustomUser',
```

### 9. Setup Storage Link (untuk Media Library)

```bash
php artisan storage:link
```

### 10. Konfigurasi Filesystem (Opsional)

Di `config/filesystems.php`, pastikan disk default sudah dikonfigurasi:

```php
'default' => env('FILESYSTEM_DISK', 'local'),

'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

## Konfigurasi Lanjutan

### 1. Konfigurasi Media Library

Edit `config/approval-workflow.php`:

```php
'media' => [
    'collection_name' => 'files',
    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ],
    'max_file_size' => 10 * 1024 * 1024, // 10MB
],
```

### 2. Konfigurasi Notifications

```php
'notifications' => [
    'enabled' => env('APPROVAL_WORKFLOW_NOTIFICATIONS', true),
    'channels' => ['mail', 'database'],
],
```

### 3. Konfigurasi System Groups

Sesuaikan system groups dengan kebutuhan organisasi:

```php
'system_groups' => [
    'department-manager' => 'Department Manager',
    'department-head' => 'Department Head',
    'asset-coordinator' => 'Asset Coordinator',
    'origin-asset-user' => 'Origin Asset User',
    'destination-asset-user' => 'Destination Asset User',
    'finance-director' => 'Finance Director',
    'hr-manager' => 'HR Manager',
],
```

## Setup Data Awal

### 1. Buat Seeder untuk Flow

```bash
php artisan make:seeder ApprovalFlowSeeder
```

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
            'name' => 'Purchase Request Approval',
            'is_active' => true,
        ]);

        // Step 1: Manager approval for amount > 100k
        $step1 = FlowStep::create([
            'flow_id' => $flow->id,
            'order' => 1,
            'name' => 'Manager Approval',
            'condition' => 'amount > 100000',
        ]);

        FlowStepUser::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'user_group_id' => 'department-manager',
        ]);

        // Step 2: Head approval for amount > 500k
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
    }
}
```

### 2. Jalankan Seeder

```bash
php artisan db:seed --class=ApprovalFlowSeeder
```

### 3. Update DatabaseSeeder

Tambahkan ke `DatabaseSeeder.php`:

```php
public function run()
{
    $this->call([
        ApprovalFlowSeeder::class,
    ]);
}
```

## Verifikasi Instalasi

### 1. Test Basic Functionality

Buat test sederhana:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalService;
use AsetKita\LaravelApprovalWorkflow\Models\Flow;

class ApprovalWorkflowTest extends TestCase
{
    public function test_can_create_approval_service()
    {
        $service = app(ApprovalService::class);
        $this->assertInstanceOf(ApprovalService::class, $service);
    }

    public function test_can_access_models()
    {
        $this->assertTrue(class_exists(Flow::class));
    }
}
```

### 2. Test dengan Tinker

```bash
php artisan tinker
```

```php
// Test service
$service = app(\AsetKita\LaravelApprovalWorkflow\Services\ApprovalService::class);
echo get_class($service);

// Test models
$flows = \AsetKita\LaravelApprovalWorkflow\Models\Flow::all();
echo $flows->count();

// Test facade
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;
echo ApprovalWorkflow::getCompanyId();
```

## Troubleshooting

### 1. Class Not Found

Jika mendapat error "Class not found":

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### 2. Migration Issues

Jika ada masalah dengan migrasi:

```bash
# Rollback dan jalankan ulang
php artisan migrate:rollback
php artisan migrate

# Atau reset database
php artisan migrate:fresh
```

### 3. Media Library Issues

Jika ada masalah dengan file upload:

```bash
# Pastikan storage link
php artisan storage:link

# Check permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 4. Permission Issues

```bash
# Set proper permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
```

### 5. Spatie Media Library Configuration

Jika ada masalah dengan media library, publish konfigurasinya:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

## Upgrade dari Versi Non-Laravel

Jika sudah menggunakan versi non-Laravel sebelumnya:

### 1. Backup Database

```bash
mysqldump -u username -p database_name > backup.sql
```

### 2. Install Package Laravel

Ikuti langkah instalasi di atas.

### 3. Migrate Data (jika diperlukan)

Struktur tabel sama, tapi jika ada perbedaan:

```php
// Migration untuk update struktur jika diperlukan
Schema::table('wf_approval_histories', function (Blueprint $table) {
    $table->timestamps(); // Tambah created_at, updated_at jika belum ada
});
```

### 4. Update Code

Ganti penggunaan `ApprovalHandler` dengan `ApprovalService`:

```php
// Lama
$handler = new ApprovalHandler($db, $companyId);
$result = $handler->start($flowType, $userId, $parameters);

// Baru
$service = app(ApprovalService::class);
$result = $service->start($flowType, $userId, $parameters);

// Atau dengan facade
use AsetKita\LaravelApprovalWorkflow\Facades\ApprovalWorkflow;
$result = ApprovalWorkflow::start($flowType, $userId, $parameters);
```

## Next Steps

Setelah instalasi berhasil:

1. Baca [README.md](README.md) untuk panduan penggunaan
2. Lihat [EXAMPLES.md](EXAMPLES.md) untuk contoh implementasi
3. Setup flow approval sesuai kebutuhan organisasi
4. Implementasi UI untuk approval workflow
5. Setup notifications dan email templates
