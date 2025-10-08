# Testing Media URL Feature

This guide helps you test the new `media_url` field in approval histories.

## Manual Testing

### 1. Create Approval with File Upload

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use Illuminate\Http\UploadedFile;

// Create a test file
$file = UploadedFile::fake()->create('test-document.pdf', 100);

// Start approval
$handler = new ApprovalHandler(1); // company_id = 1
$approval = $handler->start('PR', 1, [
    'amount' => 5000,
    'description' => 'Test purchase request'
]);

// Approve with file
$handler->approve($approval['id'], 2, 'Approved by manager', $file);
```

### 2. Verify Media URL in History

```php
// Get histories
$histories = $handler->getApprovalHistories($approval['id']);

// Check the last history
$lastHistory = end($histories);

// Verify media_url exists
if ($lastHistory['media_url']) {
    echo "✓ Media URL found: " . $lastHistory['media_url'] . "\n";
} else {
    echo "✗ Media URL not found\n";
}

// Verify URL is accessible
$headers = get_headers($lastHistory['media_url']);
if (strpos($headers[0], '200') !== false) {
    echo "✓ Media file is accessible\n";
} else {
    echo "✗ Media file is not accessible\n";
}
```

### 3. Test Multiple Files

```php
// Upload multiple files
$files = [
    UploadedFile::fake()->create('document1.pdf', 100),
    UploadedFile::fake()->create('document2.pdf', 100),
    UploadedFile::fake()->create('document3.pdf', 100),
];

$handler->approve($approval['id'], 3, 'Approved by director', $files);

// Get histories
$histories = $handler->getApprovalHistories($approval['id']);
$lastHistory = end($histories);

// Should return the last uploaded file URL
echo "Last media URL: " . $lastHistory['media_url'] . "\n";
```

### 4. Test History Without File

```php
// Approve without file
$handler->approve($approval['id'], 4, 'Final approval', null);

// Get histories
$histories = $handler->getApprovalHistories($approval['id']);
$lastHistory = end($histories);

// Should return null
if ($lastHistory['media_url'] === null) {
    echo "✓ Media URL is null when no file uploaded\n";
} else {
    echo "✗ Expected null but got: " . $lastHistory['media_url'] . "\n";
}
```

## Unit Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

class ApprovalHistoryMediaUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test environment
        Storage::fake('public');
        
        // Run migrations
        $this->artisan('migrate');
        
        // Seed test data
        $this->seed();
    }

    /** @test */
    public function it_includes_media_url_in_approval_histories()
    {
        // Arrange
        $handler = new ApprovalHandler(1);
        $file = UploadedFile::fake()->create('test.pdf', 100);
        
        // Act
        $approval = $handler->start('PR', 1, ['amount' => 5000]);
        $handler->approve($approval['id'], 2, 'Approved', $file);
        
        // Assert
        $histories = $handler->getApprovalHistories($approval['id']);
        $lastHistory = end($histories);
        
        $this->assertArrayHasKey('media_url', $lastHistory);
        $this->assertNotNull($lastHistory['media_url']);
        $this->assertStringContainsString('test.pdf', $lastHistory['media_url']);
    }

    /** @test */
    public function it_returns_null_media_url_when_no_file_uploaded()
    {
        // Arrange
        $handler = new ApprovalHandler(1);
        
        // Act
        $approval = $handler->start('PR', 1, ['amount' => 5000]);
        $handler->approve($approval['id'], 2, 'Approved', null);
        
        // Assert
        $histories = $handler->getApprovalHistories($approval['id']);
        $lastHistory = end($histories);
        
        $this->assertArrayHasKey('media_url', $lastHistory);
        $this->assertNull($lastHistory['media_url']);
    }

    /** @test */
    public function it_returns_last_media_url_when_multiple_files_uploaded()
    {
        // Arrange
        $handler = new ApprovalHandler(1);
        $files = [
            UploadedFile::fake()->create('file1.pdf', 100),
            UploadedFile::fake()->create('file2.pdf', 100),
            UploadedFile::fake()->create('file3.pdf', 100),
        ];
        
        // Act
        $approval = $handler->start('PR', 1, ['amount' => 5000]);
        $handler->approve($approval['id'], 2, 'Approved', $files);
        
        // Assert
        $histories = $handler->getApprovalHistories($approval['id']);
        $lastHistory = end($histories);
        
        $this->assertArrayHasKey('media_url', $lastHistory);
        $this->assertNotNull($lastHistory['media_url']);
        
        // Should contain the last file name
        $this->assertStringContainsString('file3.pdf', $lastHistory['media_url']);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_existing_fields()
    {
        // Arrange
        $handler = new ApprovalHandler(1);
        
        // Act
        $approval = $handler->start('PR', 1, ['amount' => 5000]);
        $histories = $handler->getApprovalHistories($approval['id']);
        
        // Assert - Check all expected fields exist
        $history = $histories[0];
        
        $this->assertArrayHasKey('id', $history);
        $this->assertArrayHasKey('approval_id', $history);
        $this->assertArrayHasKey('flow_step_id', $history);
        $this->assertArrayHasKey('user_id', $history);
        $this->assertArrayHasKey('user_name', $history);
        $this->assertArrayHasKey('user_email', $history);
        $this->assertArrayHasKey('title', $history);
        $this->assertArrayHasKey('flag', $history);
        $this->assertArrayHasKey('notes', $history);
        $this->assertArrayHasKey('file', $history);
        $this->assertArrayHasKey('date_time', $history);
        $this->assertArrayHasKey('media_url', $history); // New field
    }

    /** @test */
    public function media_url_is_accessible_via_http()
    {
        // Arrange
        $handler = new ApprovalHandler(1);
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        
        // Act
        $approval = $handler->start('PR', 1, ['amount' => 5000]);
        $handler->approve($approval['id'], 2, 'Approved', $file);
        
        // Assert
        $histories = $handler->getApprovalHistories($approval['id']);
        $lastHistory = end($histories);
        
        // Check URL format
        $this->assertStringStartsWith('http', $lastHistory['media_url']);
        
        // Verify file exists in storage
        $historyModel = ApprovalHistory::find($lastHistory['id']);
        $media = $historyModel->getMedia('attachments')->last();
        
        $this->assertTrue($media->exists());
        $this->assertEquals('test.pdf', $media->file_name);
    }
}
```

