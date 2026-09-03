<?php

return [
    'availability' => [
        'pending' => 'Pending',
        'scanning' => 'Scanning file',
        'infected' => 'Unsafe file detected',
        'queued' => 'Queued',
        'processing' => 'Processing document',
        'indexing' => 'Indexing document',
        'ready' => 'Ready',
        'failed' => 'Processing failed',
    ],

    'processing_run' => [
        'status' => [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'indexing' => 'Indexing',
            'indexed' => 'Processing completed',
            'failed' => 'Processing failed',
        ],

        'kind' => [
            'initial' => 'Initial processing',
            'reprocessing' => 'Reprocessing',
        ],

        'profile' => [
            'cloud' => 'Cloud',
            'hybrid_local' => 'Hybrid local',
        ],
    ],

    'failure' => [
        'processing_failed' => 'Document processing could not be completed. You can try again.',
    ],

    'validation' => [
        'secure_upload' => [
            'invalid_file' => 'The uploaded file could not be read or the upload is invalid.',
            'unsafe_filename' => 'The filename is unsafe. Please use a valid filename.',
            'unsupported_type' => 'The file type is not supported.',
            'content_mismatch' => 'The file content does not match its extension.',
            'inspection_failed' => 'The uploaded file could not be inspected.',
            'malformed_or_unsafe' => 'The file is malformed or unsafe and cannot be used.',
        ],
    ],
];
