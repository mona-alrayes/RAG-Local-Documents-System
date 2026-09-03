@props([
    'document',
])

@php
    $reprocessModal = 'reprocess-document-' . $document->id;
    $deleteModal = 'delete-document-' . $document->id;

    $activeProfile = $document->activeRun?->profile;
@endphp

<div class="shrink-0">
    <flux:dropdown position="bottom" align="end">
        <flux:button
            icon="ellipsis-horizontal"
            variant="ghost"
            size="sm"
            square
            tooltip="إجراءات الوثيقة"
            aria-label="إجراءات {{ $document->title ?: $document->originalName }}"
        />

        <flux:menu>
            {{-- عرض الوثيقة --}}
            <flux:menu.item
                icon="eye"
                href="{{ route('documents.show', $document->id) }}"
            >
                عرض التفاصيل
            </flux:menu.item>

            {{-- تحميل الوثيقة --}}
            @if ($document->canDownload)
                <flux:menu.item
                    icon="arrow-down-tray"
                    href="{{ route('documents.download', $document->id) }}"
                >
                    تحميل الملف
                </flux:menu.item>
            @else
                <flux:menu.item
                    icon="arrow-down-tray"
                    disabled
                >
                    التحميل غير متاح
                </flux:menu.item>
            @endif

            <flux:menu.separator />

            {{-- إعادة المعالجة --}}
            @if ($document->canReprocess && $activeProfile !== null)
                <flux:modal.trigger :name="$reprocessModal">
                    <flux:menu.item icon="arrow-path">
                        إعادة المعالجة
                    </flux:menu.item>
                </flux:modal.trigger>
            @else
                <flux:menu.item
                    icon="arrow-path"
                    disabled
                >
                    إعادة المعالجة غير متاحة
                </flux:menu.item>
            @endif

            {{-- حذف الوثيقة --}}
            @if ($document->canDelete)
                <flux:modal.trigger :name="$deleteModal">
                    <flux:menu.item
                        icon="trash"
                        variant="danger"
                    >
                        حذف الوثيقة
                    </flux:menu.item>
                </flux:modal.trigger>
            @else
                <flux:menu.item
                    icon="trash"
                    variant="danger"
                    disabled
                >
                    الحذف غير متاح
                </flux:menu.item>
            @endif
        </flux:menu>
    </flux:dropdown>

    {{-- نافذة تأكيد إعادة المعالجة --}}
    @if ($document->canReprocess && $activeProfile !== null)
        <flux:modal
            :name="$reprocessModal"
            class="md:w-96"
        >
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        إعادة معالجة الوثيقة؟
                    </flux:heading>

                    <flux:text class="mt-2">
                        سيتم بدء معالجة جديدة للوثيقة
                        "{{ $document->title ?: $document->originalName }}"
                        باستخدام نفس طريقة المعالجة الحالية:
                        <span class="font-semibold text-ice-100">
                            {{ __('documents.processing_run.profile.' . $activeProfile->value) }}
                        </span>
                    </flux:text>
                </div>

                <form
                    method="POST"
                    action="{{ route('documents.reprocess', $document->id) }}"
                    class="flex flex-wrap justify-end gap-3"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="processing_profile"
                        value="{{ $activeProfile->value }}"
                    >

                    <flux:modal.close>
                        <flux:button
                            type="button"
                            variant="ghost"
                        >
                            إلغاء
                        </flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="arrow-path"
                    >
                        بدء إعادة المعالجة
                    </flux:button>
                </form>
            </div>
        </flux:modal>
    @endif

    {{-- نافذة تأكيد الحذف --}}
    @if ($document->canDelete)
        <flux:modal
            :name="$deleteModal"
            class="md:w-96"
        >
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        حذف الوثيقة؟
                    </flux:heading>

                    <flux:text class="mt-2">
                        سيتم حذف
                        "{{ $document->title ?: $document->originalName }}"
                        وبياناتها المرتبطة.
                    </flux:text>

                    <flux:text class="mt-2">
                        لا يمكن التراجع عن هذه العملية بعد تنفيذها.
                    </flux:text>
                </div>

                <form
                    method="POST"
                    action="{{ route('documents.destroy', $document->id) }}"
                    class="flex flex-wrap justify-end gap-3"
                >
                    @csrf
                    @method('DELETE')

                    <flux:modal.close>
                        <flux:button
                            type="button"
                            variant="ghost"
                        >
                            إلغاء
                        </flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="danger"
                        icon="trash"
                    >
                        حذف الوثيقة
                    </flux:button>
                </form>
            </div>
        </flux:modal>
    @endif
</div>