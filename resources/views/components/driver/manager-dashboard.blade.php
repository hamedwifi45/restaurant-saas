@props([
    'restaurant',
    'metrics',
    'drivers',
    'activeDrivers',
    'activeDeliveryOrders',
    'fleetStats',
    'mapRoutes',
    'mapDrivers',
    'orderStates',
])

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

<section dir="rtl" class="mx-auto min-h-screen max-w-[1600px] space-y-6 bg-slate-50 px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V6.75A2.25 2.25 0 0 1 5.25 4.5h8.379a2.25 2.25 0 0 1 1.591.659l3.621 3.621a2.25 2.25 0 0 1 .659 1.591v3.129M3 13.5h18M3 13.5v3.75A2.25 2.25 0 0 0 5.25 19.5h.879m12.992 0h.629A2.25 2.25 0 0 0 22 17.25V13.5m-3.5 6a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-11.5 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-blue-700">{{ $restaurant->name }} <span class="text-slate-300">/</span> مركز إدارة الأسطول</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">مركز قيادة التوصيل</h1>
                <p class="mt-1 text-sm text-slate-500">متابعة مباشرة للمندوبين والطلبات على الخريطة.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span id="fleet-connection" class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700" role="status" aria-live="polite">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                جارٍ الاتصال
            </span>
            <a href="{{ route('driver.dashboard', ['slug' => $restaurant->slug]) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:text-blue-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8.1 8.1 0 0 0-14.9-4L3 10m0-6v6h6m-5 3a8.1 8.1 0 0 0 14.9 4L21 14m0 6v-6h-6" />
                </svg>
                تحديث البيانات
            </a>
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="ملخص الأسطول">
        <article class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-slate-600">متاحين الآن</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700" aria-hidden="true">◎</span>
            </div>
            <strong class="mt-4 block text-3xl font-black tracking-tight text-slate-950" data-fleet-stat="active-drivers">{{ number_format($fleetStats['active_drivers']) }}</strong>
            <p class="mt-1 text-xs text-slate-500">مندوبون على رأس العمل · {{ number_format($metrics['drivers']) }} إجماليًا</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-slate-600">طلبات قيد التوصيل</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700" aria-hidden="true">↗</span>
            </div>
            <strong class="mt-4 block text-3xl font-black tracking-tight text-slate-950" data-fleet-stat="live-orders">{{ number_format($fleetStats['live_orders']) }}</strong>
            <p class="mt-1 text-xs text-slate-500">طلبات جارية الآن</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-slate-600">متوسط زمن التوصيل</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700" aria-hidden="true">◷</span>
            </div>
            <strong class="mt-4 block text-3xl font-black tracking-tight text-slate-950" data-fleet-stat="average-delivery">
                {{ $fleetStats['average_delivery_minutes'] === null ? '—' : number_format($fleetStats['average_delivery_minutes']).' د' }}
            </strong>
            <p class="mt-1 text-xs text-slate-500">آخر {{ number_format(min($metrics['completed'], 500)) }} طلب مكتمل</p>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-slate-600">طلبات بانتظار مندوب</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700" aria-hidden="true">⌛</span>
            </div>
            <strong class="mt-4 block text-3xl font-black tracking-tight text-slate-950" data-fleet-stat="unassigned-orders">{{ number_format($fleetStats['unassigned_orders']) }}</strong>
            <p class="mt-1 text-xs text-slate-500">جاهزة للإسناد إلى مندوب</p>
        </article>
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
        <div class="min-w-0 space-y-6">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">خريطة الأسطول المباشرة</h2>
                        <p class="mt-1 text-sm text-slate-500">مواقع المندوبين ومسارات الطلبات النشطة.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                        تحديث مباشر
                    </span>
                </div>
                <div id="fleet-map"
                    class="h-[440px] min-h-[360px] w-full bg-slate-100 sm:h-[560px]"
                    data-restaurant-id="{{ $restaurant->id }}"
                    data-routing-url="{{ config('services.osrm.url') }}"
                    data-tracking-url-template="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => '__TRACKING_CODE__']) }}"
                    aria-label="خريطة مواقع المندوبين وطلبات التوصيل">
                </div>
                @unless(config('services.osrm.url'))
                    <p class="border-t border-amber-100 bg-amber-50 px-5 py-3 text-xs leading-5 text-amber-800">
                        يظهر خط مباشر بين المندوب والعميل. لتخطيط المسارات عبر OSRM، اضبط OSRM_ROUTING_URL على عنوان خدمة توجيه تديرها مؤسستك.
                    </p>
                @endunless
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">الطلبات المرسلة للتوصيل</h2>
                        <p class="mt-1 text-sm text-slate-500">المندوب والعميل وحالة الرحلة لكل طلب.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700" data-metric="dispatched-delivery-orders">{{ number_format($metrics['dispatched']) }} في الطريق</span>
                </div>
                <div id="fleet-live-orders" class="divide-y divide-slate-100">
                    @forelse($activeDeliveryOrders as $order)
                        <article class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-6" data-order-row="{{ $order->id }}">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $order->tracking_code]) }}"
                                        class="font-mono text-sm font-black text-blue-700 hover:text-blue-900">#{{ $order->tracking_code }}</a>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">في الطريق</span>
                                </div>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800">{{ $order->customer_name ?: 'عميل' }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $order->driverUser?->name ?? 'مندوب غير محدد' }} · {{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p>
                            </div>
                            <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $order->tracking_code]) }}"
                                class="shrink-0 rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:border-blue-300 hover:text-blue-700">
                                فتح التتبع
                            </a>
                        </article>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">لا توجد طلبات قيد التوصيل حاليًا.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">حالة المندوبين</h2>
                        <p class="mt-1 text-sm text-slate-500">المركبة والرحلة الحالية لكل مندوب.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ number_format($drivers->count()) }}</span>
                </div>
            </div>
            <div class="max-h-[760px] divide-y divide-slate-100 overflow-y-auto">
                @forelse($drivers as $driver)
                    @php
                        $driverStatus = [
                            'available' => ['متاح', 'bg-emerald-50 text-emerald-700'],
                            'on_delivery' => ['في التوصيل', 'bg-blue-50 text-blue-700'],
                            'offline' => ['غير متصل', 'bg-slate-100 text-slate-500'],
                        ][$driver['status']];
                    @endphp
                    <article class="px-5 py-4" data-driver-card="{{ $driver['id'] }}">
                        <div class="flex items-start gap-3">
                            @if($driver['avatar'])
                                <img src="{{ media_url($driver['avatar']) }}" alt="" class="h-11 w-11 shrink-0 rounded-2xl object-cover ring-1 ring-slate-200">
                            @else
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700">
                                    {{ \Illuminate\Support\Str::initials($driver['name']) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="truncate text-sm font-black text-slate-900">{{ $driver['name'] }}</h3>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $driverStatus[1] }}">{{ $driverStatus[0] }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-500">
                                    {{ $driver['vehicle_model'] ?: match ($driver['vehicle_type']) {
                                        'motorcycle' => 'دراجة نارية',
                                        'car' => 'سيارة',
                                        'bicycle' => 'دراجة',
                                        default => $driver['vehicle_type'] ?: 'مركبة غير محددة',
                                    } }}
                                    @if($driver['plate_number'])
                                        <span class="px-1 text-slate-300">·</span><span dir="ltr">{{ $driver['plate_number'] }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($driver['current_order'])
                            <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $driver['current_order']->tracking_code]) }}"
                                class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs transition hover:bg-blue-50">
                                <span class="text-slate-500">الطلب الحالي</span>
                                <span class="truncate font-mono font-black text-blue-700">#{{ $driver['current_order']->tracking_code }}</span>
                            </a>
                        @else
                            <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">لا يوجد طلب مسند حاليًا</p>
                        @endif
                        <button type="button"
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                            data-focus-driver="{{ $driver['id'] }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" /><circle cx="12" cy="10" r="2.5" />
                            </svg>
                            تتبع على الخريطة
                        </button>
                    </article>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-slate-500">لا يوجد مندوبون مسجلون لهذا المطعم.</p>
                @endforelse
            </div>
        </aside>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer crossorigin=""></script>
