<x-layouts.app title="المحادثات">
    <div class="space-y-8">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-cyan-400">
                    المحادثات
                </p>

                <h1 class="mt-2 text-3xl font-bold text-ice-100">
                    محادثاتي
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-7 text-mist-300">
                    أنشئ محادثة جديدة أو استعرض المحادثات التي أنشأتها سابقًا.
                </p>
            </div>

            <div class="shrink-0 text-sm text-mist-300">
                {{ $conversations->count() }} محادثة
            </div>
        </header>

        @if (session('success'))
            <div
                role="status"
                class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ session('success') }}
            </div>
        @endif

        <section
            class="rounded-2xl border border-white/10 bg-navy-900/70 p-4 sm:p-5"
            aria-labelledby="create-conversation-title"
        >
            <div class="mb-5">
                <h2
                    id="create-conversation-title"
                    class="text-lg font-semibold text-ice-100"
                >
                    محادثة جديدة
                </h2>

                <p class="mt-2 text-sm leading-7 text-mist-300">
                    يمكنك إضافة عنوان الآن أو تركه فارغًا.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('conversations.store') }}"
                class="flex flex-col gap-4 sm:flex-row sm:items-end"
            >
                @csrf

                <div class="min-w-0 flex-1">
                    <label
                        for="title"
                        class="mb-2 block text-sm font-medium text-ice-100"
                    >
                        عنوان المحادثة
                    </label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        maxlength="255"
                        value="{{ old('title') }}"
                        placeholder="مثال: أسئلة عن بحث التخرج"
                        @error('title')
                            aria-invalid="true"
                            aria-describedby="title-error"
                        @enderror
                        class="block w-full rounded-xl border border-white/10 bg-navy-950 px-4 py-3 text-sm text-ice-100 placeholder:text-mist-400 focus:border-cyan-400 focus:outline-none"
                    >

                    @error('title')
                        <p
                            id="title-error"
                            role="alert"
                            class="mt-2 text-sm text-red-300"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-cyan-300"
                >
                    إنشاء محادثة
                </button>
            </form>
        </section>

        <section aria-labelledby="conversation-list-title">
            <div class="mb-4">
                <h2
                    id="conversation-list-title"
                    class="text-lg font-semibold text-ice-100"
                >
                    المحادثات السابقة
                </h2>
            </div>

            @forelse ($conversations as $conversation)
                <article
                    class="mb-3 rounded-2xl border border-white/10 bg-navy-900/70 p-5"
                >
                    <h3 class="font-semibold text-ice-100">
                        {{ $conversation->title ?: 'محادثة بدون عنوان' }}
                    </h3>

                    <p class="mt-2 text-xs text-mist-300">
                        أُنشئت في
                        {{ $conversation->created_at->format('Y-m-d H:i') }}
                    </p>
                </article>
            @empty
                <div
                    class="rounded-2xl border border-dashed border-white/10 bg-navy-900/40 p-8 text-center"
                >
                    <p class="font-medium text-ice-100">
                        لا توجد محادثات حتى الآن
                    </p>

                    <p class="mt-2 text-sm text-mist-300">
                        أنشئ أول محادثة باستخدام النموذج أعلاه.
                    </p>
                </div>
            @endforelse
        </section>
    </div>
</x-layouts.app>