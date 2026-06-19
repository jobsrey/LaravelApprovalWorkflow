# Changelog: Media URL in Approval Histories

## Update: Automatic Media URL in `getApprovalHistories()`

### What Changed?

The `getApprovalHistories()` method now automatically includes the URL of the last uploaded media file for each history record.

### New Field

Each history record now includes:
- **`media_url`** (string|null): URL of the last uploaded media file. Returns `null` if no media was uploaded.

### Example Usage

```php
$handler = new ApprovalHandler(auth()->user()->company_id);
$histories = $handler->getApprovalHistories($approvalId);

foreach ($histories as $history) {
    echo "Action: {$history['title']}\n";
    echo "By: {$history['user_name']}\n";
    
    // Display media URL if exists
    if ($history['media_url']) {
        echo "Attachment: {$history['media_url']}\n";
    }
}
```

### API Response Example

```json
{
  "histories": [
    {
      "id": 1,
      "approval_id": 1,
      "title": "Approved by Manager",
      "user_name": "John Doe",
      "flag": "approved",
      "notes": "Looks good",
      "media_url": "http://example.com/storage/1/document.pdf",
      "date_time": 1234567890
    },
    {
      "id": 2,
      "approval_id": 1,
      "title": "Rejected by Director",
      "user_name": "Jane Smith",
      "flag": "rejected",
      "notes": "Need revision",
      "media_url": null,
      "date_time": 1234567900
    }
  ]
}
```

### Technical Details

- The method now uses Eloquent models instead of raw DB queries to access media library relations
- Only the **last** uploaded media file URL is included (if multiple files were uploaded)
- The legacy `file` field is still included for backward compatibility but is deprecated
- Performance impact is minimal as media relations are efficiently loaded

### Migration Notes

**No breaking changes** - This is a backward-compatible addition:
- Existing code will continue to work
- The new `media_url` field is simply added to the response
- You can start using it immediately without any code changes

### Related Documentation

- [API Reference](API_REFERENCE.md#getapprovalhistories)
- [File Upload Guide](FILE_UPLOAD_GUIDE.md)
- [Usage Guide](USAGE.md#get-approval-history)
