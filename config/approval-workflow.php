<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Company ID
    |--------------------------------------------------------------------------
    |
    | This value is used as the default company ID when creating approvals.
    | You can override this by passing a company_id parameter to the service.
    |
    */
    'default_company_id' => env('APPROVAL_WORKFLOW_COMPANY_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model used by the approval workflow. You can change
    | this to your custom User model if needed.
    |
    */
    'user_model' => env('APPROVAL_WORKFLOW_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    |
    | Configuration for Spatie Media Library collections used in approval
    | history for file attachments.
    |
    */
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
            'text/plain'
        ],
        'max_file_size' => 10 * 1024 * 1024, // 10MB in bytes
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications sent during approval workflow.
    |
    */
    'notifications' => [
        'enabled' => env('APPROVAL_WORKFLOW_NOTIFICATIONS', true),
        'channels' => ['mail', 'database'], // Available: mail, database, slack, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | System Groups
    |--------------------------------------------------------------------------
    |
    | Configuration for system-defined user groups that can be used in
    | approval flows.
    |
    */
    'system_groups' => [
        'department-manager' => 'Department Manager',
        'department-head' => 'Department Head',
        'asset-coordinator' => 'Asset Coordinator',
        'origin-asset-user' => 'Origin Asset User',
        'destination-asset-user' => 'Destination Asset User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expression Language
    |--------------------------------------------------------------------------
    |
    | Configuration for the Symfony Expression Language used in flow step
    | conditions.
    |
    */
    'expression_language' => [
        'cache' => env('APPROVAL_WORKFLOW_EXPRESSION_CACHE', true),
    ],
];
