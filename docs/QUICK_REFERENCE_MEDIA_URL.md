# Quick Reference: Media URL in Approval Histories

## 🚀 Quick Start

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

$handler = new ApprovalHandler(auth()->user()->company_id);
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    // Access media URL
    if ($history['media_url']) {
        echo "File: " . $history['media_url'];
    }
}
```

## 📋 Field Reference

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `media_url` | `string\|null` | URL of last uploaded media file | `"http://example.com/storage/1/file.pdf"` |

## 🎯 Common Use Cases

### 1. Display Download Link

```blade
@if($history['media_url'])
    <a href="{{ $history['media_url'] }}" target="_blank">
        Download Attachment
    </a>
@endif
```

### 2. API Response

```php
return response()->json([
    'histories' => $histories // media_url included automatically
]);
```

### 3. Check if File Exists

```php
if ($history['media_url']) {
    // File was uploaded
} else {
    // No file uploaded
}
```

### 4. Download File

```php
if ($history['media_url']) {
    return redirect($history['media_url']);
}
```

### 5. Display Image

```blade
@if($history['media_url'])
    <img src="{{ $history['media_url'] }}" alt="Attachment">
@endif
```

## 🔧 Advanced Usage

### Get All Attachments (Not Just Last)

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$historyModel = ApprovalHistory::find($history['id']);
$allMedia = $historyModel->getMedia('attachments');

foreach ($allMedia as $media) {
    echo $media->getUrl() . "\n";
}
```

### Check File Type

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$historyModel = ApprovalHistory::find($history['id']);
$lastMedia = $historyModel->getMedia('attachments')->last();

if ($lastMedia) {
    echo $lastMedia->mime_type; // e.g., "application/pdf"
    echo $lastMedia->file_name; // e.g., "document.pdf"
    echo $lastMedia->size; // in bytes
}
```

### Generate Thumbnail (for images)

```php
$historyModel = ApprovalHistory::find($history['id']);
$lastMedia = $historyModel->getMedia('attachments')->last();

if ($lastMedia && str_starts_with($lastMedia->mime_type, 'image/')) {
    $thumbnailUrl = $lastMedia->getUrl('thumb'); // if conversion defined
}
```

## 💡 Tips & Tricks

### 1. Null Safety

Always check if `media_url` exists before using:

```php
$url = $history['media_url'] ?? 'No attachment';
```

### 2. Frontend Display

```javascript
// Vue.js
<a v-if="history.media_url" :href="history.media_url">Download</a>

// React
{history.media_url && <a href={history.media_url}>Download</a>}
```

### 3. Conditional Styling

```blade
<div class="history-item {{ $history['media_url'] ? 'has-attachment' : '' }}">
    <!-- content -->
</div>
```

### 4. File Icon Based on Type

```php
function getFileIcon($mediaUrl) {
    $extension = pathinfo($mediaUrl, PATHINFO_EXTENSION);
    
    return match($extension) {
        'pdf' => 'fa-file-pdf',
        'doc', 'docx' => 'fa-file-word',
        'xls', 'xlsx' => 'fa-file-excel',
        'jpg', 'jpeg', 'png' => 'fa-file-image',
        default => 'fa-file'
    };
}
```

## 🎨 UI Examples

### Bootstrap Card

```blade
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $history['title'] }}</h5>
        <p class="card-text">{{ $history['notes'] }}</p>
        
        @if($history['media_url'])
            <a href="{{ $history['media_url'] }}" class="btn btn-primary btn-sm">
                <i class="fas fa-download"></i> Download
            </a>
        @endif
    </div>
</div>
```

### Tailwind CSS

```blade
<div class="bg-white rounded-lg shadow p-4">
    <h3 class="font-semibold text-lg">{{ $history['title'] }}</h3>
    <p class="text-gray-600 text-sm">{{ $history['notes'] }}</p>
    
    @if($history['media_url'])
        <a href="{{ $history['media_url'] }}" 
           class="mt-2 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Download File
        </a>
    @endif
</div>
```

## 🔍 Debugging

### Check if Media Exists

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find($historyId);
dd([
    'media_count' => $history->getMedia('attachments')->count(),
    'last_media' => $history->getMedia('attachments')->last(),
    'media_url' => $history->getMedia('attachments')->last()?->getUrl()
]);
```

### Verify URL Accessibility

```php
$url = $history['media_url'];
$headers = @get_headers($url);

if ($headers && strpos($headers[0], '200')) {
    echo "✓ URL is accessible";
} else {
    echo "✗ URL is not accessible";
}
```

## ⚠️ Common Mistakes

### ❌ Wrong

```php
// Don't assume media_url always exists
echo $history['media_url']; // May be null!
```

### ✅ Correct

```php
// Always check first
if ($history['media_url']) {
    echo $history['media_url'];
}

// Or use null coalescing
echo $history['media_url'] ?? 'No file';
```

### ❌ Wrong

```php
// Don't hardcode storage path
echo "/storage/files/" . $history['file'];
```

### ✅ Correct

```php
// Use the provided media_url
echo $history['media_url'];
```

## 📊 Response Examples

### Single History

```json
{
  "id": 1,
  "title": "Approved by Manager",
  "user_name": "John Doe",
  "flag": "approved",
  "notes": "Looks good",
  "media_url": "http://example.com/storage/1/document.pdf",
  "date_time": 1696752000
}
```

### Multiple Histories

```json
[
  {
    "id": 1,
    "title": "Created",
    "media_url": null
  },
  {
    "id": 2,
    "title": "Approved by Manager",
    "media_url": "http://example.com/storage/1/approval.pdf"
  },
  {
    "id": 3,
    "title": "Approved by Director",
    "media_url": "http://example.com/storage/2/final-approval.pdf"
  }
]
```

## 🔗 Quick Links

- [Full Documentation](API_REFERENCE.md#getapprovalhistories)
- [Examples](EXAMPLES_MEDIA_URL.md)
- [Testing Guide](TESTING_MEDIA_URL.md)
- [Changelog](CHANGELOG_MEDIA_URL.md)

## 📞 Support

If you encounter issues:

1. Check [Troubleshooting Guide](../MEDIA_URL_IMPLEMENTATION_SUMMARY.md#-troubleshooting)
2. Verify storage configuration
3. Run `php artisan storage:link`
4. Check file permissions

---

**Last Updated:** 2025-10-08
**Version:** 1.0.0
