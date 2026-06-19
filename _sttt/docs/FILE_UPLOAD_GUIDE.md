# File Upload Guide - Automatic Media Library Integration

Sistem approval workflow sekarang mendukung **automatic file upload** ke Spatie Media Library saat approve/reject. File akan langsung tersimpan tanpa perlu mencari record history terlebih dahulu.

## 🎯 Fitur Baru

- ✅ Upload file langsung saat `approve()`
- ✅ Upload file langsung saat `reject()`
- ✅ Upload file langsung saat `reset()`
- ✅ Upload file langsung saat `rejectBySystem()`
- ✅ Support single file
- ✅ Support multiple files
- ✅ Otomatis tersimpan di Media Library collection 'attachments'

## 📝 Cara Penggunaan

### 1. Single File Upload

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler(1);

// Approve dengan file attachment
$result = $handler->approve(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Approved with supporting document',
    file: $request->file('attachment') // UploadedFile instance
);

// File langsung tersimpan di Media Library!
```

### 2. Multiple Files Upload

```php
// Approve dengan multiple files
$result = $handler->approve(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Approved with multiple documents',
    file: $request->file('attachments') // Array of UploadedFile
);

// Semua file langsung tersimpan!
```

### 3. Reject dengan File

```php
// Reject dengan file attachment
$result = $handler->reject(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Rejected, please see attached feedback',
    file: $request->file('feedback_document')
);
```

### 4. Reset dengan File

```php
// Reset dengan file bukti koreksi
$result = $handler->reset(
    approvalId: $approvalId,
    userId: auth()->id(),
    notes: 'Resubmitting with corrections',
    file: $request->file('corrected_document'),
    parameters: ['amount' => 4000] // Updated parameters
);
```

## 🎨 Contoh Controller

### Basic Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalController extends Controller
{
    public function approve(Request $request, $approvalId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ]);

        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->approve(
                $approvalId,
                auth()->id(),
                $validated['notes'] ?? null,
                $request->file('attachment') // Langsung pass UploadedFile
            );

            return response()->json([
                'success' => true,
                'message' => 'Approved successfully',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function reject(Request $request, $approvalId)
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:5',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $handler = new ApprovalHandler(auth()->user()->company_id);

        try {
            $result = $handler->reject(
                $approvalId,
                auth()->id(),
                $validated['notes'],
                $request->file('attachments') // Multiple files
            );

            return response()->json([
                'success' => true,
                'message' => 'Rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

### With File Type Validation

```php
public function approve(Request $request, $approvalId)
{
    $validated = $request->validate([
        'notes' => 'nullable|string',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
    ]);

    $handler = new ApprovalHandler(auth()->user()->company_id);

    $result = $handler->approve(
        $approvalId,
        auth()->id(),
        $validated['notes'] ?? null,
        $request->file('attachment')
    );

    return response()->json(['success' => true]);
}
```

### With Multiple Files and Size Limit

```php
public function approveWithDocuments(Request $request, $approvalId)
{
    $validated = $request->validate([
        'notes' => 'required|string',
        'documents' => 'required|array|min:1|max:5', // Max 5 files
        'documents.*' => 'file|mimes:pdf,doc,docx|max:5120', // Max 5MB each
    ]);

    $handler = new ApprovalHandler(auth()->user()->company_id);

    $result = $handler->approve(
        $approvalId,
        auth()->id(),
        $validated['notes'],
        $request->file('documents') // Array of files
    );

    return response()->json([
        'success' => true,
        'message' => 'Approved with ' . count($request->file('documents')) . ' documents',
    ]);
}
```

## 📤 Form HTML Example

### Single File Upload

```html
<form action="/approvals/{{ $approvalId }}/approve" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>
    
    <div class="form-group">
        <label>Attachment (Optional)</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
    </div>
    
    <button type="submit" class="btn btn-success">Approve</button>
</form>
```

### Multiple Files Upload

```html
<form action="/approvals/{{ $approvalId }}/approve" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>
    
    <div class="form-group">
        <label>Attachments (Optional, max 5 files)</label>
        <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx">
        <small class="text-muted">You can select multiple files (max 5MB each)</small>
    </div>
    
    <button type="submit" class="btn btn-success">Approve</button>
</form>
```

## 🔍 Mengakses File yang Sudah Diupload

### Get Files dari History

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

// Get history record
$history = ApprovalHistory::find($historyId);

// Get all attachments
$attachments = $history->getMedia('attachments');

foreach ($attachments as $media) {
    echo $media->file_name; // Original filename
    echo $media->getUrl(); // Full URL
    echo $media->getPath(); // File path
    echo $media->size; // File size in bytes
    echo $media->mime_type; // MIME type
}
```

### Display Files in Blade

```blade
@php
    $history = \AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory::find($historyId);
    $attachments = $history->getMedia('attachments');
@endphp

@if($attachments->count() > 0)
    <div class="attachments">
        <h5>Attachments:</h5>
        <ul>
            @foreach($attachments as $media)
                <li>
                    <a href="{{ $media->getUrl() }}" target="_blank">
                        {{ $media->file_name }} ({{ $media->human_readable_size }})
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
```

### Get Attachment URLs in API

```php
public function getHistory($approvalId)
{
    $handler = new ApprovalHandler(auth()->user()->company_id);
    $histories = $handler->getApprovalHistories($approvalId);

    // The media_url field is automatically included for each history
    // It contains the URL of the last uploaded media file
    return response()->json(['histories' => $histories]);
}
```

