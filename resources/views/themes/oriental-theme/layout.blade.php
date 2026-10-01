<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $restaurant->primary_color ?? theme_setting('primary_color', '#9A3412') }}">
    <meta name="description" content="{{ $restaurant->description ?? 'اطلب أشهى الأطباق من '.$restaurant->name }}">
    <title>@yield('title', $restaurant->name.' | المذاق الشرقي')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&family=Cairo:wght@400;600;700;800&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
    <x-theme-styles :restaurant="$restaurant" />
    <style>
        :root {
            --primary-color: {{ $restaurant->primary_color ?? theme_setting('primary_color', '#9A3412') }};
            --font-family: '{{ theme_setting('font_family', 'Tajawal') }}', sans-serif;
        }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        body { font-family: var(--font-family); }
        .oriental-primary { background-color: var(--primary-color); }
        .oriental-text { color: var(--primary-color); }
        .oriental-border { border-color: var(--primary-color); }
        .oriental-button { background-color: var(--primary-color); color: white; }
        .oriental-button:hover { filter: brightness(.92); }
    </style>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    @php
        $themeCart = session('cart_'.$restaurant->id, []);
        $themeCartCount = collect($themeCart)->sum('qty');
        $themeCurrency = $restaurant->currency_symbol ?? 'ل.س';
    @endphp
    <div x-data="{ menuOpen: false, cartOpen: false }" class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="flex min-w-0 items-center gap-3">
                    @if($restaurant->logo)
                        <img src="{{ media_url($restaurant->logo) }}" alt="{{ $restaurant->name }}" class="h-11 w-11 rounded-full object-cover">
                    @else
                        <span class="oriental-primary flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-xl text-white">✦</span>
                    @endif
                    <span class="truncate text-lg font-black sm:text-xl">{{ $restaurant->name }}</span>
                </a>
                <nav class="hidden items-center gap-6 text-sm font-bold md:flex" aria-label="التنقل الرئيسي">
                    <a class="transition hover:oriental-text" href="{{ route('restaurant.home', $restaurant->slug) }}">الرئيسية</a>
                    <a class="transition hover:oriental-text" href="{{ route('restaurant.menu', $restaurant->slug) }}">القائمة</a>
                    <a class="transition hover:oriental-text" href="{{ route('offers.index', $restaurant->slug) }}">العروض</a>
                    <a class="transition hover:oriental-text" href="{{ route('order.track.form', $restaurant->slug) }}">تتبع طلب</a>
                    <a class="transition hover:oriental-text" href="{{ route('reviews.index', $restaurant->slug) }}">التقييمات</a>
                </nav>
                <div class="flex items-center gap-2">
                    <a href="{{ route('cart.index', $restaurant->slug) }}" class="relative rounded-xl border border-stone-200 px-3 py-2 text-sm font-bold transition hover:border-stone-400" aria-label="سلة التسوق">
                        <span>السلة</span>
                        @if($themeCartCount > 0)
                            <span class="oriental-primary absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs text-white">{{ $themeCartCount }}</span>
                        @endif
                    </a>
                    <button type="button" class="rounded-xl border border-stone-200 px-3 py-2 md:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-label="فتح القائمة">☰</button>
                </div>
            </div>
            <nav x-cloak x-show="menuOpen" x-transition class="border-t border-stone-100 bg-white px-4 py-3 md:hidden" aria-label="التنقل للجوال">
                <div class="mx-auto grid max-w-7xl gap-1 text-sm font-bold">
                    <a class="rounded-lg px-3 py-2 hover:bg-stone-50" href="{{ route('restaurant.home', $restaurant->slug) }}">الرئيسية</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-stone-50" href="{{ route('restaurant.menu', $restaurant->slug) }}">القائمة</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-stone-50" href="{{ route('offers.index', $restaurant->slug) }}">العروض</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-stone-50" href="{{ route('order.track.form', $restaurant->slug) }}">تتبع طلب</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-stone-50" href="{{ route('reviews.index', $restaurant->slug) }}">التقييمات</a>
                </div>
            </nav>
        </header>

        @if(session('success') || session('error'))
            <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6" role="status">
                <p class="rounded-xl border px-4 py-3 {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}">{{ session('success') ?? session('error') }}</p>
            </div>
        @endif
        <main class="flex-1">@yield('content')</main>
        <footer class="mt-12 bg-stone-950 text-stone-200">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3">
                <div>
                    <h2 class="text-lg font-black text-white">{{ $restaurant->name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-400">{{ $restaurant->description ?? 'مذاق شرقي أصيل ومكونات مختارة بعناية.' }}</p>
                </div>
                <div>
                    <h3 class="font-bold text-white">روابط سريعة</h3>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm text-stone-400">
                        <a class="hover:text-white" href="{{ route('restaurant.menu', $restaurant->slug) }}">القائمة</a>
                        <a class="hover:text-white" href="{{ route('offers.index', $restaurant->slug) }}">العروض</a>
                        <a class="hover:text-white" href="{{ route('order.track.form', $restaurant->slug) }}">تتبع الطلب</a>
                        <a class="hover:text-white" href="{{ route('reviews.index', $restaurant->slug) }}">التقييمات</a>
                    </div>
                </div>
                <div class="text-sm text-stone-400">
                    @if($restaurant->phone)<p>للطلب والاستفسار: <a class="font-bold text-white" href="tel:{{ $restaurant->phone }}">{{ $restaurant->phone }}</a></p>@endif
                    @if($restaurant->address)<p class="mt-2">{{ $restaurant->address }}</p>@endif
                    <p class="mt-3">جميع الأسعار بعملة {{ $themeCurrency }}</p>
                </div>
            </div>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
