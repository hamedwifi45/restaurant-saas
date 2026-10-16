<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مركز التوصيل | {{ $restaurant->name }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 p-4 text-slate-900 sm:p-6">
    <main class="mx-auto max-w-7xl space-y-8">
        
        <!-- الهيدر الرئيسي -->
        <header class="flex flex-col gap-5 rounded-3xl border border-slate-800 bg-slate-900 p-6 text-white shadow-2xl sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-orange-400">{{ $restaurant->name }} - مركز التوصيل</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">لوحة تحكم المندوب</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-300">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_14px_rgba(52,211,153,0.8)]"></span>
                <span>متصل</span>
                @if($myActiveOrders->isNotEmpty())
                    <span class="rounded-full bg-amber-500/20 px-3 py-1 font-bold text-amber-400 border border-amber-500/30">
                        {{ $myActiveOrders->count() }} طلب قيد التوصيل
                    </span>
                @endif
                <span class="rounded-full bg-slate-800 px-3 py-1 font-bold">{{ $availableOrders->count() }} طلب متاح</span>
            </div>
        </header>

        <!-- ========================================================= -->
        <!-- 1. قسم طلبات المندوب الحالية (مميزة بلون ونمط تنبيهي) -->
        <!-- ========================================================= -->
        @if($myActiveOrders->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-3 w-3 relative">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <h2 class="text-xl font-black text-amber-400">طلباتك الحالية المكلف بها (يلزم إنهاؤها)</h2>
                </div>

                @foreach ($myActiveOrders as $order)
                    <article class="overflow-hidden rounded-3xl border-2 border-amber-500/80 bg-gradient-to-br from-slate-900 via-amber-950/20 to-slate-900 shadow-[0_0_25px_rgba(245,158,11,0.15)]" data-order="{{ $order->id }}" data-status="{{ $order->status }}">
                        <div class="grid lg:grid-cols-[minmax(0,1fr)_390px]">
                            <!-- خريطة موقع المشتري -->
                            <div class="h-64 w-full min-h-[360px] rounded-xl bg-slate-800" id="map-{{ $order->id }}"
                                 style="min-height: 250px; height: 100%; width: 100%;"
                                 data-customer-lat="{{ $order->latitude ?? $order->delivery_latitude ?? $order->customer_lat ?? $order->customer_latitude ?? $order->lat }}"
                                 data-customer-lng="{{ $order->longitude ?? $order->delivery_longitude ?? $order->customer_lng ?? $order->customer_longitude ?? $order->lng }}"
                                 data-customer-name="{{ $order->customer_name }}"
                                 data-restaurant-lat="{{ $restaurant->latitude }}"
                                 data-restaurant-lng="{{ $restaurant->longitude }}">
                            </div>

                            <div class="flex flex-col gap-5 p-5 text-white sm:p-7">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest text-amber-400">طلبك الحالي</p>
                                        <h2 class="mt-1 text-2xl font-black">طلب {{ $order->tracking_code }}</h2>
                                    </div>
                                    <span class="shrink-0 animate-pulse rounded-full bg-amber-500/30 px-3.5 py-1.5 text-xs font-black text-amber-300 border border-amber-500/50">
                                        <i class="fa-solid fa-triangle-exclamation ml-1" aria-hidden="true"></i> {{ $order->status === 'on_way' ? 'جاري التوصيل للزبون' : 'قيد الاستلام' }}
                                    </span>
                                </div>

                                <div class="space-y-3 border-y border-amber-500/30 py-4 text-sm">
                                    <div class="flex gap-3 items-center">
                                        <i class="fa-solid fa-user text-lg text-amber-400" aria-hidden="true"></i>
                                        <span class="font-bold text-amber-100">{{ $order->customer_name }}</span>
                                        <a class="mr-auto rounded-lg bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-300 underline border border-amber-500/30" href="tel:{{ $order->customer_phone }}">اتصال</a>
                                    </div>
                                    <div class="flex gap-3 text-slate-300">
                                        <i class="fa-solid fa-location-dot text-lg text-amber-400" aria-hidden="true"></i>
                                        <span>{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <p class="mb-2 text-xs font-black uppercase tracking-wider text-amber-400/80">محتويات الطلب</p>
                                    <div class="space-y-2 text-sm">
                                        @foreach ($order->items as $item)
                                            <div class="flex justify-between">
                                                <span>{{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif</span>
                                                <span class="font-bold text-amber-400">×{{ $item->quantity }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-auto space-y-3 pt-2">
                                    <!-- رابط التتبع الخارجي للطلب -->
                                    <a href="{{ route('driver.order.tracking', [$restaurant->slug,$order->tracking_code]) }}" class="block w-full text-center rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-xs font-bold text-amber-300 hover:bg-amber-500/20 transition">
                                        <i class="fa-solid fa-map-location-dot ml-1" aria-hidden="true"></i> فتح الخريطة المباشرة للتتبع
                                    </a>

                                    @unless(auth()->user()->isRestaurantAdmin())
                                        <!-- زر تأكيد التسليم -->
                                        <button type="button" data-deliver-order="{{ $order->id }}" class="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-black text-white transition hover:bg-emerald-500 active:scale-[0.99] shadow-lg shadow-emerald-900/40">
                                            <i class="fa-solid fa-circle-check ml-1" aria-hidden="true"></i> تأكيد تسليم الطلب للعميل
                                        </button>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        <!-- ========================================================= -->
        <!-- 2. قسم الطلبات المتاحة للاستلام (العادية) -->
        <!-- ========================================================= -->
        <section class="space-y-4 pt-4 border-t border-slate-800">
            <h2 class="text-xl font-black text-slate-200">الطلبات المتاحة للقبول والتوصيل</h2>

            @forelse ($availableOrders as $order)
                <article class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60 shadow-xl transition hover:border-slate-700" data-order="{{ $order->id }}" data-status="{{ $order->status }}">
                    <div class="grid lg:grid-cols-[minmax(0,1fr)_390px]">
                        <!-- خريطة موقع المشتري -->
                        <div class="h-64 w-full min-h-[360px] rounded-xl bg-slate-800" id="map-{{ $order->id }}"
                             data-customer-lat="{{ $order->latitude ?? $order->delivery_latitude ?? $order->customer_lat ?? $order->customer_latitude ?? $order->lat }}"
                             data-customer-lng="{{ $order->longitude ?? $order->delivery_longitude ?? $order->customer_lng ?? $order->customer_longitude ?? $order->lng }}"
                             data-customer-name="{{ $order->customer_name }}"
                             data-restaurant-lat="{{ $restaurant->latitude }}"
                             data-restaurant-lng="{{ $restaurant->longitude }}">
                        </div>

                        <div class="flex flex-col gap-5 p-5 text-white sm:p-7">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-orange-500">{{ $order->restaurant?->name }}</p>
                                    <h2 class="mt-1 text-xl font-black">طلب {{ $order->tracking_code }}</h2>
                                </div>
                                <span class="shrink-0 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-black text-emerald-400 border border-emerald-500/30">
                                    <i class="fa-solid fa-fire ml-1" aria-hidden="true"></i> متاح للاستلام
                                </span>
                            </div>

                            <div class="space-y-3 border-y border-slate-800 py-4 text-sm">
                                <div class="flex gap-3">
                                    <span class="text-orange-500">●</span>
                                    <span class="font-bold">{{ $order->customer_name }}</span>
                                    <a class="mr-auto text-orange-500 underline" href="tel:{{ $order->customer_phone }}">اتصال</a>
                                </div>
                                <div class="flex gap-3 text-slate-400">
                                    <span class="text-orange-500">⌖</span>
                                    <span>{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</span>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-black uppercase tracking-wider text-slate-400">محتويات الطلب</p>
                                <div class="space-y-2 text-sm">
                                    @foreach ($order->items as $item)
                                        <div class="flex justify-between">
                                            <span>{{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif</span>
                                            <span class="font-bold text-slate-400">×{{ $item->quantity }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @unless(auth()->user()->isRestaurantAdmin())
                                <div class="mt-auto space-y-2">
                                    <form action="{{ route('orders.accept', ['slug' => $restaurant->slug, 'order' => $order->id]) }}" method="POST" data-accept-order="{{ $order->id }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl bg-orange-600 px-4 py-3.5 font-black text-white transition hover:bg-orange-500 active:scale-[0.99]">
                                            قبول وتوصيل الطلب
                                        </button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-800 bg-slate-900/40 p-12 text-center text-slate-400">
                    لا توجد طلبات جديدة متاحة حالياً.
                </div>
            @endforelse
        </section>
    </main>

    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/offline-map-tiles.js') }}"></script>
    <script>
        const bindDriverOrderEcho = () => {
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                window.Echo.private(`restaurant.{{ $restaurant->id }}.drivers`)
                    .listen('.order.ready', (e) => {
                        const audio = new Audio('/assets/sounds/new-order.mp3');
                        audio.play().catch(() => {});

                        const alertBox = document.createElement('div');
                        alertBox.className = "fixed top-5 left-1/2 -translate-x-1/2 z-50 bg-emerald-600 text-white px-6 py-3 rounded-2xl font-bold shadow-2xl animate-bounce";
                        const alertIcon = document.createElement('i');
                        alertIcon.className = 'fa-solid fa-fire ml-2';
                        alertIcon.setAttribute('aria-hidden', 'true');
                        alertBox.append(alertIcon, document.createTextNode(`طلب جديد جاهز للتوصيل: #${e.orderData.tracking_code}`));
                        document.body.appendChild(alertBox);

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    });
            } else {
                console.warn('Echo instance not found on window. Skipping WebSocket binding.');
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindDriverOrderEcho, { once: true });
        } else {
            bindDriverOrderEcho();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const restaurantSlug = @js($restaurant->slug);
        const restaurantName = @js($restaurant->name);

        const request = (url, options = {}) => fetch(url, {
            ...options,
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken, 
                'Accept': 'application/json', 
                ...(options.headers || {}) 
            },
        });

        if (typeof window.L !== 'undefined') {
            const customerMarkerIcon = L.icon({
                iconUrl: @json(asset('vendor/leaflet/images/marker-icon.png')),
                iconRetinaUrl: @json(asset('vendor/leaflet/images/marker-icon-2x.png')),
                shadowUrl: @json(asset('vendor/leaflet/images/marker-shadow.png')),
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41],
            });

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
                const map = L.map(mapElement, { zoomControl: false }).setView(initialCenter, hasCustomerCoords ? 15 : 13);

                L.control.zoom({ position: 'bottomright' }).addTo(map);
                window.addOfflineAwareMapTiles(map);

                if (hasCustomerCoords) {
                    const popup = document.createElement('span');
                    popup.textContent = `موقع توصيل الزبون: ${mapElement.dataset.customerName}`;
                    L.marker([customerLat, customerLng], { icon: customerMarkerIcon })
                        .addTo(map)
                        .bindPopup(popup);
                }

                if (hasRestaurantCoords) {
                    L.marker([restaurantLat, restaurantLng])
                        .addTo(map)
                        .bindPopup(restaurantName);
                }

                window.setTimeout(() => map.invalidateSize(), 300);
            });
        } else {
            console.error('Leaflet is not available; driver order maps could not be initialized.');
        }

        document.querySelectorAll('form[data-accept-order]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'جاري قبول الطلب...';

                try {
                    const response = await request(form.action, {
                        method: 'POST'
                    });

                    const result = await response.json().catch(() => ({}));

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert(result.message || result.error || 'تعذر قبول الطلب.');
                        button.disabled = false;
                        button.textContent = 'قبول وتوصيل الطلب';
                    }
                } catch (err) {
                    alert('حدث خطأ أثناء الاتصال بالخادم.');
                    button.disabled = false;
                    button.textContent = 'قبول وتوصيل الطلب';
                }
            });
        });

        // تأكيد التسليم
        document.querySelectorAll('[data-deliver-order]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!window.confirm('هل تريد تأكيد تسليم هذا الطلب واستلام المبلغ؟')) return;
                
                const orderId = button.dataset.deliverOrder;
                button.disabled = true;

                try {
                    const response = await request(`/${restaurantSlug}/driver/orders/${orderId}/status`, { 
                        method: 'POST', 
                        body: JSON.stringify({ status: 'delivered' }) 
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('تعذر تغيير حالة الطلب.');
                        button.disabled = false;
                    }
                } catch (err) {
                    alert('حدث خطأ أثناء الاتصال بالخادم.');
                    button.disabled = false;
                }
            });
        });
    </script>
</body>
</html>