<?php

namespace App\Enums;

/**
 * Defines the aggregate lifecycle statuses of a document.
 *
 * حالات دورة حياة الوثيقة.
 */
enum DocumentStatus: string
{
    case Pending = 'pending';
    case Scanning = 'scanning';
    case Infected = 'infected';
    case Queued = 'queued';
    case Processing = 'processing';
    case AwaitingSelection = 'awaiting_selection';
    case Indexing = 'indexing';
    case Ready = 'ready';
    case Failed = 'failed';
    case SelectionExpired = 'selection_expired';
}
