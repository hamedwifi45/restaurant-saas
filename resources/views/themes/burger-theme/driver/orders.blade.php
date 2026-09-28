<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مركز التوصيل | {{ $restaurant->name }}</title>
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 p-4 text-slate-900 sm:p-6">
    <main class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-5 rounded-3xl border border-slate-800 bg-slate-900 p-6 text-white shadow-2xl sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-orange-400">{{ $restaurant->name }} - مركز التوصيل</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">الطلبات المتاحة للتوصيل</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-300">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_14px_rgba(52,211,153,0.8)]"></span>
                <span>متصل</span>
                <span class="rounded-full bg-slate-800 px-3 py-1 font-bold">{{ $orders->count() }} طلب</span>
            </div>
        </header>

        @forelse ($orders as$order)
            <article class="overflow-hidden rounded-3xl border {{ is_null($order->driver_id) ? 'border-amber-400/50 bg-slate-900/40' : 'border-slate-200 bg-white' }} shadow-xl" data-order="{{ $order->id }}" data-status="{{ $order->status }}">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_390px]">
                    <!-- خريطة موقع المشتري فقط -->
                    <div class="min-h-[360px] bg-slate-200" 
                         id="map-{{ $order->id }}"
                         data-customer-lat="{{ $order->latitude ?? $order->customer_latitude ?? $order->lat ?? $order->delivery_latitude }}"
                         data-customer-lng="{{ $order->longitude ?? $order->customer_longitude ?? $order->lng ?? $order->delivery_longitude }}">
                    </div>

                    <div class="flex flex-col gap-5 p-5 sm:p-7 {{ is_null($order->driver_id) ? 'text-white' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">{{ $order->restaurant?->name }}</p>
                                <h2 class="mt-1 text-xl font-black">طلب {{ $order->tracking_code }}</h2>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                @if (is_null($order->driver_id))
                                    <span class="shrink-0 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-black text-amber-400 border border-amber-500/30">🔥 متاح للاستلام</span>
                                @elseif ($order->driver_id === auth()->id())
                                    <span class="shrink-0 rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">
                                        قيد التوصيل
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3 border-y {{ is_null($order->driver_id) ? 'border-slate-800' : 'border-slate-100' }} py-4 text-sm">
                            <div class="flex gap-3">
                                <span class="text-orange-500">●</span>
                                <span class="font-bold">{{ $order->customer_name }}</span>
                                <a class="mr-auto text-orange-500 underline" href="tel:{{ $order->customer_phone }}">اتصال</a>
                            </div>
                            <div class="flex gap-3 {{ is_null($order->driver_id) ? 'text-slate-400' : 'text-slate-600' }}">
                                <span class="text-orange-500">⌖</span>
                                <span>{{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</span>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-black uppercase tracking-wider text-slate-400">محتويات الطلب</p>
                            <div class="space-y-2 text-sm">
                                @foreach ($order->items as $item)
                                    <div class="flex justify-between">
                                        <span>{{ $item->product?->name ?? 'منتج محذوف' }}</span>
                                        <span class="font-bold {{ is_null($order->driver_id) ? 'text-slate-400' : 'text-slate-500' }}">×{{ $item->quantity }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-auto space-y-2">
                            @if (is_null($order->driver_id))
                                <button type="button" data-accept-order="{{ $order->id }}" class="w-full rounded-xl bg-emerald-600 px-4 py-3.5 font-black text-white transition hover:bg-emerald-500 active:scale-[0.99]">
                                    قبول وتوصيل الطلب
                                </button>
                            @elseif ($order->driver_id === auth()->id())
                                <button type="button" data-deliver-order="{{ $order->id }}" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-black text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50">
                                    تأكيد التسليم
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-800 bg-slate-900 p-16 text-center text-slate-400">
                لا توجد طلبات توصيل متاحة حالياً لهذا المطعم.
            </div>
        @endforelse
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const restaurantSlug = "{{ $restaurant->slug }}";

        const request = (url, options = {}) => fetch(url, {
            ...options,
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken, 
                'Accept': 'application/json', 
                ...(options.headers || {}) 
            },
        });

        document.querySelectorAll('[data-order]').forEach((card) => {
            const orderId = card.dataset.order;
            const mapElement = document.getElementById(`map-${orderId}`);
            
            const custLat = parseFloat(mapElement.dataset.customerLat);
            const custLng = parseFloat(mapElement.dataset.customerLng);
            const hasCustomerCoords = !isNaN(custLat) && !isNaN(custLng);

            const initialCenter = hasCustomerCoords ? [custLat, custLng] : [24.7136, 46.6753];

            const map = L.map(`map-${orderId}`, { zoomControl: false }).setView(initialCenter, hasCustomerCoords ? 15 : 13);
            
            L.control.zoom({ position: 'bottomright' }).addTo(map);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
                attribution: '&copy; OpenStreetMap contributors', 
                maxZoom: 19 
            }).addTo(map);

            if (hasCustomerCoords) {
                L.marker([custLat, custLng])
                    .addTo(map)
                    .bindPopup('<b>موقع المشتري (العميل)</b>')
                    .openPopup();
            }
        });

        document.querySelectorAll('[data-accept-order]').forEach((button) => {
            button.addEventListener('click', async () => {
                const orderId = button.dataset.acceptOrder;
                button.disabled = true;
                button.textContent = 'جاري قبول الطلب...';

                try {
                    const response = await request(`/${restaurantSlug}/driver/orders/${orderId}/accept`, {
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

        document.querySelectorAll('[data-deliver-order]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!window.confirm('هل تريد تأكيد تسليم هذا الطلب؟')) return;
                
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