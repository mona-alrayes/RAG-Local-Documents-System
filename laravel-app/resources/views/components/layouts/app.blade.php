{{-- القالب المشترك لجميع صفحات المستخدم بعد تسجيل الدخول --}}
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
        {{-- Sidebar الرئيسي --}}
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-white/10 bg-navy-900"
        >
            <flux:sidebar.header>
                <a
                    href="{{ route('workspace') }}"
                    class="min-w-0"
                    aria-label="الانتقال إلى مساحة العمل"
                >
                    <x-brand compact />
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            {{-- التنقل الرئيسي --}}
            <flux:sidebar.nav aria-label="التنقل الرئيسي">
                <flux:sidebar.item
                    icon="home"
                    href="{{ route('workspace') }}"
                    :current="request()->routeIs('workspace')"
                >
                    مساحة العمل
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="document-text"
                    href="{{ route('documents.index') }}"
                    :current="request()->routeIs('documents.*')"
                >
                    الوثائق
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            {{-- إعدادات المستخدم --}}
            <flux:sidebar.nav aria-label="إعدادات المستخدم">
                <flux:sidebar.item
                    icon="cog-6-tooth"
                    href="{{ route('settings.account') }}"
                    :current="request()->routeIs('settings.*')"
                >
                    إعدادات الحساب
                </flux:sidebar.item>
            </flux:sidebar.nav>

            {{-- هوية المستخدم وتسجيل الخروج --}}
            <div class="mt-4 border-t border-white/10 pt-4">
                <div class="mb-3 min-w-0 px-2">
                    <p class="truncate text-sm font-medium text-ice-100">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mt-1 text-xs text-mist-300">
                        حساب المستخدم
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-mist-300 transition hover:bg-white/5 hover:text-ice-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-400"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            aria-hidden="true"
                        >
                            <path d="M14.25 8.25 18 12m0 0-3.75 3.75M18 12H7.5" />
                            <path d="M10.5 5.25V4.5A1.5 1.5 0 0 0 9 3H4.5A1.5 1.5 0 0 0 3 4.5v15A1.5 1.5 0 0 0 4.5 21H9a1.5 1.5 0 0 0 1.5-1.5v-.75" />
                        </svg>

                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </flux:sidebar>

        {{-- Header يظهر فقط على الشاشات الصغيرة --}}
        <flux:header class="border-b border-white/10 bg-navy-900 lg:hidden">
            <flux:sidebar.toggle
                class="lg:hidden"
                icon="bars-2"
                inset="left"
            />

            <p class="min-w-0 truncate text-sm font-semibold text-ice-100">
                {{ $title }}
            </p>

            <flux:spacer />

            <a
                href="{{ route('workspace') }}"
                aria-label="الانتقال إلى مساحة العمل"
            >
                <x-brand
                    compact
                    class="gap-0 [&>div]:hidden [&>span]:size-9 [&>span]:rounded-md"
                />
            </a>
        </flux:header>

        {{-- محتوى الصفحة --}}
        <flux:main class="min-w-0 overflow-x-clip">
            <div class="mx-auto w-full max-w-7xl px-5 py-10 sm:px-8 lg:px-10">
                {{ $slot }}
            </div>
        </flux:main>

        @livewireScripts
        @fluxScripts
    </body>
</html>