**Note:** Since version with media library integration, `getApprovalHistories()` automatically includes the `media_url` field containing the URL of the last uploaded media file for each history record.

#### Get All Attachments (Optional)

If you need to get all attachments (not just the last one), you can manually enrich the data:

```php
public function getHistoryWithAllAttachments($approvalId)
{
    $handler = new ApprovalHandler(auth()->user()->company_id);
    $histories = $handler->getApprovalHistories($approvalId);

    // Enrich histories with all attachment URLs
    foreach ($histories as &$history) {
        $historyModel = ApprovalHistory::find($history['id']);
        $history['attachments'] = $historyModel->getMedia('attachments')->map(function($media) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
            ];
        });
    }

    return response()->json(['histories' => $histories]);
}
```

## 🎯 Livewire Example

```php
namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

class ApprovalCard extends Component
{
    use WithFileUploads;

    public $approvalId;
    public $notes = '';
    public $attachment;
    public $attachments = [];

    public function approve()
    {
        $this->validate([
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            
            // Pass Livewire's TemporaryUploadedFile (compatible with UploadedFile)
            $handler->approve(
                $this->approvalId,
                auth()->id(),
                $this->notes,
                $this->attachment
            );

            session()->flash('message', 'Approved successfully!');
            $this->reset(['notes', 'attachment']);
            $this->emit('approvalUpdated');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function approveWithMultipleFiles()
    {
        $this->validate([
            'notes' => 'required|string',
            'attachments.*' => 'file|max:5120',
        ]);

        try {
            $handler = new ApprovalHandler(auth()->user()->company_id);
            
            $handler->approve(
                $this->approvalId,
                auth()->id(),
                $this->notes,
                $this->attachments // Array of files
            );

            session()->flash('message', 'Approved with ' . count($this->attachments) . ' files!');
            $this->reset(['notes', 'attachments']);
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

## ⚙️ Konfigurasi Media Library

### Ubah Disk Storage (Optional)

Edit `config/approval-workflow.php`:

```php
return [
    'media_disk' => env('APPROVAL_WORKFLOW_MEDIA_DISK', 'public'),
];
```

Atau set di `.env`:

```env
APPROVAL_WORKFLOW_MEDIA_DISK=s3
```

### Custom Media Collection Name (Advanced)

Jika ingin mengubah nama collection dari 'attachments', edit model `ApprovalHistory`:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('approval_documents') // Custom name
        ->useDisk(config('approval-workflow.media_disk', 'public'));
}
```

## 🔒 Security Best Practices

### 1. Validate File Types

```php
$request->validate([
    'attachment' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
]);
```

### 2. Validate File Size

```php
$request->validate([
    'attachment' => 'file|max:5120', // Max 5MB
]);
```

### 3. Limit Number of Files

```php
$request->validate([
    'attachments' => 'array|max:5', // Max 5 files
    'attachments.*' => 'file|max:10240',
]);
```

### 4. Scan for Malware (Optional)

```php
use Illuminate\Support\Facades\Storage;

$file = $request->file('attachment');

// Basic validation
if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
    throw new \Exception('File too large');
}

// Check MIME type
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($file->getMimeType(), $allowedMimes)) {
    throw new \Exception('Invalid file type');
}

// Then upload
$handler->approve($approvalId, $userId, $notes, $file);
```

## 📊 File Information

Saat file diupload, Spatie Media Library akan menyimpan:

- `file_name` - Original filename
- `mime_type` - File MIME type
- `size` - File size in bytes
- `disk` - Storage disk used
- `collection_name` - 'attachments'
- `custom_properties` - Additional metadata
- Generated conversions (if configured)

## 🎉 Benefits

✅ **Automatic** - File langsung tersimpan saat create history
✅ **No Extra Steps** - Tidak perlu find record lalu upload
✅ **Clean Code** - Satu function call saja
✅ **Type Safe** - Using UploadedFile type
✅ **Flexible** - Support single & multiple files
✅ **Media Library** - Full Spatie Media Library features

## ⚠️ Important Notes

1. **UploadedFile Required** - Parameter file harus `Illuminate\Http\UploadedFile` instance atau array
2. **Media Library Required** - Pastikan Spatie Media Library sudah terinstall
3. **Storage Configured** - Pastikan disk storage sudah dikonfigurasi
4. **Automatic Storage** - File akan otomatis tersimpan saat create history
5. **Multiple Collections** - Semua file tersimpan di collection 'attachments'

## 🔄 Migration dari String Path

### Sebelumnya (Versi Lama)
```php
// Old way - manual string path
$handler->approve($approvalId, $userId, $notes, '/path/to/file.pdf');
```

### Sekarang (Versi Baru)
```php
// New way - UploadedFile instance
$handler->approve($approvalId, $userId, $notes, $request->file('attachment'));
```

### Backward Compatibility

Jika Anda masih punya code lama yang pass string path, Anda perlu update ke UploadedFile:

```php
// Convert string path to UploadedFile (if needed)
use Illuminate\Http\UploadedFile;

$path = '/path/to/file.pdf';
$file = new UploadedFile($path, basename($path), mime_content_type($path), null, true);

$handler->approve($approvalId, $userId, $notes, $file);
```

## 📝 Summary

Fitur baru ini membuat upload file menjadi **jauh lebih mudah dan otomatis**:

- ✅ Pass `$request->file('attachment')` langsung ke method
- ✅ File otomatis tersimpan di Media Library
- ✅ Support single & multiple files
- ✅ Tidak perlu cari record dulu
- ✅ Clean & simple code

**Happy coding!** 🚀
