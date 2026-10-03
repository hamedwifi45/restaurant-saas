@extends('themes.oriental-theme.layout')

@section('title', 'تتبع الطلب '.$order->tracking_code.' | '.$restaurant->name)

@pushOnce('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endPushOnce

@section('content')
    @php
        $steps = [
            'pending' => ['label' => 'تم استلام الطلب', 'icon' => '✓'],
            'preparing' => ['label' => 'قيد التحضير', 'icon' => '٢'],
            'ready' => ['label' => 'جاهز للاستلام', 'icon' => '✦'],
            'on_way' => ['label' => 'في الطريق إليك', 'icon' => '➜'],
            'delivered' => ['label' => 'تم التسليم', 'icon' => '⌂'],
        ];
    @endphp
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6" x-data="orderTracking">
        <div class="text-center"><p class="oriental-text font-bold">تحديث مباشر لحالة طلبك</p><h1 class="mt-2 text-3xl font-black">طلب <span class="font-mono">{{ $order->tracking_code }}</span></h1></div>
        <div data-order-cancellation class="{{ $order->status === 'cancelled' ? '' : 'hidden' }} mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-right font-bold text-red-800" role="alert" aria-live="assertive">تعذر قبول طلبك. السبب: {{ $order->rejection_reason }}</div>
        @php($isInTransit = in_array($order->status, ['on_way', 'out_for_delivery', 'in_transit'], true))
        @if($isInTransit)
            <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $order->tracking_code, 'token' => $order->device_token]) }}"
                class="oriental-button mt-6 block rounded-2xl px-6 py-5 text-center text-lg font-black shadow-lg">
                تتبع المندوب مباشر 🚴‍♂️
            </a>
        @elseif($order->delivery_latitude && $order->delivery_longitude)
            <div class="mt-6 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-black">تتبع المندوب على الخريطة</h2>
                        <p id="tracking-status" class="mt-1 text-sm text-stone-500">جاري تحميل موقع المندوب...</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">تحديث مباشر</span>
                </div>
                <div id="tracking-map" class="h-80 min-h-[250px] w-full rounded-2xl" style="min-height: 250px; width: 100%;"></div>
            </div>
        @endif
        <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-8">
            <ol class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($steps as $status => $step)
                    <li class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full text-xl font-black" :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(status) ? 'oriental-primary text-white' : 'bg-stone-100 text-stone-400'">{{ $step['icon'] }}</span>
                        <span class="text-sm font-bold" :class="status === '{{ $status }}' ? 'oriental-text' : 'text-stone-600'">{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-8 border-t border-stone-100 pt-6">
                <h2 class="font-black">تفاصيل التوصيل</h2>
                <p class="mt-2 text-sm text-stone-600">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                @if($order->delivery_address)<p class="mt-1 text-sm text-stone-600">{{ $order->delivery_address }}</p>@endif
                <p class="mt-4 text-sm">حالة الطلب: <strong x-text="statusLabel"></strong></p>
            </div>
        </div>
        <div class="mt-5 flex justify-center gap-3">
            <a href="{{ route('order.invoice', [$restaurant->slug, $order->tracking_code]) }}" class="rounded-xl border border-stone-300 bg-white px-5 py-3 text-sm font-bold">عرض الفاتورة</a>
            @if($order->status === 'delivered')<a href="{{ route('review.create', [$restaurant->slug, $order->tracking_code]) }}" class="oriental-button rounded-xl px-5 py-3 text-sm font-bold">قيّم تجربتك</a>@endif
        </div>
    </section>
    <div
        class="fixed bottom-5 left-5 z-[100] flex w-[min(24rem,calc(100vw-2.5rem))] flex-col gap-3"
        data-order-notifications
        data-auth-url="{{ route('broadcasting.customer-auth') }}"
        data-restaurant-id="{{ $restaurant->id }}"
        data-tracking-code="{{ $order->tracking_code }}"
        x-data="orderNotificationToasts"
        @order-notification.window="show($event.detail)"
        aria-live="polite"
        aria-atomic="false"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <article class="rounded-2xl border border-stone-200 bg-white p-4 text-right shadow-xl" role="status">
                <h2 class="font-black text-stone-900" x-text="notification.title"></h2>
                <p class="mt-1 text-sm leading-6 text-stone-600" x-text="notification.message"></p>
            </article>
        </template>
    </div>
    @push('scripts')
        @if($order->delivery_latitude && $order->delivery_longitude && ! $isInTransit)
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
            <script>
                const customerPoint = [{{ $order->delivery_latitude }}, {{ $order->delivery_longitude }}];
                const trackingMap = L.map('tracking-map').setView(customerPoint, 14);
                requestAnimationFrame(() => trackingMap.invalidateSize());
                window.setTimeout(() => trackingMap.invalidateSize(), 150);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(trackingMap);
                L.marker(customerPoint).addTo(trackingMap).bindPopup('موقع التوصيل');
                let driverMarker;

                async function refreshDriverLocation() {
                    const response = await fetch(@js(route('order.track.location', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token])), { headers: { Accept: 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    if (!data.driver.latitude || !data.driver.longitude) {
                        document.getElementById('tracking-status').textContent = 'سيظهر موقع المندوب بعد بدء التوصيل.';
                        return;
                    }
                    const point = [Number(data.driver.latitude), Number(data.driver.longitude)];
                    driverMarker = driverMarker || L.marker(point).addTo(trackingMap).bindPopup('المندوب');
                    driverMarker.setLatLng(point);
                    trackingMap.fitBounds([customerPoint, point], { padding: [40, 40] });
                    document.getElementById('tracking-status').textContent = data.driver.updated_at
                        ? `آخر تحديث: ${new Date(data.driver.updated_at).toLocaleTimeString('ar')}`
                        : 'تم تحديث موقع المندوب.';
                }

                refreshDriverLocation();
                window.setInterval(refreshDriverLocation, 10000);
            </script>
        @endif
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('orderTracking', () => ({
                    status: @js($order->status),
                    statusOrder: ['pending', 'preparing', 'ready', 'on_way', 'out_for_delivery', 'in_transit', 'delivered'],
                    get statusLabel() {
                        return ({ pending: 'تم استلام الطلب', preparing: 'قيد التحضير', ready: 'جاهز للاستلام', on_way: 'في الطريق إليك', out_for_delivery: 'في الطريق إليك', in_transit: 'في الطريق إليك', delivered: 'تم التسليم' })[this.status] || this.status;
                    },
                    init() {
                        window.setInterval(async () => {
                            try {
                                const response = await fetch(@js(route('order.track.location', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token])), { headers: { Accept: 'application/json' } });
                                if (!response.ok) throw new Error('تعذر تحديث حالة الطلب.');
                                this.status = (await response.json()).status;
                            } catch (error) {
                                console.error(error);
                            }
                        }, 15000);
                    }
                }));
            });
        </script>
    @endpush
@endsection
