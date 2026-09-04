<x-layouts.app title="{{ $conversation->title ?: 'محادثة بدون عنوان' }}">
    @php
        $selectedDocumentIds = collect(
            old('document_ids', $conversation->documents->modelKeys())
        )
            ->map(fn ($id) => (string) $id)
            ->all();
    @endphp

    <div class="space-y-8">
        <header>
            <a
                href="{{ route('conversations.index') }}"
                class="text-sm font-semibold text-cyan-400 hover:text-cyan-300"
            >
                العودة إلى المحادثات
            </a>

            <h1 class="mt-4 text-3xl font-bold text-ice-100">
                {{ $conversation->title ?: 'محادثة بدون عنوان' }}
            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-7 text-mist-300">
                اختر وثيقة واحدة أو أكثر لربطها بهذه المحادثة.
            </p>
        </header>

        @if (session('success'))
            <div
                role="status"
                class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ session('success') }}
            </div>
        @endif

        @if ($documents->isEmpty())
            <section
                class="rounded-2xl border border-dashed border-white/10 bg-navy-900/40 p-8 text-center"
            >
                <p class="font-medium text-ice-100">
                    لا توجد وثائق متاحة للاختيار
                </p>

                <p class="mt-2 text-sm text-mist-300">
                    ارفع وثيقة أولًا ثم عد إلى هذه المحادثة.
                </p>

                <a
                    href="{{ route('documents.index') }}"
                    class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300"
                >
                    الذهاب إلى الوثائق
                </a>
            </section>
        @else
            <form
                method="POST"
                action="{{ route('conversations.documents.update', $conversation) }}"
                class="space-y-5"
            >
                @csrf
                @method('PUT')

                <section
                    class="rounded-2xl border border-white/10 bg-navy-900/70 p-4 sm:p-5"
                    aria-labelledby="document-selection-title"
                >
                    <div class="mb-5">
                        <h2
                            id="document-selection-title"
                            class="text-lg font-semibold text-ice-100"
                        >
                            وثائق المحادثة
                        </h2>

                        <p class="mt-2 text-sm leading-7 text-mist-300">
                            يمكنك تغيير الاختيار في أي وقت. إلغاء تحديد جميع الوثائق سيزيلها من المحادثة.
                        </p>
                    </div>

                    @if ($errors->has('document_ids') || $errors->has('document_ids.*'))
                        <div
                            role="alert"
                            class="mb-4 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                        >
                            {{ $errors->first('document_ids') ?: $errors->first('document_ids.*') }}
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach ($documents as $document)
                            <label
                                for="document-{{ $document->id }}"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 bg-navy-950/70 p-4 transition hover:border-cyan-400/30"
                            >
                                <input
                                    id="document-{{ $document->id }}"
                                    name="document_ids[]"
                                    type="checkbox"
                                    value="{{ $document->id }}"
                                    @checked(
                                        in_array(
                                            (string) $document->id,
                                            $selectedDocumentIds,
                                            true
                                        )
                                    )
                                    class="mt-1 size-4 rounded border-white/20 bg-navy-950 text-cyan-400 focus:ring-cyan-400"
                                >

                                <span class="min-w-0">
                                    <span class="block font-medium text-ice-100">
                                        {{ $document->title ?: $document->original_name }}
                                    </span>

                                    @if ($document->title && $document->title !== $document->original_name)
                                        <span class="mt-1 block text-xs text-mist-300">
                                            {{ $document->original_name }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300"
                    >
                        حفظ الاختيار
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>