{{-- ذا القالب مسؤول فقط عن الهيكل المشترك للصفحات العامة: الرأس، التنقل، الخلفية والـFooter. أما محتوى الصفحة الرئيسية فسيبقى داخل welcome.blade.php --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            {{ $title ? $title . ' | ' . config('app.name') : config('app.name') }}
        </title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-navy-950 text-ice-100">
        <div class="relative isolate min-h-screen overflow-hidden">
            {{-- خلفية تقنية خفيفة --}}
            <div
                class="pointer-events-none fixed inset-0 -z-10 opacity-30"
                aria-hidden="true"
                style="background-image:
                    linear-gradient(rgba(0, 229, 255, 0.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(0, 229, 255, 0.025) 1px, transparent 1px);
                    background-size: 48px 48px;"
            ></div>

            {{-- شريط التنقل --}}
            <header class="sticky top-0 z-50 border-b border-white/10 bg-navy-950/80 backdrop-blur-xl">
                <nav
                    class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8 lg:px-10"
                    aria-label="التنقل الرئيسي"
                >
                    <a href="{{ url('/') }}" aria-label="الصفحة الرئيسية">
                        <x-brand compact />
                    </a>

                    <a
                        href="#features"
                        class="hidden text-sm text-mist-300 transition hover:text-cyan-400 md:inline-flex"
                    >
                        الميزات
                    </a>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route('login') }}"
                            class="hidden rounded-lg px-4 py-2 text-sm font-medium text-mist-300 transition hover:bg-white/5 hover:text-ice-100 sm:inline-flex"
                        >
                            تسجيل الدخول
                        </a>

                        <a
                            href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-navy-950 shadow-lg shadow-cyan-400/10 transition hover:bg-cyan-400/90 hover:shadow-cyan-400/20"
                        >
                            إنشاء حساب
                        </a>
                    </div>
                </nav>
            </header>

            <main>
                {{ $slot }}
            </main>

            <footer class="border-t border-white/10 bg-navy-950">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-mist-300 sm:px-8 md:flex-row md:items-center md:justify-between lg:px-10">
                    <x-brand compact />

                    <p>
                        &copy; {{ now()->year }}
                        {{ config('app.name') }}. جميع الحقوق محفوظة.
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>