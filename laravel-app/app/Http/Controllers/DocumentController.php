<?php

namespace App\Http\Controllers;

use App\Exceptions\AiServiceException;
use App\Exceptions\DocumentDeletionException;
use App\Exceptions\DocumentReprocessingException;
use App\Exceptions\DuplicateDocumentException;
use App\Http\Requests\DeleteDocumentRequest;
use App\Http\Requests\DocumentIndexRequest;
use App\Http\Requests\ReprocessDocumentRequest;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use App\Services\Ai\ProcessingCapabilityService;
use App\Services\Documents\DocumentDeletionService;
use App\Services\Documents\DocumentProcessingDispatcher;
use App\Services\Documents\DocumentUploadService;
use App\Services\Documents\Presentation\DocumentReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(
        DocumentIndexRequest $request,
        DocumentReadService $documentReadService,
        ProcessingCapabilityService $processingCapabilityService,
    ): View {
        $documents = $documentReadService
            ->paginateForUser(
                $request->user(),
                $request->criteria(),
            )
            ->appends($request->validated());

        $hasAnyDocuments = $documentReadService->hasAnyForUser(
            $request->user(),
        );

        try {
            $availableProcessingProfiles = $processingCapabilityService
                ->availableProfiles();
        } catch (AiServiceException) {
            $availableProcessingProfiles = [];
        }

        return view('documents.index', compact(
            'documents',
            'hasAnyDocuments',
            'availableProcessingProfiles',
        ));
    }

    public function show(Document $document): View
    {
        Gate::authorize('view', $document);

        return view('documents.show', compact('document'));
    }

    public function store(
        UploadDocumentRequest $request,
        DocumentUploadService $uploadService,
    ): RedirectResponse {
        try {
            $document = $uploadService->store(
                $request->user(),
                $request->file('document'),
                $request->processingProfile(),
            );
        } catch (DuplicateDocumentException $exception) {
            return redirect()
                ->route('documents.show', $exception->document)
                ->with(
                    'warning',
                    __('documents.commands.upload.duplicate'),
                );
        } catch (AiServiceException $exception) {
            $message = match ($exception->errorCode) {
                'processing_profile_unavailable' => __('documents.commands.upload.profile_unavailable'),

                default => __('documents.commands.upload.service_unavailable'),
            };

            return redirect()
                ->route('documents.index')
                ->with('error', $message);
        }

        return redirect()
            ->route('documents.show', $document)
            ->with(
                'success',
                __('documents.commands.upload.success'),
            );
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

    public function reprocess(
        ReprocessDocumentRequest $request,
        Document $document,
        DocumentProcessingDispatcher $dispatcher,
    ): RedirectResponse {
        try {
            $dispatcher->dispatchReprocessing(
                $document,
                $request->processingProfile(),
            );
        } catch (DocumentReprocessingException $exception) {
            $message = match ($exception->reason) {
                DocumentReprocessingException::NO_ACTIVE_RUN => __('documents.commands.reprocess.no_active_run'),

                DocumentReprocessingException::INVALID_ACTIVE_RUN => __('documents.commands.reprocess.invalid_active_run'),

                DocumentReprocessingException::ALREADY_IN_PROGRESS => __('documents.commands.reprocess.already_in_progress'),

                default => __('documents.commands.reprocess.failed'),
            };

            return redirect()
                ->route('documents.show', $document)
                ->with('error', $message);
        } catch (AiServiceException $exception) {
            $message = match ($exception->errorCode) {
                'processing_profile_unavailable' => __('documents.commands.reprocess.profile_unavailable'),

                default => __('documents.commands.reprocess.service_unavailable'),
            };

            return redirect()
                ->route('documents.show', $document)
                ->with('error', $message);
        }

        return redirect()
            ->route('documents.show', $document)
            ->with(
                'success',
                __('documents.commands.reprocess.started'),
            );
    }

    public function destroy(
        DeleteDocumentRequest $request,
        Document $document,
        DocumentDeletionService $deletionService,
    ): RedirectResponse {
        try {
            $deletionService->delete($document);
        } catch (DocumentDeletionException $exception) {
            $message = match ($exception->reason) {
                DocumentDeletionException::PROCESSING_IN_PROGRESS => __('documents.commands.delete.processing_in_progress'),

                default => __('documents.commands.delete.failed'),
            };

            return redirect()
                ->route('documents.show', $document)
                ->with('error', $message);
        } catch (AiServiceException) {
            return redirect()
                ->route('documents.show', $document)
                ->with(
                    'error',
                    __('documents.commands.delete.cleanup_failed'),
                );
        }

        return redirect()
            ->route('documents.index')
            ->with(
                'success',
                __('documents.commands.delete.success'),
            );
    }
}
