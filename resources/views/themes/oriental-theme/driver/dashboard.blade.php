@extends('themes.oriental-theme.layout')

@section('title', 'لوحة المندوب | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
        <header class="rounded-3xl bg-stone-950 p-6 text-white sm:p-8">
            <p class="text-sm font-bold text-amber-300">{{ $restaurant->name }} · مركز التوصيل</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div><h1 class="text-3xl font-black">أهلاً {{ $driver->name }}</h1><p class="mt-2 text-stone-300">تابع رحلاتك وابقَ على تواصل مع عملائك.</p></div>
                <a href="{{ route('driver.orders', $slug) }}" class="oriental-button rounded-xl px-5 py-3 font-bold">عرض الطلبات</a>
            </div>
        </header>
        <div class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-stone-200 bg-white p-5"><p class="text-sm text-stone-500">الطلبات النشطة</p><strong class="mt-2 block text-3xl font-black">{{ $stats['active'] }}</strong></article>
            <article class="rounded-2xl border border-stone-200 bg-white p-5"><p class="text-sm text-stone-500">تم تسليمها اليوم</p><strong class="mt-2 block text-3xl font-black">{{ $stats['deliveredToday'] }}</strong><p class="mt-1 text-xs text-stone-400">من {{ $stats['totalToday'] }} طلب اليوم</p></article>
            <article class="rounded-2xl border border-stone-200 bg-white p-5"><p class="text-sm text-stone-500">الأرباح المسجلة</p><strong class="mt-2 block text-2xl font-black">{{ isset($driver->earnings) ? number_format($driver->earnings, 2).' '.($restaurant->currency_symbol ?? 'ل.س') : 'تُحدّد من إدارة المطعم' }}</strong><p class="mt-1 text-xs text-stone-400">حسب سجل حسابك</p></article>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-black">الرحلات الحالية</h2><p class="mt-1 text-sm text-stone-500">الطلبات المسندة إليك</p></div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">{{ $activeOrders->count() }} نشطة</span></div>
                <div class="mt-4 divide-y divide-stone-100">
                    @forelse($activeOrders as $order)
                        <article class="py-4 first:pt-0">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-black">#{{ $order->tracking_code }}</h3><p class="mt-1 text-sm">{{ $order->customer_name }}</p><p class="mt-1 text-sm text-stone-500">{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p></div><span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-bold">{{ $order->status === 'on_way' ? 'في الطريق' : 'جاهز للاستلام' }}</span></div>
                            <div class="mt-3 flex gap-2"><a href="tel:{{ $order->customer_phone }}" class="rounded-lg border border-stone-200 px-3 py-2 text-sm font-bold">اتصال</a><a href="{{ route('driver.order.tracking', [$slug, $order->tracking_code]) }}" class="oriental-text rounded-lg border border-stone-200 px-3 py-2 text-sm font-bold">فتح التتبع</a></div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-stone-500">لا توجد رحلات نشطة الآن.</p>
                    @endforelse
                </div>
            </section>
            <section class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                <div class="flex items-center justify-between"><div><h2 class="text-xl font-black">آخر التسليمات</h2><p class="mt-1 text-sm text-stone-500">أحدث الطلبات المكتملة</p></div><span class="text-sm text-stone-400">{{ $recentOrders->count() }} طلبات</span></div>
                <div class="mt-4 divide-y divide-stone-100">
                    @forelse($recentOrders as $order)
                        <div class="flex items-center justify-between gap-3 py-4 first:pt-0"><div><p class="font-bold">#{{ $order->tracking_code }}</p><p class="mt-1 text-xs text-stone-500">{{ $order->customer_name }} · {{ $order->updated_at?->diffForHumans() }}</p></div><span class="text-sm font-bold text-emerald-700">تم التسليم</span></div>
                    @empty
                        <p class="py-8 text-center text-sm text-stone-500">لا توجد طلبات مكتملة بعد.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
