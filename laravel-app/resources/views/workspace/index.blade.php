<x-layouts.app title="مساحة العمل">
    <section class="max-w-3xl">
        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm text-cyan-400">
            <span class="size-2 rounded-full bg-cyan-400"></span>
            الحساب مفعّل
        </div>

        <h1 class="mt-6 text-3xl font-bold text-ice-100 sm:text-4xl">
            أهلًا، {{ auth()->user()->name }}
        </h1>

        <p class="mt-4 max-w-2xl leading-8 text-mist-300">
            أصبحت مساحة العمل جاهزة. ستُضاف إدارة الوثائق والمحادثات
            والاستعلام الذكي ضمن المراحل القادمة من المشروع.
        </p>
    </section>

    <section class="mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
        <article class="rounded-xl border border-white/10 bg-navy-900/70 p-6">
            <p class="text-sm font-semibold text-cyan-400">
                الوثائق
            </p>

            <h2 class="mt-3 text-xl font-semibold text-ice-100">
                إدارة قاعدة المعرفة
            </h2>

            <p class="mt-3 text-sm leading-7 text-mist-300">
                رفع الملفات ومعالجتها ومتابعة حالتها ستُضاف في مرحلة
                Documents Domain.
            </p>
        </article>

        <article class="rounded-xl border border-white/10 bg-navy-900/70 p-6">
            <p class="text-sm font-semibold text-cyan-400">
                المحادثات
            </p>

            <h2 class="mt-3 text-xl font-semibold text-ice-100">
                الاستعلام عن الوثائق
            </h2>

            <p class="mt-3 text-sm leading-7 text-mist-300">
                المحادثات والإجابات المرتبطة بالمصادر ستُضاف بعد اكتمال
                معالجة الوثائق.
            </p>
        </article>
    </section>
</x-layouts.app>