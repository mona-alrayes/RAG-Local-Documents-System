<?php

return [
    'availability' => [
        'pending' => 'قيد الانتظار',
        'scanning' => 'جارٍ فحص الملف',
        'infected' => 'تم اكتشاف ملف غير آمن',
        'queued' => 'في قائمة الانتظار',
        'processing' => 'جارٍ معالجة الوثيقة',
        'indexing' => 'جارٍ فهرسة الوثيقة',
        'ready' => 'جاهزة',
        'failed' => 'فشلت المعالجة',
    ],

    'processing_run' => [
        'status' => [
            'pending' => 'قيد الانتظار',
            'processing' => 'جارٍ المعالجة',
            'indexing' => 'جارٍ الفهرسة',
            'indexed' => 'اكتملت المعالجة',
            'failed' => 'فشلت المعالجة',
        ],

        'kind' => [
            'initial' => 'المعالجة الأولى',
            'reprocessing' => 'إعادة المعالجة',
        ],

        'profile' => [
            'cloud' => 'سحابي',
            'hybrid_local' => 'محلي هجين',
        ],
    ],

    'failure' => [
        'processing_failed' => 'تعذر إكمال معالجة الوثيقة. يمكنك المحاولة مرة أخرى.',
    ],

    'validation' => [
        'secure_upload' => [
            'invalid_file' => 'تعذر قراءة الملف المرفوع أو أن عملية رفعه غير صالحة.',
            'unsafe_filename' => 'اسم الملف غير آمن. يرجى استخدام اسم ملف صالح.',
            'unsupported_type' => 'نوع الملف غير مدعوم.',
            'content_mismatch' => 'محتوى الملف لا يتطابق مع امتداده.',
            'inspection_failed' => 'تعذر فحص الملف المرفوع.',
            'malformed_or_unsafe' => 'الملف تالف أو غير آمن ولا يمكن استخدامه.',
        ],
    ],
];
