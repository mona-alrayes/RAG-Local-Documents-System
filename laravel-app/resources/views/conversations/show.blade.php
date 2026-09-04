<x-layouts.app
    title="{{ $conversation->title ?: 'محادثة بدون عنوان' }}"
>
    <livewire:conversations.document-selector
        :conversation-id="$conversation->id"
    />
</x-layouts.app>