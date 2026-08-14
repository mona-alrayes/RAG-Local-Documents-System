{{-- قالب مساحة المستخدم بعد تسجيل الدخول --}}
@props(['title'])

<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }} | {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>

    <body class="min-h-screen bg-navy-950 text-ice-100">
        <div class="min-h-screen">
            <header class="border-b border-white/10 bg-navy-900/70 backdrop-blur-xl">
                <nav
                    class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8 lg:px-10"
                    aria-label="التنقل الرئيسي"
                >
                    {{-- هوية المشروع --}}
                    <a
                        href="{{ route('workspace') }}"
                        aria-label="الانتقال إلى مساحة العمل"
                    >
                        <x-brand compact />
                    </a>

                    {{-- معلومات المستخدم وإجراءات الحساب --}}
                    <div class="flex items-center gap-4">
                        <a
                            href="{{ route('workspace') }}"
                            title="العودة إلى مساحة العمل"
                            class="hidden items-center rounded-lg px-3 py-2 text-sm text-mist-300 transition hover:bg-white/5 hover:text-cyan-400 sm:inline-flex"
                        >
                            {{ auth()->user()->name }}
                        </a>

                        {{-- إعدادات الحساب --}}
                        <a
                            href="{{ route('settings.account') }}"
                            title="إعدادات الحساب"
                            aria-label="إعدادات الحساب"
                            @class([
                                'inline-flex size-10 items-center justify-center rounded-lg border transition',
                                'border-cyan-400/30 bg-cyan-400/10 text-cyan-400' => request()->routeIs('settings.*'),
                                'border-white/10 text-mist-300 hover:border-cyan-400/30 hover:bg-white/5 hover:text-ice-100' => ! request()->routeIs('settings.*'),
                            ])
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 15.25A3.25 3.25 0 1 0 12 8.75a3.25 3.25 0 0 0 0 6.5Z"
                                />

                                <path
                                    d="M19.4 13.5a7.9 7.9 0 0 0 0-3l2-1.55-2-3.46-2.48 1a8.2 8.2 0 0 0-2.6-1.5L14 2.35h-4L9.68 5a8.2 8.2 0 0 0-2.6 1.5l-2.48-1-2 3.46 2 1.55a7.9 7.9 0 0 0 0 3l-2 1.55 2 3.46 2.48-1a8.2 8.2 0 0 0 2.6 1.5l.32 2.64h4l.32-2.64a8.2 8.2 0 0 0 2.6-1.5l2.48 1 2-3.46-2-1.55Z"
                                />
                            </svg>
                        </a>

                        {{-- تسجيل الخروج --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg border border-white/10 px-4 py-2 text-sm text-mist-300 transition hover:border-cyan-400/30 hover:bg-white/5 hover:text-ice-100"
                            >
                                تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </nav>
            </header>

            <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:px-10">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
        @fluxScripts
    </body>
</html>