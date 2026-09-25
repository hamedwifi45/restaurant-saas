<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لوحة المندوب</title>
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #driver-map { min-height: 330px; }
        .dashboard-grid { grid-template-columns: minmax(0, 1fr) 360px; }
        @media (max-width: 1024px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="min-h-screen bg-[#f4f6f8] text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-500 text-xl text-white shadow-lg shadow-orange-500/20">⌁</div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Delivery desk</p>
                    <h1 class="text-xl font-black">لوحة المندوب</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden text-left sm:block">
                    <p class="text-xs text-slate-400">مرحبًا بك</p>
                    <p class="text-sm font-black">{{ $driver->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 transition hover:border-orange-300 hover:text-orange-600">خروج</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:py-8">
        <section class="flex flex-col justify-between gap-4 rounded-3xl bg-slate-900 p-6 text-white shadow-xl sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-orange-300">{{ now()->translatedFormat('l، d F Y') }}</p>
                <h2 class="mt-2 text-3xl font-black tracking-tight">جاهز لرحلتك القادمة؟</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-400">تابع طلباتك، شارك موقعك، وسجّل التسليم من مكان واحد.</p>
            </div>
            <a href="{{ route('driver.orders', ['slug']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-black text-white transition hover:bg-orange-600">عرض كل الطلبات <span>←</span></a>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><span class="text-sm font-bold text-slate-500">الطلبات النشطة</span><span class="text-xl text-orange-500">◉</span></div><strong class="mt-3 block text-3xl font-black">{{ $stats['active'] }}</strong><p class="mt-1 text-xs text-slate-400">تحتاج إلى متابعة الآن</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><span class="text-sm font-bold text-slate-500">تم التسليم اليوم</span><span class="text-xl text-emerald-500">✓</span></div><strong class="mt-3 block text-3xl font-black">{{ $stats['deliveredToday'] }}</strong><p class="mt-1 text-xs text-slate-400">من أصل {{ $stats['totalToday'] }} طلب اليوم</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><span class="text-sm font-bold text-slate-500">حالة الحساب</span><span class="h-3 w-3 rounded-full bg-emerald-500"></span></div><strong class="mt-3 block text-2xl font-black">متصل</strong><p class="mt-1 text-xs text-slate-400">يمكنك مشاركة موقعك الآن</p></div>
        </section>

        <section class="dashboard-grid grid gap-5">
            <div class="space-y-5">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6"><div><h2 class="text-xl font-black">الرحلات الحالية</h2><p class="mt-1 text-sm text-slate-500">الطلبات المسندة إليك والمفتوحة</p></div><span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-black text-orange-700">{{ $activeOrders->count() }} نشطة</span></div>
                    @forelse ($activeOrders as $order)
                        <article class="border-b border-slate-100 p-5 last:border-0 sm:p-6">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black">#{{ $order->tracking_code }}</h3><span class="rounded-full px-3 py-1 text-xs font-black {{ $order->status === 'on_way' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->status === 'on_way' ? 'في الطريق' : 'جاهز للاستلام' }}</span></div><p class="mt-2 text-sm font-bold text-slate-700">{{ $order->customer_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p></div>
                                <div class="flex shrink-0 gap-2"><a href="tel:{{ $order->customer_phone }}" aria-label="الاتصال بالعميل" class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-700 hover:bg-emerald-100">☎</a><a href="{{ route('driver.order.tracking', $order) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-black text-white hover:bg-slate-700">فتح التتبع <span>←</span></a></div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-400"><span>المطعم: {{ $order->restaurant?->name }}</span><span>الدفع: {{ $order->payment_method === 'cash' ? 'نقدي' : 'تحويل' }}</span><span>{{ $order->items->count() }} أصناف</span></div>
                        </article>
                    @empty
                        <div class="p-12 text-center"><div class="text-4xl text-slate-300">✓</div><h3 class="mt-3 font-black">لا توجد رحلات مفتوحة</h3><p class="mt-1 text-sm text-slate-500">ستظهر الطلبات الجديدة هنا عند إسنادها إليك.</p></div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-center justify-between"><div><h2 class="text-lg font-black">آخر التسليمات</h2><p class="mt-1 text-sm text-slate-500">سجل رحلاتك المكتملة</p></div><span class="text-xs font-bold text-slate-400">آخر 5 طلبات</span></div><div class="mt-5 divide-y divide-slate-100">@forelse ($recentOrders as $order)<div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"><div><p class="text-sm font-black">#{{ $order->tracking_code }}</p><p class="mt-1 text-xs text-slate-500">{{ $order->customer_name }} · {{ $order->updated_at?->diffForHumans() }}</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">مكتمل</span></div>@empty<p class="py-5 text-center text-sm text-slate-400">لا توجد تسليمات مكتملة بعد.</p>@endforelse</div></div>
            </div>

            <aside class="space-y-5">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 p-5"><div class="flex items-center justify-between"><h2 class="font-black">الموقع المباشر</h2><span class="flex items-center gap-2 text-xs font-bold text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> مباشر</span></div></div><div id="driver-map" class="bg-slate-200"></div><div class="p-4">@if($featuredOrder)<p class="text-xs text-slate-500">الطلب المحدد: <strong class="text-slate-900">#{{ $featuredOrder->tracking_code }}</strong></p><a href="{{ route('driver.order.tracking', $featuredOrder) }}" class="mt-3 block w-full rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-black text-slate-700 hover:border-orange-300 hover:text-orange-600">فتح تفاصيل الرحلة</a>@else<p class="text-center text-sm text-slate-400">لا توجد رحلة لعرضها على الخريطة.</p>@endif</div></div>
                <div class="rounded-3xl bg-orange-500 p-6 text-white shadow-lg shadow-orange-500/20"><p class="text-sm font-bold text-orange-100">تحتاج مساعدة؟</p><h2 class="mt-2 text-xl font-black">تواصل مع المطعم</h2><p class="mt-2 text-sm leading-6 text-orange-100">يمكنك الاتصال بالمطعم من تفاصيل الطلب عند الحاجة.</p>@if($featuredOrder)<a href="tel:{{ $featuredOrder->restaurant?->phone }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-black text-orange-600 hover:bg-orange-50">☎ اتصال بالمطعم</a>@endif</div>
            </aside>
        </section>
    </main>

    @if($featuredOrder)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            const map = L.map('driver-map', { zoomControl: false }).setView([33.5138, 36.2765], 13);
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors', maxZoom: 19 }).addTo(map);
            const customer = @json([$featuredOrder->delivery_latitude, $featuredOrder->delivery_longitude]);
            const driver = @json([$featuredOrder->driver_latitude, $featuredOrder->driver_longitude]);
            const points = [customer, driver].filter((point) => point[0] !== null && point[1] !== null).map((point) => point.map(Number));
            points.forEach((point, index) => L.marker(point).addTo(map).bindPopup(index === 0 ? 'موقع العميل' : 'موقع المندوب'));
            if (points.length) map.fitBounds(points, { padding: [30, 30] });
        </script>
    @endif
</body>
</html>