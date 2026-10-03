@extends('themes.oriental-theme.layout')

@section('title', 'طلبات التوصيل | '.$restaurant->name)

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6" x-data="driverOrders()">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="oriental-text font-bold">{{ $restaurant->name }}</p><h1 class="mt-1 text-3xl font-black">طلبات التوصيل</h1></div><a href="{{ route('driver.dashboard', $restaurant->slug) }}" class="rounded-xl border border-stone-300 bg-white px-4 py-2.5 text-sm font-bold">لوحة المندوب</a></div>
        <p x-cloak x-show="message" x-text="message" class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="status"></p>
        <section class="mt-8">
            <h2 class="text-xl font-black">طلباتي النشطة</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @forelse($myActiveOrders as $order)
                    <article class="rounded-2xl border border-stone-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs text-stone-500">رقم التتبع</p><h3 class="font-mono text-lg font-black">{{ $order->tracking_code }}</h3></div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold">{{ $order->status === 'on_way' ? 'في الطريق' : 'تم الاستلام' }}</span></div>
                        <p class="mt-3 font-bold">{{ $order->customer_name }}</p><p class="mt-1 text-sm text-stone-600">{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p>
                        <div id="map-{{ $order->id }}" class="mt-4 h-64 w-full rounded-xl bg-stone-100" data-customer-lat="{{ $order->delivery_latitude ?? $order->customer_lat }}" data-customer-lng="{{ $order->delivery_longitude ?? $order->customer_lng }}" data-customer-name="{{ $order->customer_name }}" data-restaurant-lat="{{ $restaurant->latitude }}" data-restaurant-lng="{{ $restaurant->longitude }}"></div>
                        <ul class="mt-3 space-y-1 border-t border-stone-100 pt-3 text-sm">@foreach($order->items as $item)<li class="flex justify-between"><span>{{ $item->product?->name ?? $item->product_name }}</span><span>×{{ $item->quantity }}</span></li>@endforeach</ul>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="tel:{{ $order->customer_phone }}" class="rounded-lg border border-stone-200 px-3 py-2 text-sm font-bold">اتصال بالعميل</a>
                            <a href="{{ route('driver.order.tracking', [$restaurant->slug, $order->tracking_code]) }}" class="rounded-lg border border-stone-200 px-3 py-2 text-sm font-bold">الموقع المباشر</a>
                            @if($order->status === 'ready')
                                <button type="button" @click="transition('{{ $order->id }}', '{{ route('driver.status.update', [$restaurant->slug, $order->id]) }}', 'on_way')" :disabled="busy === '{{ $order->id }}'" class="oriental-button min-h-10 flex-1 rounded-lg px-3 py-2 text-sm font-black">في الطريق</button>
                            @elseif($order->status === 'on_way')
                                <button type="button" @click="transition('{{ $order->id }}', '{{ route('driver.status.update', [$restaurant->slug, $order->id]) }}', 'delivered')" :disabled="busy === '{{ $order->id }}'" class="oriental-button min-h-10 flex-1 rounded-lg px-3 py-2 text-sm font-black">تم التسليم</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl bg-white p-8 text-stone-500">ليس لديك طلبات نشطة.</p>
                @endforelse
            </div>
        </section>
        <section class="mt-10">
            <h2 class="text-xl font-black">طلبات جاهزة للاستلام</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @forelse($availableOrders as $order)
                    <article class="rounded-2xl border border-stone-200 bg-white p-5">
                        <p class="font-mono text-lg font-black">#{{ $order->tracking_code }}</p><p class="mt-2 font-bold">{{ $order->customer_name }}</p><p class="mt-1 text-sm text-stone-600">{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p>
                        <div id="map-{{ $order->id }}" class="mt-4 h-64 w-full rounded-xl bg-stone-100" data-customer-lat="{{ $order->delivery_latitude ?? $order->customer_lat }}" data-customer-lng="{{ $order->delivery_longitude ?? $order->customer_lng }}" data-customer-name="{{ $order->customer_name }}" data-restaurant-lat="{{ $restaurant->latitude }}" data-restaurant-lng="{{ $restaurant->longitude }}"></div>
                        <ul class="mt-3 space-y-1 border-t border-stone-100 pt-3 text-sm">@foreach($order->items as $item)<li class="flex justify-between"><span>{{ $item->product?->name ?? $item->product_name }}</span><span>×{{ $item->quantity }}</span></li>@endforeach</ul>
                        <form method="POST" action="{{ route('orders.accept', ['slug' => $restaurant->slug, 'order' => $order->id]) }}" @submit.prevent="transition('{{ $order->id }}', $event.currentTarget.action, null)" class="mt-4">
                            @csrf
                            <button type="submit" :disabled="busy === '{{ $order->id }}'" class="oriental-button min-h-11 w-full rounded-xl px-4 py-2.5 font-black">تم الاستلام</button>
                        </form>
                    </article>
                @empty
                    <p class="rounded-2xl bg-white p-8 text-stone-500">لا توجد طلبات متاحة حالياً.</p>
                @endforelse
            </div>
        </section>
    </section>
    <script>
        function driverOrders() {
            return {
                busy: null,
                message: '',
                async transition(orderId, endpoint, status) {
                    this.busy = orderId;
                    this.message = '';
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify(status ? { status } : {})
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message || 'تعذر تحديث حالة الطلب.');
                        window.location.reload();
                    } catch (error) {
                        this.message = error.message;
                        this.busy = null;
                    }
                }
            };
        }
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        if (typeof window.L !== 'undefined') {
            const customerMarkerIcon = L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41],
            });
            const restaurantName = @js($restaurant->name);
            const readCoordinate = (value) => {
                if (value === undefined || value.trim() === '') return null;
                const coordinate = Number(value);
                return Number.isFinite(coordinate) ? coordinate : null;
            };

            document.querySelectorAll('[id^="map-"]').forEach((mapElement) => {
                const customerLat = readCoordinate(mapElement.dataset.customerLat);
                const customerLng = readCoordinate(mapElement.dataset.customerLng);
                const restaurantLat = readCoordinate(mapElement.dataset.restaurantLat);
                const restaurantLng = readCoordinate(mapElement.dataset.restaurantLng);
                const hasCustomerCoords = customerLat !== null && customerLng !== null
                    && Math.abs(customerLat) <= 90 && Math.abs(customerLng) <= 180;
                const hasRestaurantCoords = restaurantLat !== null && restaurantLng !== null
                    && Math.abs(restaurantLat) <= 90 && Math.abs(restaurantLng) <= 180;
                const initialCenter = hasCustomerCoords
                    ? [customerLat, customerLng]
                    : hasRestaurantCoords ? [restaurantLat, restaurantLng] : [24.7136, 46.6753];
                const cardMap = L.map(mapElement).setView(initialCenter, hasCustomerCoords ? 15 : 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(cardMap);

                if (hasCustomerCoords) {
                    const popup = document.createElement('span');
                    popup.textContent = `موقع توصيل الزبون: ${mapElement.dataset.customerName}`;
                    L.marker([customerLat, customerLng], { icon: customerMarkerIcon }).addTo(cardMap).bindPopup(popup);
                }

                if (hasRestaurantCoords) {
                    L.marker([restaurantLat, restaurantLng]).addTo(cardMap).bindPopup(restaurantName);
                }

                window.setTimeout(() => cardMap.invalidateSize(), 300);
            });
        } else {
            console.error('Leaflet is not available; driver order maps could not be initialized.');
        }
    </script>
@endsection
