<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\UpdateConversationDocumentsRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Conversation::class);

        $conversations = $request->user()
            ->conversations()
            ->latest()
            ->get();

        return view('conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    public function store(
        StoreConversationRequest $request,
    ): RedirectResponse {
        $request->user()
            ->conversations()
            ->create([
                'title' => $request->validated('title'),
            ]);

        return redirect()
            ->route('conversations.index')
            ->with('success', 'تم إنشاء المحادثة بنجاح.');
    }

    public function show(
        Conversation $conversation,
    ): View {
        Gate::authorize('view', $conversation);

        return view('conversations.show', [
            'conversation' => $conversation,
        ]);
    }

    public function updateDocuments(
        UpdateConversationDocumentsRequest $request,
        Conversation $conversation,
    ): RedirectResponse {
        $conversation->documents()->sync(
            $request->validated('document_ids', []),
        );

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'تم تحديث وثائق المحادثة بنجاح.');
    }
}