## Integration Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class ApprovalHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_returns_media_url_in_history_response()
    {
        // Arrange
        $this->actingAs($this->createUser());
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        // Create approval
        $response = $this->postJson('/api/approvals', [
            'type' => 'PR',
            'user_id' => 1,
            'parameters' => ['amount' => 5000]
        ]);
        
        $approvalId = $response->json('data.id');
        
        // Approve with file
        $this->postJson("/api/approvals/{$approvalId}/approve", [
            'user_id' => 2,
            'notes' => 'Approved',
            'file' => $file
        ]);
        
        // Act - Get history
        $response = $this->getJson("/api/approvals/{$approvalId}/history");
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'user_name',
                    'flag',
                    'notes',
                    'date_time',
                    'media_url' // New field
                ]
            ]
        ]);
        
        // Check last history has media_url
        $histories = $response->json('data');
        $lastHistory = end($histories);
        
        $this->assertNotNull($lastHistory['media_url']);
        $this->assertStringContainsString('document.pdf', $lastHistory['media_url']);
    }
}
```

## Browser Test (Laravel Dusk)

```php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ApprovalHistoryMediaTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_see_and_download_attachment_in_history()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createUser())
                    ->visit('/approvals/1/history')
                    ->assertSee('Approval History')
                    ->assertSee('Download Attachment')
                    ->click('@download-attachment')
                    ->pause(1000);
            
            // Verify download initiated
            // Note: Actual file download verification depends on your setup
        });
    }
}
```

## Postman/API Testing

### Request: Get Approval History

```
GET /api/approvals/{approvalId}/history
Authorization: Bearer {token}
```

### Expected Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "approval_id": 123,
      "flow_step_id": 1,
      "flow_step_name": "Manager Approval",
      "user_id": 5,
      "user_name": "John Doe",
      "user_email": "john@example.com",
      "title": "Approved by Manager",
      "flag": "approved",
      "notes": "Budget approved",
      "file": null,
      "date_time": 1696752000,
      "media_url": "http://localhost/storage/1/approval-document.pdf"
    }
  ]
}
```

### Test Cases

1. **Test with file upload**
   - Upload file during approval
   - Verify `media_url` is not null
   - Verify URL is accessible

2. **Test without file upload**
   - Approve without file
   - Verify `media_url` is null

3. **Test with multiple files**
   - Upload multiple files
   - Verify `media_url` contains last file URL

4. **Test URL accessibility**
   - Copy `media_url` from response
   - Open in browser
   - Verify file downloads correctly

## Debugging Tips

### Check if Media Library is Working

```php
use AsetKita\LaravelApprovalWorkflow\Models\ApprovalHistory;

$history = ApprovalHistory::find(1);

// Check if media exists
$media = $history->getMedia('attachments');
dd($media->count()); // Should be > 0 if files uploaded

// Get last media
$lastMedia = $media->last();
dd($lastMedia->getUrl()); // Should return URL
```

### Check Database

```sql
-- Check approval histories
SELECT * FROM wf_approval_histories;

-- Check media library
SELECT * FROM media WHERE model_type = 'AsetKita\\LaravelApprovalWorkflow\\Models\\ApprovalHistory';
```

### Check Storage

```php
use Illuminate\Support\Facades\Storage;

// List all files in media storage
$files = Storage::disk('public')->allFiles();
dd($files);
```

## Common Issues

### Issue 1: media_url returns null even with file upload

**Solution:**
- Check if Spatie Media Library is properly installed
- Verify `media` table exists
- Check storage disk configuration in `config/approval-workflow.php`

### Issue 2: media_url returns broken link

**Solution:**
- Run `php artisan storage:link`
- Check `APP_URL` in `.env`
- Verify file permissions on storage directory

### Issue 3: Multiple files but only first URL shown

**Solution:**
- This is expected behavior - only last file URL is shown
- To get all files, manually query the media collection

## Performance Testing

```php
// Test with large number of histories
$handler = new ApprovalHandler(1);

// Create 100 approval histories
for ($i = 0; $i < 100; $i++) {
    $approval = $handler->start('PR', 1, ['amount' => 5000]);
    $handler->approve($approval['id'], 2, "Approval $i", null);
}

// Measure performance
$start = microtime(true);
$histories = $handler->getApprovalHistories($approval['id']);
$end = microtime(true);

echo "Time taken: " . ($end - $start) . " seconds\n";
echo "Memory used: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB\n";
```
