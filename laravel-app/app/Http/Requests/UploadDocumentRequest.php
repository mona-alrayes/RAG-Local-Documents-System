<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Rules\SecureDocumentUpload;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxSizeKilobytes = (int) config(
            'documents.upload.max_size_kilobytes',
            10240,
        );

        return [
            'document' => [
                'bail',
                'required',
                'file',
                'max:'.$maxSizeKilobytes,
                new SecureDocumentUpload,
            ],
        ];
    }
}
