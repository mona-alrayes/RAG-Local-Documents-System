<x-layouts.app title="مساحة العمل">
    <div class="space-y-10">
        {{-- Header --}}
        <section>
            <p class="text-sm font-semibold text-cyan-400">
                مساحة العمل
            </p>

            <h1 class="mt-2 text-3xl font-bold text-ice-100 sm:text-4xl">
                أهلًا، {{ auth()->user()->name }}
            </h1>

            <p class="mt-3 max-w-2xl leading-7 text-mist-300">
                نظرة سريعة على وثائقك وحالة معالجتها الحالية.
            </p>
        </section>

        {{-- Summary --}}
        <section aria-labelledby="workspace-summary-heading">
            <h2 id="workspace-summary-heading" class="sr-only">
                ملخص الوثائق
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-white/10 bg-navy-900/70 p-5">
                    <p class="text-sm text-mist-300">
                        جميع الوثائق
                    </p>

                    <p class="mt-3 text-3xl font-bold text-ice-100">
                        {{ $dashboard['totalDocuments'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-emerald-400/20 bg-emerald-400/5 p-5">
                    <p class="text-sm text-emerald-300">
                        جاهزة
                    </p>

                    <p class="mt-3 text-3xl font-bold text-ice-100">
                        {{ $dashboard['readyDocuments'] }}
                    </p>
                </article>

                <article class="rounded-2xl border border-amber-400/20 bg-amber-400/5 p-5">
                    <p class="text-sm text-amber-300">
                        قيد المعالجة
                    </p>

                    <p class="mt-3 text-3xl font-bold text-ice-100">
                        {{ $dashboard['processingDocuments'] }}
                    </p>

                    @if ($dashboard['reprocessingCount'] > 0)
                        <p class="mt-2 text-xs text-mist-300">
                            منها {{ $dashboard['reprocessingCount'] }} إعادة معالجة
                        </p>
                    @endif
                </article>

                <article class="rounded-2xl border border-red-400/20 bg-red-400/5 p-5">
                    <p class="text-sm text-red-300">
                        فشلت المعالجة
                    </p>

                    <p class="mt-3 text-3xl font-bold text-ice-100">
                        {{ $dashboard['failedDocuments'] }}
                    </p>
                </article>
            </div>
        </section>

        @if ($dashboard['totalDocuments'] === 0)
            {{-- Empty state --}}
            <section
                class="rounded-2xl border border-dashed border-white/15 bg-navy-900/50 px-6 py-12 text-center"
            >
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300"
                    aria-hidden="true"
                >
                    <svg
                        class="size-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14.25 2.25H6.375A2.625 2.625 0 0 0 3.75 4.875v14.25a2.625 2.625 0 0 0 2.625 2.625h11.25a2.625 2.625 0 0 0 2.625-2.625V8.25m-6-6 6 6m-6-6v6h6"
                        />
                    </svg>
                </div>

                <h2 class="mt-5 text-xl font-semibold text-ice-100">
                    لا توجد لديك وثائق بعد
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-mist-300">
                    انتقل إلى قسم الوثائق لإدارة ملفاتك ومتابعة حالتها.
                </p>

                <a
                    href="{{ route('documents.index') }}"
                    class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-2 focus:ring-offset-navy-950"
                >
                    الانتقال إلى الوثائق
                </a>
            </section>
        @else
            {{-- Recent documents --}}
            <section aria-labelledby="recent-documents-heading">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2
                            id="recent-documents-heading"
                            class="text-xl font-semibold text-ice-100"
                        >
                            أحدث الوثائق
                        </h2>

                        <p class="mt-1 text-sm text-mist-300">
                            آخر الوثائق المضافة إلى حسابك.
                        </p>
                    </div>

                    <a
                        href="{{ route('documents.index') }}"
                        class="text-sm font-semibold text-cyan-300 transition hover:text-cyan-200"
                    >
                        عرض كل الوثائق
                    </a>
                </div>

                <div
                    class="mt-5 divide-y divide-white/10 overflow-hidden rounded-2xl border border-white/10 bg-navy-900/60"
                >
                    @foreach ($dashboard['recentDocuments'] as $document)
                        @php
                            $availability = $document->availability->value;

                            $statusDotClasses = match ($availability) {
                                'ready' => 'bg-emerald-400',
                                'failed', 'infected' => 'bg-red-400',
                                'processing', 'indexing' => 'bg-amber-400',
                                'scanning', 'queued' => 'bg-cyan-400',
                                default => 'bg-slate-400',
                            };
                        @endphp

                        <article
                            class="flex min-w-0 items-center justify-between gap-4 px-5 py-4"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="size-2.5 shrink-0 rounded-full {{ $statusDotClasses }}"
                                    aria-hidden="true"
                                ></span>

                                <div class="min-w-0">
                                    <a
                                        href="{{ route('documents.show', $document->id) }}"
                                        class="block truncate font-medium text-ice-100 transition hover:text-cyan-300"
                                    >
                                        {{ $document->title ?: $document->originalName }}
                                    </a>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="text-xs text-mist-300">
                                            {{ __('documents.availability.' . $availability) }}
                                        </span>

                                        @if ($document->reprocessingInProgress)
                                            <span class="text-xs text-cyan-300">
                                                إعادة معالجة جارية
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <a
                                href="{{ route('documents.show', $document->id) }}"
                                class="shrink-0 rounded-lg px-3 py-2 text-sm text-mist-300 transition hover:bg-white/5 hover:text-ice-100"
                            >
                                عرض
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>

            @if (count($dashboard['recentFailures']) > 0)
                {{-- Recent failures --}}
                <section
                    aria-labelledby="recent-failures-heading"
                    class="rounded-2xl border border-red-400/15 bg-red-400/5 p-5"
                >
                    <h2
                        id="recent-failures-heading"
                        class="font-semibold text-ice-100"
                    >
                        تحتاج إلى انتباه
                    </h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($dashboard['recentFailures'] as $document)
                            <div class="flex min-w-0 items-center justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="size-2.5 shrink-0 rounded-full bg-red-400"
                                        aria-hidden="true"
                                    ></span>

                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('documents.show', $document->id) }}"
                                            class="block truncate text-sm font-medium text-ice-100 hover:text-cyan-300"
                                        >
                                            {{ $document->title ?: $document->originalName }}
                                        </a>

                                        <p class="mt-1 text-xs text-red-200">
                                            {{ $document->safeFailure ?? __('documents.failure.processing_failed') }}
                                        </p>
                                    </div>
                                </div>

                                <a
                                    href="{{ route('documents.show', $document->id) }}"
                                    class="shrink-0 text-sm font-medium text-red-200 hover:text-red-100"
                                >
                                    التفاصيل
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.app>