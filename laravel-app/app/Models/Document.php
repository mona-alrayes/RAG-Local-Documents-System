<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a document owned by a user.
 *
 * يمثل وثيقة يملكها مستخدم.
 */
#[Fillable([
    'original_name',
    'stored_name',
    'title',
    'file_path',
    'file_type',
    'mime_type',
    'file_size',
    'sha256',
])]
class Document extends Model
{
    /**
     * Get the user who owns the document.
     *
     * المستخدم المالك للوثيقة.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
