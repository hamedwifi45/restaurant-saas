@props(['title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | مطعمك' : 'مطعمك' }}</title>
    @vite(['resources/css/auth.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-orange-50 font-sans text-zinc-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-orange-100 bg-white shadow-2xl shadow-orange-950/10 lg:grid-cols-[1fr_1.1fr]">
            <aside class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-orange-600 via-orange-500 to-amber-400 p-10 text-white lg:flex">
                <div class="absolute -left-20 -top-20 size-72 rounded-full border-[36px] border-white/10"></div>
                <div class="absolute -bottom-28 -right-16 size-80 rounded-full border-[48px] border-white/10"></div>
                <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-2xl font-black">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-white/15 text-2xl" aria-hidden="true">م</span>
                    <span>مطعمك</span>
                </a>
                <div class="relative space-y-4">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-orange-100">منصتك لإدارة المطعم</p>
                    <h1 class="text-4xl font-black leading-tight">كل أعمال مطعمك،<br>في مكان واحد.</h1>
                    <p class="max-w-sm text-base leading-8 text-orange-50">إدارة الطلبات والتوصيل والفريق أصبحت أسهل وأسرع.</p>
                </div>
                <p class="relative text-sm text-orange-100">© {{ now()->year }} مطعمك</p>
            </aside>

            <section class="flex items-center justify-center p-6 sm:p-10 lg:p-14">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 text-xl font-black text-orange-700 lg:hidden">
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-orange-100 text-2xl" aria-hidden="true">م</span>
                        <span>مطعمك</span>
                    </a>
                    {{ $slot }}
                </div>
            </section>
        </div>
    </main>
    @livewireScripts
    @stack('scripts')
</body>
</html>
