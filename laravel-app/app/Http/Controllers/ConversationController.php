<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ConversationController extends Controller
{
    /**
     * Display the authenticated user's conversations.
     */
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

    /**
     * Create a conversation owned by the authenticated user.
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $request->user()
            ->conversations()
            ->create([
                'title' => $request->validated('title'),
            ]);

        return redirect()
            ->route('conversations.index')
            ->with('success', 'تم إنشاء المحادثة بنجاح.');
    }
}
