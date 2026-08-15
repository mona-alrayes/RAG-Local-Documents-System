<x-layouts.app :title="$document->title ?: $document->original_name">
    <header class="mb-8">
        <a
            href="{{ route('documents.index') }}"
            class="text-sm font-semibold text-cyan-400 hover:text-cyan-300"
        >
            العودة إلى الوثائق
        </a>

        <h1 class="mt-4 text-3xl font-bold text-ice-100">
            {{ $document->title ?: $document->original_name }}
        </h1>
    </header>

    <dl class="grid gap-6 rounded-xl border border-white/10 bg-navy-900/70 p-6 sm:grid-cols-2">
        <div>
            <dt class="text-sm text-mist-300">اسم الملف</dt>
            <dd class="mt-2 text-ice-100">{{ $document->original_name }}</dd>
        </div>

        <div>
            <dt class="text-sm text-mist-300">نوع الملف</dt>
            <dd class="mt-2 text-ice-100">{{ strtoupper($document->file_type->value) }}</dd>
        </div>

        <div>
            <dt class="text-sm text-mist-300">الحجم</dt>
            <dd class="mt-2 text-ice-100">{{ number_format($document->file_size) }} بايت</dd>
        </div>

        <div>
            <dt class="text-sm text-mist-300">الحالة</dt>
            <dd class="mt-2 text-ice-100">{{ $document->status->value }}</dd>
        </div>

        <div class="sm:col-span-2">
            <dt class="text-sm text-mist-300">SHA-256</dt>
            <dd class="mt-2 break-all font-mono text-sm text-ice-100">
                {{ $document->sha256 }}
            </dd>
        </div>
    </dl>
</x-layouts.app>
