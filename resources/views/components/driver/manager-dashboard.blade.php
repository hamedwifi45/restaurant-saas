@props(['restaurant', 'metrics', 'drivers', 'dispatchedOrders'])

<section dir="rtl" class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-slate-950 p-6 text-white sm:p-8">
        <div>
            <p class="text-sm font-bold text-amber-300">{{ $restaurant->name }} · مركز التوصيل</p>
            <h1 class="mt-2 text-3xl font-black">لوحة التوصيل والمندوبين</h1>
            <p class="mt-2 text-sm text-slate-300">ملخص مباشر للطلبات والسائقين المسجلين في مطعمك.</p>
        </div>
        <a href="{{ route('driver.dashboard', ['slug' => $restaurant->slug]) }}"
            class="rounded-xl bg-amber-400 px-5 py-3 text-center font-black text-slate-950 transition hover:bg-amber-300">
            تحديث البيانات
        </a>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <p class="text-sm font-bold text-blue-800">طلبات التوصيل النشطة</p>
            <strong class="mt-2 block text-3xl font-black text-slate-950"><span data-metric="active-delivery-orders">{{ number_format($metrics['active']) }}</span></strong>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-sm font-bold text-emerald-800">طلبات التوصيل المكتملة</p>
            <strong class="mt-2 block text-3xl font-black text-slate-950"><span data-metric="completed-delivery-orders">{{ number_format($metrics['completed']) }}</span></strong>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-bold text-amber-900">طلبات في الطريق</p>
            <strong class="mt-2 block text-3xl font-black text-slate-950"><span data-metric="dispatched-delivery-orders">{{ number_format($metrics['dispatched']) }}</span></strong>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-bold text-slate-600">عدد المندوبين</p>
            <strong class="mt-2 block text-3xl font-black text-slate-950">{{ number_format($metrics['drivers']) }}</strong>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-xl font-black text-slate-950">حالة المندوبين</h2>
                <p class="mt-1 text-sm text-slate-500">حالة التوفر والطلبات المسندة لكل مندوب.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($drivers as $driver)
                    @php($driverStatus = [
                        'available' => ['متاح', 'bg-emerald-100 text-emerald-800'],
                        'on_delivery' => ['في التوصيل', 'bg-amber-100 text-amber-900'],
                        'offline' => ['غير متصل', 'bg-slate-100 text-slate-600'],
                    ][$driver['status']])
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-bold text-slate-900">{{ $driver['name'] }}</p>
                            @if($driver['phone'])
                                <p class="mt-1 text-sm text-slate-500" dir="ltr">{{ $driver['phone'] }}</p>
                            @endif
                            @if($driver['current_order'])
                                <p class="mt-1 text-xs font-bold text-slate-600">الطلب الحالي: #{{ $driver['current_order']->tracking_code }}</p>
                            @endif
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $driverStatus[1] }}">{{ $driverStatus[0] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">لا يوجد مندوبون مسجلون لهذا المطعم.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-xl font-black text-slate-950">الطلبات المرسلة للتوصيل</h2>
                <p class="mt-1 text-sm text-slate-500">الطلبات الجارية والمندوب المسؤول عنها.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($dispatchedOrders as $order)
                    <article class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                        <div>
                            <p class="font-mono font-black text-slate-900">#{{ $order->tracking_code }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-700">{{ $order->customer_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p>
                            <p class="mt-2 text-xs font-bold text-slate-600">
                                المندوب: {{ $order->driverUser?->name ?? 'غير محدد' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">في الطريق</span>
                            <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $order->tracking_code]) }}"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                فتح التتبع
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">لا توجد طلبات قيد التوصيل حالياً.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
