<div
    class="space-y-8"
    @if ($pollRequired)
        wire:poll.visible.5s="refreshDocuments"
    @endif
>
    <header>
        <a
            href="{{ route('conversations.index') }}"
            wire:navigate
            class="text-sm font-semibold text-cyan-400 hover:text-cyan-300"
        >
            العودة إلى المحادثات
        </a>

        <h1 class="mt-4 text-3xl font-bold text-ice-100">
            {{ $conversation->title ?: 'محادثة بدون عنوان' }}
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-mist-300">
            اختر وثيقة واحدة أو أكثر لربطها بهذه المحادثة.
            الوثائق غير الجاهزة تبقى قابلة للاختيار، لكنها لن تدخل إلى RAG
            حتى تصبح جاهزة فعليًا.
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

    @if ($saved)
        <div
            role="status"
            class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
        >
            تم تحديث وثائق المحادثة بنجاح.
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
                wire:navigate
                class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300"
            >
                الذهاب إلى الوثائق
            </a>
        </section>
    @else
        <form wire:submit="save" class="space-y-5">
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
                        يمكنك تغيير الاختيار في أي وقت.
                        حالة الوثيقة تتحدث تلقائيًا دون Refresh يدوي.
                    </p>
                </div>

                @if ($errors->has('document_ids') || $errors->has('document_ids.*'))
                    <div
                        role="alert"
                        class="mb-4 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                    >
                        {{
                            $errors->first('document_ids')
                                ?: $errors->first('document_ids.*')
                        }}
                    </div>
                @endif

                <div class="space-y-3">
                    @foreach ($documents as $document)
                        @php
                            $isReady =
                                $document->availability
                                === \App\Enums\DocumentAvailability::Ready;
                        @endphp

                        <label
                            wire:key="conversation-document-{{ $document->id }}"
                            for="document-{{ $document->id }}"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 bg-navy-950/70 p-4 transition hover:border-cyan-400/30"
                        >
                            <input
                                id="document-{{ $document->id }}"
                                type="checkbox"
                                value="{{ $document->id }}"
                                wire:model="selectedDocumentIds"
                                class="mt-1 size-4 shrink-0 rounded border-white/20 bg-navy-950 text-cyan-400 focus:ring-cyan-400"
                            >

                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-ice-100">
                                    {{ $document->title ?: $document->originalName }}
                                </span>

                                @if (
                                    $document->title
                                    && $document->title !== $document->originalName
                                )
                                    <span class="mt-1 block text-xs text-mist-300">
                                        {{ $document->originalName }}
                                    </span>
                                @endif

                                <span class="mt-3 block">
                                    <x-documents.status-indicator
                                        :availability="$document->availability"
                                        :label="
                                            $isReady
                                                ? 'جاهزة للاستخدام'
                                                : null
                                        "
                                    />
                                </span>

                                @if ($document->reprocessingInProgress)
                                    <span class="mt-2 block text-xs leading-5 text-cyan-300">
                                        إعادة معالجة جديدة جارية،
                                        والنسخة الفعالة السابقة ما زالت جاهزة للاستخدام.
                                    </span>
                                @elseif (! $isReady)
                                    <span class="mt-2 block text-xs leading-5 text-mist-400">
                                        يمكن إبقاء الوثيقة محددة؛
                                        ستصبح قابلة للاستخدام في المحادثة تلقائيًا
                                        بعد اكتمال المعالجة.
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
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">
                        حفظ الاختيار
                    </span>

                    <span wire:loading wire:target="save">
                        جارٍ الحفظ...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>