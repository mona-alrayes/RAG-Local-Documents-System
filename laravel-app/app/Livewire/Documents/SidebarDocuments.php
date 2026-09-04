<?php

namespace App\Livewire\Documents;

use App\Models\User;
use App\Services\Documents\Presentation\DocumentReadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SidebarDocuments extends Component
{
    #[Locked]
    public ?int $activeDocumentId = null;

    #[Locked]
    public bool $pollEnabled = true;

    private DocumentReadService $documentReadService;

    public function boot(
        DocumentReadService $documentReadService,
    ): void {
        $this->documentReadService = $documentReadService;
    }

    public function mount(
        ?int $activeDocumentId = null,
        bool $pollEnabled = true,
    ): void {
        $this->activeDocumentId = $activeDocumentId;
        $this->pollEnabled = $pollEnabled;
    }

    public function refreshDocuments(): void
    {
        //
    }

    public function render()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $documents = $this->documentReadService
            ->recentForUser($user);

        $pollRequired = $this->pollEnabled
            && collect($documents)->contains(
                fn ($document): bool => $document->pollRequired,
            );

        return view(
            'livewire.documents.sidebar-documents',
            [
                'documents' => $documents,
                'pollRequired' => $pollRequired,
            ],
        );
    }
}
