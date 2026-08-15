<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
}
