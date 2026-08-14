<x-layouts.marketing title="الرئيسية">
    {{-- القسم الرئيسي --}}
    <section class="relative overflow-hidden">
        <div
            class="pointer-events-none absolute right-0 top-0 -z-10 size-150 rounded-full bg-cyan-400/10 blur-3xl"
            aria-hidden="true"
        ></div>

        <div class="mx-auto grid max-w-7xl items-center gap-8 px-5 py-10 sm:px-8 sm:py-12 lg:grid-cols-2 lg:px-10 lg:py-14">            {{-- محتوى Hero --}}
            <div class="max-w-2xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/5 px-4 py-2 text-sm text-cyan-400">
                    <span class="size-2 rounded-full bg-cyan-400 shadow-[0_0_12px_rgba(0,229,255,0.8)]"></span>

                    معرفة موثقة من ملفاتك
                </div>

                <h1 class="text-4xl font-bold leading-[1.35] text-ice-100 sm:text-5xl">
                    استخلص المعرفة من وثائقك

                    <span class="block text-cyan-400">
                        بثقة ودقة
                    </span>
                </h1>

                                         {{-- الوصف --}}
                <p class="mt-4 max-w-xl text-base leading-7 text-mist-300 sm:text-lg">
                    حوّل ملفاتك المحلية إلى قاعدة معرفة ذكية، واسأل بلغتك
                    لتحصل على إجابات سياقية مرتبطة بالمصادر الأصلية.
                </p>

                                        {{-- الأزرار --}}
                <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-cyan-400 px-7 py-3.5 font-semibold text-navy-950 shadow-lg shadow-cyan-400/15 transition hover:bg-cyan-400/90 hover:shadow-cyan-400/25"
                    >
                        ابدأ الآن

                        <svg
                            viewBox="0 0 20 20"
                            class="size-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path d="M16 10H4m0 0 5-5m-5 5 5 5" />
                        </svg>
                    </a>

                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-white/15 bg-white/5 px-7 py-3.5 font-semibold text-ice-100 transition hover:border-cyan-400/30 hover:bg-white/10"
                    >
                        تسجيل الدخول
                    </a>
                </div>

                                          {{-- النقاط الثلاث --}}
                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm text-mist-300">
                    <span class="inline-flex items-center gap-2">
                        <span class="size-1.5 rounded-full bg-cyan-400"></span>
                        ملفات محلية
                    </span>

                    <span class="inline-flex items-center gap-2">
                        <span class="size-1.5 rounded-full bg-cyan-400"></span>
                        إجابات موثقة
                    </span>

                    <span class="inline-flex items-center gap-2">
                        <span class="size-1.5 rounded-full bg-cyan-400"></span>
                        خصوصية أعلى
                    </span>
                </div>
            </div>

            {{-- صورة Hero --}}
            <div class="relative">
                <div
                    class="absolute inset-8 -z-10 rounded-full bg-cyan-400/15 blur-3xl"
                    aria-hidden="true"
                ></div>

                <div class="overflow-hidden rounded-xl border border-cyan-400/20 bg-navy-900/70 p-2 shadow-2xl shadow-cyan-400/10">
                    <img
                        src="{{ asset('images/rag-hero.png') }}"
                        alt="تمثيل بصري لتحويل الوثائق إلى معرفة مترابطة"
                        class="aspect-video w-full rounded-lg object-cover"
                        width="1680"
                        height="945"
                        loading="eager"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </div>
    </section>

    {{-- الميزات --}}
    <section
        id="features"
        class="border-y border-white/10 bg-navy-900/40 px-5 py-20 sm:px-8 lg:px-10 lg:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <header class="mx-auto mb-12 max-w-2xl text-center">
                <p class="text-sm font-semibold text-cyan-400">
                    قدرات النظام
                </p>

                <h2 class="mt-3 text-3xl font-bold text-ice-100 sm:text-4xl">
                    مصمم للحصول على إجابات دقيقة
                </h2>

                <p class="mt-4 leading-7 text-mist-300">
                    يجمع النظام بين البحث الدلالي والتوليد المعزز بالاسترجاع
                    لتقديم معرفة قابلة للتتبع.
                </p>
            </header>

            <div class="grid gap-6 md:grid-cols-3">
                {{-- الاسترجاع الدلالي --}}
                <article class="group rounded-xl border border-white/10 border-r-cyan-400/60 bg-navy-900/70 p-7 transition hover:-translate-y-1 hover:border-cyan-400/30">
                    <div class="mb-6 flex size-12 items-center justify-center rounded-lg border border-cyan-400/20 bg-cyan-400/10 text-cyan-400">
                        <svg
                            viewBox="0 0 24 24"
                            class="size-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            aria-hidden="true"
                        >
                            <ellipse cx="12" cy="5" rx="7" ry="3" />
                            <path d="M5 5v6c0 1.66 3.13 3 7 3s7-1.34 7-3V5" />
                            <path d="M5 11v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-ice-100">
                        استرجاع دلالي عميق
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-mist-300">
                        يعثر على الأجزاء الأكثر ارتباطًا بالسؤال اعتمادًا على
                        المعنى، وليس على تطابق الكلمات فقط.
                    </p>
                </article>

                {{-- الإجابات السياقية --}}
                <article class="group rounded-xl border border-white/10 border-r-ice-100/50 bg-navy-900/70 p-7 transition hover:-translate-y-1 hover:border-cyan-400/30">
                    <div class="mb-6 flex size-12 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-ice-100">
                        <svg
                            viewBox="0 0 24 24"
                            class="size-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            aria-hidden="true"
                        >
                            <path d="M9 4h6v3H9zM7 9h10v11H7z" />
                            <path d="M10 12h4M10 15h4" />
                            <path d="M4 11v6M20 11v6" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-ice-100">
                        إجابات سياقية دقيقة
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-mist-300">
                        يصوغ إجابة واضحة اعتمادًا على المعلومات المسترجعة
                        من وثائقك وسياق السؤال.
                    </p>
                </article>

                {{-- إسناد المصادر --}}
                <article class="group rounded-xl border border-white/10 border-r-cyan-400/60 bg-navy-900/70 p-7 transition hover:-translate-y-1 hover:border-cyan-400/30">
                    <div class="mb-6 flex size-12 items-center justify-center rounded-lg border border-cyan-400/20 bg-cyan-400/10 text-cyan-400">
                        <svg
                            viewBox="0 0 24 24"
                            class="size-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            aria-hidden="true"
                        >
                            <path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-ice-100">
                        إسناد واضح للمصادر
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-mist-300">
                        تربط كل إجابة بالمقاطع والوثائق المستخدمة، لتتمكن
                        من مراجعة مصدر المعلومة.
                    </p>
                </article>
            </div>
        </div>
    </section>
</x-layouts.marketing>