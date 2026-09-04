<x-layouts.app :title="$documentDetail->summary->title ?: $documentDetail->summary->originalName">
    @php
        $document = $documentDetail->summary;

        $displayTitle = $document->title ?: $document->originalName;

        $fileSize = match (true) {
            $document->fileSize >= 1024 * 1024 =>
                number_format($document->fileSize / (1024 * 1024), 1) . ' MB',

            $document->fileSize >= 1024 =>
                number_format($document->fileSize / 1024, 1) . ' KB',

            default =>
                number_format($document->fileSize) . ' B',
        };

        $activeRun = $document->activeRun;
        $latestAttempt = $document->latestAttempt;

        $activeAndLatestAreDifferent =
            $activeRun !== null
            && $latestAttempt !== null
            && $activeRun->id !== $latestAttempt->id;
    @endphp

    <div class="space-y-8">
        {{-- Header --}}
        <header class="space-y-5">
            <a
                href="{{ route('documents.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-cyan-400 transition hover:text-cyan-300"
            >
                <span aria-hidden="true">←</span>
                العودة إلى الوثائق
            </a>

            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-cyan-400">
                        تفاصيل الوثيقة
                    </p>

                    <h1 class="mt-2 break-words text-3xl font-bold text-ice-100">
                        {{ $displayTitle }}
                    </h1>

                    @if ($document->title)
                        <p class="mt-2 break-all text-sm text-mist-400">
                            {{ $document->originalName }}
                        </p>
                    @endif

                    <div class="mt-4">
                        <x-documents.status-indicator
                            :availability="$document->availability"
                        />
                    </div>
                </div>

                <x-documents.actions-menu
                    :document="$document"
                    :available-processing-profiles="$availableProcessingProfiles"
                />
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('success'))
            <div
                class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div
                class="rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-200"
            >
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div
                class="rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
            >
                {{ session('error') }}
            </div>
        @endif

        {{-- Reprocessing state --}}
        @if ($document->reprocessingInProgress && $activeAndLatestAreDifferent)
            <div
                class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-5"
            >
                <p class="font-semibold text-amber-200">
                    إعادة معالجة جديدة جارية
                </p>

                <p class="mt-2 text-sm leading-7 text-amber-100/80">
                    النسخة المعالجة السابقة ما زالت هي النسخة الفعالة حاليًا
                    إلى أن تنجح محاولة إعادة المعالجة الجديدة.
                </p>
            </div>
        @endif

        @if (
            $document->safeFailure !== null
            && $activeAndLatestAreDifferent
            && $document->availability->value === 'ready'
        )
            <div
                class="rounded-2xl border border-red-400/20 bg-red-400/10 p-5"
            >
                <p class="font-semibold text-red-200">
                    فشلت آخر محاولة لإعادة المعالجة
                </p>

                <p class="mt-2 text-sm leading-7 text-red-100/80">
                    {{ __('documents.failure.processing_failed') }}
                    النسخة السابقة ما زالت فعالة ومتاحة للاستخدام.
                </p>
            </div>
        @elseif ($document->safeFailure !== null)
            <div
                class="rounded-2xl border border-red-400/20 bg-red-400/10 p-5 text-sm text-red-200"
            >
                {{ __('documents.failure.processing_failed') }}
            </div>
        @endif

        {{-- Document summary --}}
        <section
            class="rounded-2xl border border-white/10 bg-navy-900/70 p-5 sm:p-6"
            aria-labelledby="document-summary-title"
        >
            <h2
                id="document-summary-title"
                class="text-lg font-semibold text-ice-100"
            >
                معلومات الوثيقة
            </h2>

            <dl class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-sm text-mist-400">
                        اسم الملف
                    </dt>

                    <dd class="mt-2 break-all text-sm font-medium text-ice-100">
                        {{ $document->originalName }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-mist-400">
                        نوع الملف
                    </dt>

                    <dd class="mt-2 text-sm font-medium text-ice-100">
                        {{ strtoupper($document->fileType->value) }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-mist-400">
                        الحجم
                    </dt>

                    <dd class="mt-2 text-sm font-medium text-ice-100">
                        {{ $fileSize }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-mist-400">
                        الحالة الحالية
                    </dt>

                    <dd class="mt-2">
                        <x-documents.status-indicator
                            :availability="$document->availability"
                        />
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-mist-400">
                        طريقة المعالجة الفعالة
                    </dt>

                    <dd class="mt-2 text-sm font-medium text-ice-100">
                        @if ($activeRun !== null)
                            {{ __('documents.processing_run.profile.' . $activeRun->profile->value) }}
                        @else
                            —
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-mist-400">
                        تاريخ الإضافة
                    </dt>

                    <dd class="mt-2 text-sm font-medium text-ice-100">
                        <time datetime="{{ $document->createdAt->toIso8601String() }}">
                            {{ $document->createdAt->format('Y-m-d H:i') }}
                        </time>
                    </dd>
                </div>
            </dl>
        </section>

        {{-- Active and latest processing state --}}
        <section
            class="grid gap-4 lg:grid-cols-2"
            aria-label="حالة المعالجة الحالية"
        >
            <article
                class="rounded-2xl border border-white/10 bg-navy-900/70 p-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-ice-100">
                        النسخة الفعالة
                    </h2>

                    @if ($activeRun !== null)
                        <span
                            class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300"
                        >
                            فعالة
                        </span>
                    @endif
                </div>

                @if ($activeRun !== null)
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                طريقة المعالجة
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.profile.' . $activeRun->profile->value) }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                الحالة
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.status.' . $activeRun->status->value) }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                نوع العملية
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.kind.' . $activeRun->kind->value) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 text-sm leading-7 text-mist-400">
                        لا توجد نسخة معالجة فعالة حتى الآن.
                    </p>
                @endif
            </article>

            <article
                class="rounded-2xl border border-white/10 bg-navy-900/70 p-5"
            >
                <h2 class="font-semibold text-ice-100">
                    آخر محاولة معالجة
                </h2>

                @if ($latestAttempt !== null)
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                طريقة المعالجة
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.profile.' . $latestAttempt->profile->value) }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                الحالة
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.status.' . $latestAttempt->status->value) }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-mist-400">
                                نوع العملية
                            </dt>

                            <dd class="font-medium text-ice-100">
                                {{ __('documents.processing_run.kind.' . $latestAttempt->kind->value) }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 text-sm leading-7 text-mist-400">
                        لم تبدأ أي محاولة معالجة بعد.
                    </p>
                @endif
            </article>
        </section>

        {{-- Processing timeline --}}
        <section
            class="rounded-2xl border border-white/10 bg-navy-900/70 p-5 sm:p-6"
            aria-labelledby="processing-timeline-title"
        >
            <div>
                <h2
                    id="processing-timeline-title"
                    class="text-lg font-semibold text-ice-100"
                >
                    سجل المعالجة
                </h2>

                <p class="mt-2 text-sm leading-7 text-mist-400">
                    تاريخ محاولات معالجة الوثيقة والمراحل التي تم تسجيلها فعليًا.
                </p>
            </div>

            @if (count($documentDetail->timeline) > 0)
                <div class="mt-6 space-y-5">
                    @foreach ($documentDetail->timeline as $run)
                        @php
                            $stages = [
                                [
                                    'label' => 'أضيفت إلى قائمة الانتظار',
                                    'time' => $run->queuedAt,
                                ],
                                [
                                    'label' => 'بدأت المعالجة',
                                    'time' => $run->startedAt,
                                ],
                                [
                                    'label' => 'بدأت الفهرسة',
                                    'time' => $run->indexingStartedAt,
                                ],
                                [
                                    'label' => 'اكتملت المعالجة',
                                    'time' => $run->indexedAt,
                                ],
                                [
                                    'label' => 'فشلت المعالجة',
                                    'time' => $run->failedAt,
                                ],
                            ];
                        @endphp

                        <article
                            class="rounded-xl border border-white/10 bg-navy-950/60 p-5"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-ice-100">
                                            {{ __('documents.processing_run.kind.' . $run->kind->value) }}
                                        </h3>

                                        @if ($run->isActive)
                                            <span
                                                class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-300"
                                            >
                                                النسخة الفعالة
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm text-mist-400">
                                        {{ __('documents.processing_run.profile.' . $run->profile->value) }}
                                    </p>
                                </div>

                                <span class="text-sm font-medium text-mist-200">
                                    {{ __('documents.processing_run.status.' . $run->status->value) }}
                                </span>
                            </div>

                            @if ($run->totalPages !== null || $run->totalChunks !== null)
                                <dl class="mt-5 flex flex-wrap gap-6 text-sm">
                                    @if ($run->totalPages !== null)
                                        <div>
                                            <dt class="text-mist-400">
                                                الصفحات
                                            </dt>

                                            <dd class="mt-1 font-semibold text-ice-100">
                                                {{ number_format($run->totalPages) }}
                                            </dd>
                                        </div>
                                    @endif

                                    @if ($run->totalChunks !== null)
                                        <div>
                                            <dt class="text-mist-400">
                                                المقاطع
                                            </dt>

                                            <dd class="mt-1 font-semibold text-ice-100">
                                                {{ number_format($run->totalChunks) }}
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            @endif

                            <ol class="mt-6 space-y-3 border-s border-white/10 ps-5">
                                @foreach ($stages as $stage)
                                    @if ($stage['time'] !== null)
                                        <li class="relative">
                                            <span
                                                class="absolute -start-[1.55rem] top-1.5 size-2 rounded-full bg-cyan-400"
                                                aria-hidden="true"
                                            ></span>

                                            <p class="text-sm font-medium text-ice-100">
                                                {{ $stage['label'] }}
                                            </p>

                                            <time
                                                datetime="{{ $stage['time']->toIso8601String() }}"
                                                class="mt-1 block text-xs text-mist-400"
                                            >
                                                {{ $stage['time']->format('Y-m-d H:i:s') }}
                                            </time>
                                        </li>
                                    @endif
                                @endforeach
                            </ol>

                            @if (! empty($run->stageTimingsMs))
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-ice-100">
                                        أزمنة المراحل
                                    </h4>

                                    <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($run->stageTimingsMs as $stage => $milliseconds)
                                            <div
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-2"
                                            >
                                                <dt class="break-all text-xs text-mist-400">
                                                    {{ $stage }}
                                                </dt>

                                                <dd class="mt-1 text-sm font-medium text-ice-100">
                                                    {{ number_format($milliseconds) }} ms
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif

                            @if (! empty($run->warnings))
                                <div
                                    class="mt-6 rounded-xl border border-amber-400/20 bg-amber-400/10 p-4"
                                >
                                    <p class="text-sm font-semibold text-amber-200">
                                        تحذيرات أثناء المعالجة
                                    </p>

                                    <ul class="mt-3 space-y-2 text-sm text-amber-100/80">
                                        @foreach ($run->warnings as $warning)
                                            <li>
                                                {{ $warning['code'] }}

                                                @if ($warning['stage'] !== null)
                                                    —
                                                    {{ $warning['stage'] }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($run->failedAt !== null)
                                <div
                                    class="mt-6 rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                                >
                                    تعذر إكمال محاولة المعالجة هذه.
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div
                    class="mt-6 rounded-xl border border-dashed border-white/15 px-5 py-8 text-center text-sm text-mist-400"
                >
                    لا توجد محاولات معالجة مسجلة حتى الآن.
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>