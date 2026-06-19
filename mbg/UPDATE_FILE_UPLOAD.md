# Update: Automatic File Upload Feature

## 🎉 Fitur Baru - Automatic Media Library Upload

Package telah diupdate dengan fitur **automatic file upload** yang membuat proses upload file jauh lebih mudah!

## 📋 Apa yang Berubah?

### Sebelumnya (Manual)

```php
// 1. Approve dulu
$handler->approve($approvalId, $userId, 'Approved', null);

// 2. Cari history record
$histories = $handler->getApprovalHistories($approvalId);
$lastHistory = end($histories);
$history = ApprovalHistory::find($lastHistory['id']);

// 3. Baru upload file
$history->addMedia($request->file('attachment'))
    ->toMediaCollection('attachments');
```

**3 langkah, ribet! 😓**

### Sekarang (Automatic) ✨

```php
// Langsung approve dengan file - otomatis tersimpan!
$handler->approve(
    $approvalId, 
    $userId, 
    'Approved', 
    $request->file('attachment')
);
```

**1 langkah, simpel! 🚀**

## ✅ Changes Made

### 1. Updated `ApprovalHistoryRepository.php`

```php
// Parameter file sekarang accept UploadedFile atau array
public function insert(
    int $approvalId,
    ?int $flowStepId,
    ?int $userId,
    string $title,
    string $flag,
    ?string $notes,
    UploadedFile|array|null $file // ← Changed!
): ApprovalHistory
```

**Fitur:**
- ✅ Support single file (`UploadedFile` instance)
- ✅ Support multiple files (array of `UploadedFile`)
- ✅ Otomatis upload ke Media Library saat create
- ✅ Tidak perlu cari record dulu

### 2. Updated `ApprovalHandler.php`

All methods yang ada parameter file sekarang accept `UploadedFile`:

```php
// approve() - Updated
public function approve(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    UploadedFile|array|null $file = null // ← Changed!
): array

// reject() - Updated  
public function reject(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    UploadedFile|array|null $file = null // ← Changed!
): array

// rejectBySystem() - Updated
public function rejectBySystem(
    int $approvalId, 
    int $relatedUserId, 
    ?string $notes = null, 
    UploadedFile|array|null $file = null // ← Changed!
): array

// reset() - Updated
public function reset(
    int $approvalId, 
    int $userId, 
    ?string $notes = null, 
    UploadedFile|array|null $file = null, // ← Changed!
    ?array $parameters = null
): array
```

## 💡 Cara Penggunaan

### Controller Example

```php
use Illuminate\Http\Request;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

public function approve(Request $request, $approvalId)
{
    $validated = $request->validate([
        'notes' => 'nullable|string',
        'attachment' => 'nullable|file|max:10240',
    ]);

    $handler = new ApprovalHandler(auth()->user()->company_id);

    // Pass file langsung - otomatis upload!
    $result = $handler->approve(
        $approvalId,
        auth()->id(),
        $validated['notes'] ?? null,
        $request->file('attachment') // ← Magic happens here!
    );

    return response()->json(['success' => true]);
}
```

### Multiple Files

```php
public function approveMultiple(Request $request, $approvalId)
{
    $validated = $request->validate([
        'notes' => 'required|string',
        'documents' => 'required|array',
        'documents.*' => 'file|max:5120',
    ]);

    $handler = new ApprovalHandler(auth()->user()->company_id);

    // Pass array of files - semua otomatis upload!
    $result = $handler->approve(
        $approvalId,
        auth()->id(),
        $validated['notes'],
        $request->file('documents') // ← Array of files
    );

    return response()->json([
        'success' => true,
        'files_uploaded' => count($request->file('documents'))
    ]);
}
```

## 📚 Documentation

Dokumentasi lengkap tersedia di:
- **FILE_UPLOAD_GUIDE.md** - Complete guide dengan contoh lengkap
- **README.md** - Updated dengan fitur baru
- **EXAMPLES.md** - Code examples

## 🔄 Migration Guide

### Jika Anda Sudah Pakai Package Ini

#### Old Code (String Path)
```php
// Cara lama dengan string path
$handler->approve($approvalId, $userId, $notes, '/path/to/file.pdf');
```

#### New Code (UploadedFile)
```php
// Cara baru dengan UploadedFile
$handler->approve($approvalId, $userId, $notes, $request->file('attachment'));
```

### Update Your Controllers

**Before:**
```php
public function approve(Request $request, $approvalId)
{
    $handler->approve($approvalId, auth()->id(), $request->notes, null);
    
    if ($request->hasFile('attachment')) {
        // Manual upload code...
        $histories = $handler->getApprovalHistories($approvalId);
        // ... more code
    }
}
```

**After:**
```php
public function approve(Request $request, $approvalId)
{
    $handler->approve(
        $approvalId, 
        auth()->id(), 
        $request->notes, 
        $request->file('attachment') // Simple!
    );
}
```

## 🎯 Benefits

### ✅ Advantages

1. **Simpler Code** - 1 step instead of 3
2. **Automatic** - No need to find history record
3. **Type Safe** - Using UploadedFile type
4. **Flexible** - Support single & multiple files
5. **Clean** - Less code to maintain
6. **Fast** - Instant upload on create

### 📊 Code Reduction

- **Before:** ~15 lines of code
- **After:** ~5 lines of code
- **Reduction:** ~66% less code! 🎉

## ⚙️ Technical Details

### How It Works

1. `approve()` dipanggil dengan `UploadedFile` parameter
2. `ApprovalHistoryRepository::insert()` menerima file
3. History record dibuat di database
4. Jika ada file:
   - Loop files jika array
   - Upload ke Media Library collection 'attachments'
   - Spatie Media Library handle storage
5. Return history dengan relations

### File Storage

- **Collection:** `attachments`
- **Disk:** Configurable via `config/approval-workflow.php`
- **Default:** `public` disk
- **Supported:** All Laravel filesystem disks (local, s3, etc.)

### Media Information Saved

- Original filename
- MIME type
- File size
- Disk location
- Custom properties
- Timestamps

## 🔒 Security

Remember to validate files:

```php
$request->validate([
    'attachment' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
]);
```

## 🚀 Ready to Use!

Feature ini sudah siap digunakan. Update controller Anda dan nikmati kemudahan upload file!

**Questions?** Check **FILE_UPLOAD_GUIDE.md** untuk contoh lengkap.

---

**Happy coding!** 🎉
