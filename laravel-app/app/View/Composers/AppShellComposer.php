<?php

namespace App\View\Composers;

use App\Services\Documents\Presentation\DocumentReadService;
use Illuminate\View\View;

final class AppShellComposer
{
    public function __construct(
        private readonly DocumentReadService $documentReadService,
    ) {}

    public function compose(View $view): void
    {
        $user = request()->user();

        $view->with(
            'sidebarDocuments',
            $user !== null
                ? $this->documentReadService->recentForUser($user)
                : [],
        );
    }
}
