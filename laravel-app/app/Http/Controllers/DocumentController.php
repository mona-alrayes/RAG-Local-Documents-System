<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateDocumentException;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use App\Services\Documents\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        $documents = $request->user()
            ->documents()
            ->latest()
            ->paginate(10);

        return view('documents.index', compact('documents'));
    }

    public function show(Document $document): View
    {
        Gate::authorize('view', $document);

        return view('documents.show', compact('document'));
    }

    public function store(
        UploadDocumentRequest $request,
        DocumentStorageService $storage,
    ): Response|JsonResponse {
        try {
            $storage->store(
                $request->user(),
                $request->file('document'),
            );
        } catch (DuplicateDocumentException $exception) {
            return response()->json([
                'message' => 'هذا الملف مرفوع مسبقًا.',
                'errors' => [
                    'document' => [
                        sprintf(
                            'هذا الملف مرفوع مسبقًا باسم "%s".',
                            $exception->document->original_name,
                        ),
                    ],
                ],
                'duplicate_document' => [
                    'id' => $exception->document->id,
                    'original_name' => $exception->document->original_name,
                ],
            ], 422);
        }

        return response()->noContent();
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('download', $document);

        $disk = Storage::disk('documents');

        abort_unless(
            $disk->exists($document->file_path),
            404,
        );

        return $disk->download(
            $document->file_path,
            $document->original_name,
            [
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