<script>
    window.addEventListener('load', () => {
        const mapElement = document.getElementById('fleet-map');
        const connectionElement = document.getElementById('fleet-connection');

        if (!mapElement || !window.L) {
            console.error('Fleet dashboard map could not be initialized because Leaflet is unavailable.');
            return;
        }

        const colors = ['#2563eb', '#d97706', '#7c3aed', '#059669', '#db2777', '#0891b2'];
        const driverData = @js($mapDrivers);
        const routeData = @js($mapRoutes);
        const initialOrderStates = @js($orderStates);
        const routesByOrder = new Map();
        const driversById = new Map();
        const orderStatuses = new Map();
        const orderSnapshots = new Map();
        const routeLayers = new Map();
        const driverMarkers = new Map();
        let colorIndex = 0;

        const toCoordinate = (latitude, longitude) => {
            if (latitude === null || latitude === undefined || latitude === '' || longitude === null || longitude === undefined || longitude === '') {
                return null;
            }

            const lat = Number(latitude);
            const lng = Number(longitude);

            return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
                ? [lat, lng]
                : null;
        };

        const map = L.map(mapElement, { zoomControl: false, scrollWheelZoom: true });
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        const defaultCenter = @js([$restaurant->latitude ?? 33.5138, $restaurant->longitude ?? 36.2765]);
        const initialCenter = toCoordinate(defaultCenter[0], defaultCenter[1]) || [33.5138, 36.2765];
        map.setView(initialCenter, 12);

        function driverIcon(color) {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = `width:42px;height:42px;border:3px solid #fff;border-radius:15px;background:${color};box-shadow:0 4px 14px #0f172a33;display:grid;place-items:center;transform:rotate(-8deg)`;
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('width', '23');
            svg.setAttribute('height', '23');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', '#fff');
            svg.setAttribute('stroke-width', '1.8');
            svg.setAttribute('stroke-linecap', 'round');
            svg.setAttribute('stroke-linejoin', 'round');
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', 'm12 3 8 18-8-4-8 4 8-18Z');
            svg.appendChild(path);
            wrapper.appendChild(svg);

            return L.divIcon({ className: '', html: wrapper, iconSize: [42, 42], iconAnchor: [21, 21] });
        }

        function customerIcon(orderCode) {
            const pin = document.createElement('div');
            pin.style.cssText = 'position:relative;width:34px;height:42px;display:flex;align-items:flex-start;justify-content:center';
            const dot = document.createElement('span');
            dot.style.cssText = 'width:28px;height:28px;border:3px solid #fff;border-radius:50% 50% 50% 0;background:#dc2626;box-shadow:0 3px 10px #0f172a44;transform:rotate(-45deg)';
            const badge = document.createElement('span');
            badge.textContent = orderCode;
            badge.style.cssText = 'position:absolute;top:-8px;left:20px;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;border:1px solid #fecaca;border-radius:999px;background:#fff;padding:3px 7px;color:#b91c1c;font:700 10px ui-monospace,monospace;box-shadow:0 2px 6px #0f172a1a';
            pin.append(dot, badge);

            return L.divIcon({ className: '', html: pin, iconSize: [34, 42], iconAnchor: [17, 38] });
        }

        function ensureDriverMarker(driverId, name, coordinates, color) {
            if (!coordinates) {
                return null;
            }

            let marker = driverMarkers.get(String(driverId));
            if (!marker) {
                marker = L.marker(coordinates, { icon: driverIcon(color), riseOnHover: true }).addTo(map);
                driverMarkers.set(String(driverId), marker);
            } else {
                marker.setLatLng(coordinates);
                marker.setIcon(driverIcon(color));
            }

            const popup = document.createElement('div');
            popup.textContent = name || 'مندوب';
            marker.bindPopup(popup);

            return marker;
        }

        function updateRouteLine(route) {
            const key = String(route.id);
            const driverPoint = toCoordinate(route.driver_latitude, route.driver_longitude);
            const customerPoint = toCoordinate(route.customer_latitude, route.customer_longitude);
            let layer = routeLayers.get(key);

            if (!layer) {
                layer = { line: null, customer: null, revision: 0 };
                routeLayers.set(key, layer);
            }

            layer.revision += 1;
            const revision = layer.revision;
            const color = route.color || colors[0];

            if (customerPoint) {
                if (!layer.customer) {
                    layer.customer = L.marker(customerPoint, { icon: customerIcon(route.tracking_code || 'طلب') }).addTo(map);
                } else {
                    layer.customer.setLatLng(customerPoint);
                }
                const popup = document.createElement('div');
                popup.textContent = `${route.tracking_code || 'طلب'} · ${route.customer_name || 'العميل'}`;
                layer.customer.bindPopup(popup);
            }

            if (!driverPoint || !customerPoint) {
                return;
            }

            ensureDriverMarker(route.driver_id, route.driver_name, driverPoint, color);
            if (layer.line) {
                map.removeLayer(layer.line);
                layer.line = null;
            }

            const drawLine = (coordinates) => {
                if (routeLayers.get(key)?.revision !== revision) {
                    return;
                }
                layer.line = L.polyline(coordinates, {
                    color,
                    weight: 5,
                    opacity: 0.82,
                    lineCap: 'round',
                    lineJoin: 'round',
                }).addTo(map);
            };

            const routingUrl = mapElement.dataset.routingUrl?.replace(/\/+$/, '');
            if (!routingUrl) {
                drawLine([driverPoint, customerPoint]);
                return;
            }

            const waypoints = `${driverPoint[1]},${driverPoint[0]};${customerPoint[1]},${customerPoint[0]}`;
            fetch(`${routingUrl}/route/v1/driving/${waypoints}?overview=full&geometries=geojson`)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`OSRM responded with HTTP ${response.status}.`);
                    }

                    return response.json();
                })
                .then((data) => {
                    const geometry = data.routes?.[0]?.geometry?.coordinates;
                    if (!Array.isArray(geometry) || geometry.length < 2) {
                        throw new Error('OSRM returned no route geometry.');
                    }
                    drawLine(geometry.map(([longitude, latitude]) => [latitude, longitude]));
                })
                .catch((error) => {
                    console.error('Unable to retrieve the fleet route from the configured OSRM service.', error);
                    drawLine([driverPoint, customerPoint]);
                });
        }

        function normalizeRoute(route) {
            return {
                id: route.id ?? route.order_id,
                tracking_code: route.tracking_code ?? route.id ?? 'طلب',
                status: route.status,
                driver_id: route.driver_id,
                driver_name: route.driver_name,
                driver_latitude: route.driver_latitude ?? route.latitude,
                driver_longitude: route.driver_longitude ?? route.longitude,
                customer_name: route.customer_name,
                customer_latitude: route.customer_latitude ?? route.customer_lat,
                customer_longitude: route.customer_longitude ?? route.customer_lng,
                color: route.color || colors[colorIndex++ % colors.length],
            };
        }

        driverData.forEach((driver, index) => {
            const id = String(driver.id);
            const coordinates = toCoordinate(driver.latitude, driver.longitude);
            driversById.set(id, driver);
            ensureDriverMarker(id, driver.name, coordinates, colors[index % colors.length]);
        });

        routeData.forEach((item) => {
            const route = normalizeRoute(item);
            const key = String(route.id);
            const driver = driversById.get(String(route.driver_id));
            if (driver) {
                route.driver_latitude ??= driver.latitude;
                route.driver_longitude ??= driver.longitude;
                route.driver_name ??= driver.name;
            }
            routesByOrder.set(key, route);
            orderStatuses.set(key, route.status);
            orderSnapshots.set(key, route);
            updateRouteLine(route);
        });
        initialOrderStates.forEach((order) => {
            const key = String(order.id);
            orderStatuses.set(key, order.status);
            orderSnapshots.set(key, order);
        });

        const visiblePoints = [
            ...Array.from(driverMarkers.values(), (marker) => marker.getLatLng()),
            ...Array.from(routeLayers.values()).flatMap((layer) => layer.customer ? [layer.customer.getLatLng()] : []),
        ];
        if (visiblePoints.length > 1) {
            map.fitBounds(L.latLngBounds(visiblePoints), { padding: [36, 36], maxZoom: 15 });
        } else if (visiblePoints.length === 1) {
            map.setView(visiblePoints[0], 14);
        }

        function focusDriver(driverId) {
            const id = String(driverId);
            const marker = driverMarkers.get(id);
            const points = marker ? [marker.getLatLng()] : [];

            routeLayers.forEach((layer, orderId) => {
                if (String(routesByOrder.get(orderId)?.driver_id) === id && layer.customer) {
                    points.push(layer.customer.getLatLng());
                }
            });

            if (points.length > 1) {
                map.fitBounds(L.latLngBounds(points), { padding: [64, 64], maxZoom: 16 });
            } else if (points.length === 1) {
                map.setView(points[0], 16);
                marker?.openPopup();
            }
        }

        document.querySelectorAll('[data-focus-driver]').forEach((button) => {
            button.addEventListener('click', () => focusDriver(button.dataset.focusDriver));
        });

        function setStat(name, value) {
            const element = document.querySelector(`[data-fleet-stat="${name}"]`);
            if (element) {
                element.textContent = new Intl.NumberFormat('ar').format(Math.max(0, value));
            }
            if (name === 'live-orders') {
                const dispatchCount = document.querySelector('[data-metric="dispatched-delivery-orders"]');
                if (dispatchCount) {
                    dispatchCount.textContent = `${new Intl.NumberFormat('ar').format(Math.max(0, value))} في الطريق`;
                }
            }
        }

        const isLive = (status) => ['on_way', 'out_for_delivery', 'in_transit'].includes(status);
        const isUnassigned = (order) => order?.status === 'ready' && !order?.driver_id;
        let liveOrderCount = @js($fleetStats['live_orders']);
        let unassignedOrderCount = @js($fleetStats['unassigned_orders']);

        function renderOrderRow(order) {
            const container = document.getElementById('fleet-live-orders');
            const key = String(order.id);
            const orderCode = String(order.tracking_code || order.id);
            const trackingUrl = mapElement.dataset.trackingUrlTemplate.replace('__TRACKING_CODE__', encodeURIComponent(orderCode));
            let row = container.querySelector(`[data-order-row="${key}"]`);

            if (!row) {
                row = document.createElement('article');
                row.dataset.orderRow = key;
                row.className = 'flex flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-6';
                const emptyMessage = container.querySelector('p');
                emptyMessage?.remove();

                const details = document.createElement('div');
                details.className = 'min-w-0';
                const title = document.createElement('div');
                title.className = 'flex flex-wrap items-center gap-2';
                const titleLink = document.createElement('a');
                titleLink.dataset.orderTrackingLink = '';
                titleLink.className = 'font-mono text-sm font-black text-blue-700 hover:text-blue-900';
                const badge = document.createElement('span');
                badge.className = 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700';
                badge.textContent = 'في الطريق';
                title.append(titleLink, badge);

                const customer = document.createElement('p');
                customer.className = 'mt-1 truncate text-sm font-bold text-slate-800';
                customer.dataset.orderCustomer = '';
                const deliveryDetails = document.createElement('p');
                deliveryDetails.className = 'mt-1 truncate text-xs text-slate-500';
                deliveryDetails.dataset.orderDeliveryDetails = '';
                details.append(title, customer, deliveryDetails);

                const trackingLink = document.createElement('a');
                trackingLink.dataset.orderTrackingLink = '';
                trackingLink.className = 'shrink-0 rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:border-blue-300 hover:text-blue-700';
                trackingLink.textContent = 'فتح التتبع';
                row.append(details, trackingLink);
                container.prepend(row);
            }

            row.querySelectorAll('[data-order-tracking-link]').forEach((link) => {
                link.href = trackingUrl;
            });
            row.querySelector('[data-order-tracking-link]')?.replaceChildren(document.createTextNode(`#${orderCode}`));
            row.querySelector('[data-order-customer]').textContent = order.customer_name || 'عميل';
            row.querySelector('[data-order-delivery-details]').textContent = `${order.driver_name || 'مندوب غير محدد'} · ${order.delivery_address || 'لا يوجد عنوان مسجل'}`;
        }

        function handleOrderStatus(order) {
            if (!order || order.id === undefined || !order.status) {
                return;
            }

            const key = String(order.id);
            const previous = routesByOrder.get(key) || orderSnapshots.get(key) || { id: order.id, status: orderStatuses.get(key) };
            const previousStatus = orderStatuses.get(key);

            if (previousStatus !== undefined && isLive(previousStatus) !== isLive(order.status)) {
                liveOrderCount += isLive(order.status) ? 1 : -1;
                setStat('live-orders', liveOrderCount);
            }

            if (previousStatus !== undefined && isUnassigned(previous) !== isUnassigned(order)) {
                unassignedOrderCount += isUnassigned(order) ? 1 : -1;
                setStat('unassigned-orders', unassignedOrderCount);
            }

            orderStatuses.set(key, order.status);
            orderSnapshots.set(key, { ...previous, ...order });
            if (['delivered', 'cancelled', 'rejected'].includes(order.status)) {
                const layer = routeLayers.get(key);
                if (layer) {
                    [layer.line, layer.customer].forEach((item) => item && map.removeLayer(item));
                    routeLayers.delete(key);
                }
                routesByOrder.delete(key);
                document.querySelector(`[data-order-row="${key}"]`)?.remove();
                return;
            }

            const updated = normalizeRoute({ ...previous, ...order });
            routesByOrder.set(key, updated);
            orderSnapshots.set(key, updated);
            if (updated.driver_id && isLive(updated.status)) {
                updateRouteLine(updated);
            }
            if (isLive(updated.status)) {
                renderOrderRow(updated);
            }
        }

        const restaurantId = mapElement.dataset.restaurantId;
        if (window.Echo) {
            const channel = window.Echo.private(`restaurant.${restaurantId}.admin`);
            channel.listen('.DriverLocationUpdated', (event) => {
                const route = routesByOrder.get(String(event.order_id));
                if (!route) {
                    return;
                }
                route.driver_id = event.driver_id;
                route.driver_latitude = event.latitude;
                route.driver_longitude = event.longitude;
                ensureDriverMarker(route.driver_id, route.driver_name, toCoordinate(route.driver_latitude, route.driver_longitude), route.color);
                updateRouteLine(route);
            });
            channel.listen('.OrderCreated', (event) => {
                const order = event.order;
                if (!order || order.db_id === undefined) {
                    return;
                }
                const normalizedStatus = order.status === 'new' ? 'pending' : order.status;
                orderStatuses.set(String(order.db_id), normalizedStatus);
                routesByOrder.set(String(order.db_id), {
                    id: order.db_id,
                    tracking_code: order.id,
                    status: normalizedStatus,
                    driver_id: null,
                });
                if (normalizedStatus === 'ready') {
                    unassignedOrderCount += 1;
                    setStat('unassigned-orders', unassignedOrderCount);
                }
                orderSnapshots.set(String(order.db_id), {
                    id: order.db_id,
                    status: normalizedStatus,
                    driver_id: null,
                });
            });
            channel.listen('.OrderStatusUpdated', (event) => handleOrderStatus(event.order));
            window.Echo.connector.pusher.connection.bind('connected', () => {
                connectionElement.className = 'inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700';
                connectionElement.lastElementChild.className = 'h-2 w-2 rounded-full bg-emerald-500';
                connectionElement.lastChild.textContent = ' متصل';
            });
            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                connectionElement.className = 'inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700';
                connectionElement.lastElementChild.className = 'h-2 w-2 rounded-full bg-amber-400';
                connectionElement.lastChild.textContent = ' غير متصل';
            });
        } else {
            console.error('Fleet dashboard could not subscribe because Laravel Echo is unavailable.');
            connectionElement.lastChild.textContent = ' التحديث المباشر غير متاح';
        }
    });
</script>
