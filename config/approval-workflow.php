<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Company ID
    |--------------------------------------------------------------------------
    |
    | This value is the default company ID used when creating a new approval
    | handler instance. You can override this when instantiating the handler.
    |
    */
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The User model to use for the approval workflow. This should be the
    | full class name of your User model.
    |
    */
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Media Library Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for storing media files. This should match one of the
    | disks configured in your config/filesystems.php file.
    |
    */
    'media_disk' => env('APPROVAL_WORKFLOW_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Approval Notifications
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic notifications when approval status changes.
    |
    */
    'notifications_enabled' => env('APPROVAL_WORKFLOW_NOTIFICATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | The channels to use when sending notifications. Options: mail, database, etc.
    |
    */
    'notification_channels' => ['mail', 'database'],
];
