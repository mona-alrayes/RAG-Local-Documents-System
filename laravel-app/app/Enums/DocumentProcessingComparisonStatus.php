<?php

namespace App\Enums;

/**
 * Defines the lifecycle statuses of a document processing comparison.
 *
 * حالات دورة حياة مقارنة معالجة الوثيقة.
 */
enum DocumentProcessingComparisonStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Decided = 'decided';
    case Expired = 'expired';
    case Failed = 'failed';
}
