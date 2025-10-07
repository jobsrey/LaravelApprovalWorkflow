# Installation Guide - Laravel Approval Workflow

## Prerequisites

- PHP 8.2 or higher
- Laravel 11.0 or 12.0 or higher
- MySQL 5.7+ or PostgreSQL 9.6+
- Composer

## Step-by-Step Installation

### 1. Install Package via Composer

```bash
composer require asetkita/laravel-approval-workflow
```

### 2. Publish Configuration Files

```bash
php artisan vendor:publish --provider="AsetKita\LaravelApprovalWorkflow\ApprovalWorkflowServiceProvider"
```

This will publish:
- `config/approval-workflow.php` - Configuration file
- `database/migrations/*` - Migration files

### 3. Configure Environment Variables (Optional)

Add to your `.env` file:

```env
APPROVAL_WORKFLOW_COMPANY_ID=1
APPROVAL_WORKFLOW_USER_MODEL=App\Models\User
APPROVAL_WORKFLOW_MEDIA_DISK=public
APPROVAL_WORKFLOW_NOTIFICATIONS=false
```

### 4. Install Spatie Media Library

The package requires Spatie Media Library for file attachments.

```bash
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

### 5. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
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

### 6. Configure Storage (For Media Library)

Make sure your storage is properly configured:

```bash
php artisan storage:link
```

## Configuration

### Edit `config/approval-workflow.php`

```php
return [
    // Default company ID for approvals
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),
    
    // Your User model
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', \App\Models\User::class),
    
    // Disk for media storage
    'media_disk' => env('APPROVAL_WORKFLOW_MEDIA_DISK', 'public'),
    
    // Enable notifications
    'notifications_enabled' => env('APPROVAL_WORKFLOW_NOTIFICATIONS', false),
    
    // Notification channels
    'notification_channels' => ['mail', 'database'],
];
```

### User Model Requirements

Your User model should have:
- `id` field (primary key)
- `name` field (optional but recommended)
- `email` field (optional but recommended)

No modifications needed to your existing User model!

## Verification

### Test Installation

Create a simple test route in `routes/web.php`:

```php
use AsetKita\LaravelApprovalWorkflow\Services\ApprovalHandler;

Route::get('/test-approval', function () {
    try {
        $handler = new ApprovalHandler(1);
        return response()->json([
            'status' => 'success',
            'message' => 'Approval Workflow installed successfully!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});
```

Visit: `http://your-app.test/test-approval`

## Next Steps

1. **Create Your First Flow** - See [USAGE.md](USAGE.md)
2. **Set Up Department Users** - Configure department structure
3. **Create Approver Groups** - Set up custom approver groups
4. **Test the Workflow** - Run a test approval

## Troubleshooting

### Migration Issues

If migrations fail, check:
- Database connection in `.env`
- User has CREATE TABLE privileges
- No existing tables with same names

### User Model Issues

If you get user model errors:
- Verify `user_model` in config points to correct class
- Ensure User model exists at specified path

### Media Library Issues

If file uploads fail:
- Run `php artisan storage:link`
- Check disk permissions
- Verify `filesystems.php` configuration

## Uninstallation

To remove the package:

```bash
# 1. Remove migrations (optional - will delete all approval data!)
php artisan migrate:rollback

# 2. Remove config
rm config/approval-workflow.php

# 3. Remove package
composer remove asetkita/laravel-approval-workflow
```

## Support

For issues, please check:
- [README.md](README.md) - Full documentation
- [USAGE.md](USAGE.md) - Usage examples
- GitHub Issues
