<x-layouts.app title="الوثائق">
    <header class="mb-8">
        <p class="text-sm font-semibold text-cyan-400">
            الوثائق
        </p>

        <h1 class="mt-2 text-3xl font-bold text-ice-100">
            وثائقي
        </h1>

        <p class="mt-3 text-mist-300">
            استعرض الوثائق المرتبطة بحسابك.
        </p>
    </header>

    <div class="space-y-4">
        @forelse ($documents as $document)
            <article class="rounded-xl border border-white/10 bg-navy-900/70 p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-ice-100">
                            {{ $document->title ?: $document->original_name }}
                        </h2>

                        <p class="mt-2 text-sm text-mist-300">
                            {{ $document->original_name }}
                        </p>

                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-mist-300">
                            <div>
                                <dt class="sr-only">النوع</dt>
                                <dd>{{ strtoupper($document->file_type->value) }}</dd>
                            </div>

                            <div>
                                <dt class="sr-only">الحجم</dt>
                                <dd>{{ number_format($document->file_size) }} بايت</dd>
                            </div>

                            <div>
                                <dt class="sr-only">الحالة</dt>
                                <dd>{{ $document->status->value }}</dd>
                            </div>

                            <div>
                                <dt class="sr-only">تاريخ الإضافة</dt>
                                <dd>{{ $document->created_at->format('Y-m-d') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <a
                        href="{{ route('documents.show', $document) }}"
                        class="inline-flex rounded-lg border border-cyan-400/30 px-4 py-2 text-sm font-semibold text-cyan-400 transition hover:bg-cyan-400/10"
                    >
                        عرض التفاصيل
                    </a>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-white/10 bg-navy-900/70 p-6 text-mist-300">
                لا توجد وثائق حتى الآن.
            </p>
        @endforelse
    </div>

    @if ($documents->hasPages())
        <div class="mt-6">
            {{ $documents->links() }}
        </div>
    @endif
</x-layouts.app>
