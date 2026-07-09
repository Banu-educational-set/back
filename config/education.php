<?php

return [
    'terms_required_for_missionary' => (int) env('TERMS_REQUIRED_FOR_MISSIONARY', 3),
    'max_exam_attempts' => (int) env('MAX_EXAM_ATTEMPTS', 3),

    // Fallback session length (minutes) used by the student dashboard's
    // "hours spent" tile when a session has no explicit duration_minutes.
    'default_session_duration_minutes' => (int) env('DEFAULT_SESSION_DURATION_MINUTES', 60),

    'external_api_key' => env('WORDPRESS_MISSIONARY_API_KEY', env('EXTERNAL_API_KEY')),

    'admin_seed' => [
        'name' => env('DEFAULT_ADMIN_NAME', 'Admin'),
        'email' => env('DEFAULT_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('DEFAULT_ADMIN_PASSWORD', 'password'),
    ],

    'media' => [
        'pending_ttl_hours' => (int) env('MEDIA_PENDING_TTL_HOURS', 24),
        // How long a signed preview URL (Media::url() for non-public disks,
        // e.g. tickets/homework/session attachments) stays valid before the
        // link 404s and needs to be re-fetched from the API.
        'signed_url_ttl_minutes' => (int) env('MEDIA_SIGNED_URL_TTL_MINUTES', 1440),
        'purposes' => [
            'avatar' => [
                'disk' => env('AVATAR_DISK', 'public'),
                'collection' => 'avatar',
                'single_file' => true,
                'max_size_kb' => 5 * 1024,
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'cover' => [
                'disk' => env('AVATAR_DISK', 'public'),
                'collection' => 'cover',
                'single_file' => true,
                'max_size_kb' => 5 * 1024,
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'video' => [
                'disk' => env('SESSION_MEDIA_DISK', 'local'),
                'collection' => 'videos',
                'single_file' => false,
                'max_size_kb' => 300 * 1024,
                'allowed_mimes' => ['mp4'],
            ],
            'voice' => [
                'disk' => env('SESSION_MEDIA_DISK', 'local'),
                'collection' => 'voices',
                'single_file' => false,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['mp3', 'wav', 'm4a'],
            ],
            'pdf' => [
                'disk' => env('SESSION_MEDIA_DISK', 'local'),
                'collection' => 'pdfs',
                'single_file' => false,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['pdf'],
            ],
            'slide' => [
                'disk' => env('SESSION_MEDIA_DISK', 'local'),
                'collection' => 'slides',
                'single_file' => false,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['pdf', 'ppt', 'pptx'],
            ],
            'attachment' => [
                'disk' => env('SESSION_MEDIA_DISK', 'local'),
                'collection' => 'attachments',
                'single_file' => false,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'],
            ],
            'homework_file' => [
                'disk' => env('HOMEWORK_DISK', 'local'),
                'collection' => 'submission_files',
                'single_file' => true,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'],
            ],
            // Missionary "memory" photos/videos. On the public disk so the
            // generated URLs are permanent and directly servable — these are
            // shown publicly in the external (WordPress) missionary detail.
            'memory' => [
                'disk' => env('MEMORY_DISK', 'public'),
                'collection' => 'memory_media',
                'single_file' => false,
                'max_size_kb' => 50 * 1024,
                'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'mp4'],
            ],
        ],
    ],

    'roles' => [
        'admin' => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
        'missionary' => 'missionary',
        'counselor' => 'counselor',
    ],
];
