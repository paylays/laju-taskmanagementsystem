<?php

// Allowed uploads by category. MIME types are sniffed from file contents, and the
// stored file's extension comes from this map, never from the client's file name.
return [
    'disk' => env('ATTACHMENT_DISK', 'local'),

    'types' => [
        'image' => [
            'max_kb' => 10 * 1024,
            'mimes' => [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ],
        ],
        'document' => [
            'max_kb' => 20 * 1024,
            'mimes' => [
                'application/pdf' => 'pdf',
                'text/plain' => 'txt',
                'text/csv' => 'csv',
                'application/msword' => 'doc',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            ],
        ],
        'video' => [
            'max_kb' => 50 * 1024,
            'mimes' => [
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/quicktime' => 'mov',
            ],
        ],
    ],

    'thumbnail_size' => 320,

    // Artificial delay for the simulated virus scan, so the "pending" state is visible in the UI.
    'scan_delay_ms' => (int) env('ATTACHMENT_SCAN_DELAY_MS', 1500),

    // Treated like EICAR, which Windows Defender quarantines before it can be uploaded.
    'scan_test_marker' => env('ATTACHMENT_SCAN_TEST_MARKER', 'LAJUR-SIMULATED-VIRUS'),
];
