@props([
    'availability',
    'showLabel' => true,
])

@php
    $status = $availability instanceof \App\Enums\DocumentAvailability
        ? $availability->value
        : (string) $availability;

    $dotClasses = match ($status) {
        'ready' => 'bg-emerald-400',
        'failed', 'infected' => 'bg-red-400',
        'processing', 'indexing' => 'bg-amber-400',
        'scanning', 'queued' => 'bg-cyan-400',
        'pending' => 'bg-slate-400',
        default => 'bg-slate-400',
    };
@endphp

<span {{ $attributes->class(['inline-flex min-w-0 items-center gap-2']) }}>
    <span
        class="size-2.5 shrink-0 rounded-full {{ $dotClasses }}"
        aria-hidden="true"
    ></span>

    @if ($showLabel)
        <span class="truncate text-xs text-mist-300">
            {{ __('documents.availability.' . $status) }}
        </span>
    @endif
</span>