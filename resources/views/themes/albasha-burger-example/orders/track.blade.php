@extends('themes.albasha-burger.layout')

@pushOnce('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endPushOnce

@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
    <section class="py-16 bg-gray-50 min-h-screen">
        <div class="container mx-auto px-4 max-w-4xl">

            <!-- رأس الصفحة -->
            <div class="text-center mb-12">
                <span class="bg-primary text-white px-4 py-1 rounded-full text-sm font-bold mb-4 inline-block">حالة
                    الطلب</span>
                <h1 class="text-4xl font-black text-gray-800 mb-2">تتبع طلبك المباشر</h1>
                <p class="text-gray-600">رقم التتبع: <span
                        class="font-mono font-bold text-primary text-lg">{{ $order->tracking_code }}</span></p>
            </div>

            <div
                data-order-cancellation
                class="{{ $order->status === 'cancelled' ? '' : 'hidden' }} mb-8 rounded-2xl border border-red-200 bg-red-50 p-5 text-right font-bold text-red-800"
                role="alert"
                aria-live="assertive"
            >تعذر قبول طلبك. السبب: {{ $order->rejection_reason }}</div>

            @include('components.order-push-subscription', ['order' => $order, 'restaurant' => $restaurant])
            @include('components.driver-rating', ['order' => $order, 'restaurant' => $restaurant])

            @if((! $order->deliveryDispatch || $order->deliveryDispatch->status === 'cancelled') && in_array($order->status, ['on_way', 'out_for_delivery', 'in_transit'], true))
                <a href="{{ route('driver.order.tracking', ['slug' => $restaurant->slug, 'order' => $order->tracking_code, 'token' => $order->device_token]) }}"
                    class="mb-8 block rounded-2xl bg-primary px-6 py-5 text-center text-lg font-black text-white shadow-lg transition hover:brightness-110">
                    تتبع المندوب مباشر 🚴‍♂️
                </a>
            @elseif((! $order->deliveryDispatch || $order->deliveryDispatch->status === 'cancelled') && $order->delivery_latitude && $order->delivery_longitude)
                <div class="mb-8 rounded-3xl bg-white p-5 shadow-lg">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-black text-gray-800">تتبع المندوب على الخريطة</h3>
                            <p id="tracking-status" class="mt-1 text-sm text-gray-500">جاري تحميل موقع المندوب...</p>
                        </div>
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">تحديث مباشر</span>
                    </div>
                    <div id="tracking-map" class="h-80 min-h-[250px] w-full rounded-2xl" style="min-height: 250px; width: 100%;"></div>
                </div>
            @endif

            <!-- بطاقة الحالة الرئيسية -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8 border border-gray-100">

                <!-- شريط التقدم المرئي -->
                <div class="p-8 bg-gray-50 border-b border-gray-100">
                    <div class="relative flex justify-between items-center">
                        <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 transform -translate-y-1/2"></div>

                        <!-- الخطوة 1 -->
                        <div class="flex flex-col items-center gap-3 relative z-10">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-500 bg-primary text-white shadow-lg scale-110">
                                ✓
                            </div>
                            <span class="text-sm font-bold text-gray-800">تم الاستلام</span>
                        </div>

                        <!-- الخطوة 2 -->
                        <div class="flex flex-col items-center gap-3 relative z-10">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-500 {{ in_array($order->status, ['preparing', 'on_way', 'out_for_delivery', 'in_transit', 'delivered']) ? 'bg-primary text-white shadow-lg scale-110' : 'bg-white text-gray-300 border-2 border-gray-200' }}">
                                ٢
                            </div>
                            <span class="text-sm font-bold {{ in_array($order->status, ['preparing', 'on_way', 'out_for_delivery', 'in_transit', 'delivered']) ? 'text-gray-800' : 'text-gray-400' }}">جاري التحضير</span>
                        </div>

                        <!-- الخطوة 3 -->
                        <div class="flex flex-col items-center gap-3 relative z-10">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-500 {{ in_array($order->status, ['on_way', 'out_for_delivery', 'in_transit', 'delivered']) ? 'bg-primary text-white shadow-lg scale-110' : 'bg-white text-gray-300 border-2 border-gray-200' }}">
                                ٣
                            </div>
                            <span class="text-sm font-bold {{ in_array($order->status, ['on_way', 'out_for_delivery', 'in_transit', 'delivered']) ? 'text-gray-800' : 'text-gray-400' }}">في الطريق إليك</span>
                        </div>

                        <!-- الخطوة 4 -->
                        <div class="flex flex-col items-center gap-3 relative z-10">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-500 {{ $order->status === 'delivered' ? 'bg-primary text-white shadow-lg scale-110' : 'bg-white text-gray-300 border-2 border-gray-200' }}">
                                ٤
                            </div>
                            <span class="text-sm font-bold {{ $order->status === 'delivered' ? 'text-gray-800' : 'text-gray-400' }}">تم التوصيل</span>
                        </div>
                    </div>
                </div>

                <!-- تفاصيل الطلب والتوصيل -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                    <!-- معلومات العميل -->
                    <div class="p-8 border-b md:border-b-0 md:border-l border-gray-100">
                        <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-2">
                            معلومات التوصيل
                        </h3>
                        @include('components.external-delivery-dispatch-status', ['order' => $order, 'restaurant' => $restaurant])
                        <ul class="space-y-4 text-gray-600">
                            <li class="flex items-start gap-3">
                                <div>
                                    <span class="block text-xs text-gray-400">الاسم</span>
                                    <span class="font-bold text-gray-800">{{ $order->customer_name }}</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <div>
                                    <span class="block text-xs text-gray-400">رقم الهاتف</span>
                                    <span class="font-bold text-gray-800">{{ $order->customer_phone }}</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <div>
                                    <span class="block text-xs text-gray-400">العنوان</span>
                                    <span class="font-bold text-gray-800">{{ $order->delivery_address }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- ملخص الدفع -->
                    <div class="p-8 bg-gray-50">
                        <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-2">
                            ملخص الفاتورة
                        </h3>

                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>المجموع الفرعي:</span>
                                <span>{{ number_format($order->total_amount, 2) }} ل.س</span>
                            </div>

                            <div class="flex justify-between text-gray-600">
                                <span>الضريبة ({{ $restaurant->tax_percentage ?? 0 }}٪):</span>
                                <span>{{ number_format($order->tax_amount ?? 0, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                            </div>

                            @if(($order->delivery_fee ?? 0) > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>رسوم التوصيل:</span>
                                    <span>{{ number_format($order->delivery_fee, 2) }} ل.س</span>
                                </div>
                            @endif

                            @if(($order->discount ?? 0) > 0)
                                <div class="flex justify-between text-green-600 font-bold">
                                    <span>الخصم:</span>
                                    <span>- {{ number_format($order->discount, 2) }} ل.س</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-gray-600">
                                <span>طريقة الدفع:</span>
                                <span class="font-bold">{{ $order->payment_method === 'cash' ? 'نقدي عند الاستلام' : 'تحويل بنكي' }}</span>
                            </div>

                            <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between items-center">
                                <span class="font-black text-gray-800">الإجمالي النهائي:</span>
                                <span class="text-2xl font-black text-primary">{{ number_format($order->final_amount, 2) }} ل.س</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة المنتجات المطلوبة -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
                <h3 class="font-black text-2xl mb-8 text-gray-800 border-b pb-4">محتويات الطلب</h3>
                <div class="space-y-6">
                    @foreach($order->items as $item)
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b last:border-0 last:pb-0">
                            <div class="flex items-center gap-4">
                                @if($item->product && $item->product->image)
                                    <img src="{{ media_url($item->product->image) }}" alt="{{ $item->product->name }}" loading="lazy" decoding="async"
                                        class="w-16 h-16 rounded-2xl object-cover border border-orange-100">
                                @else
                                    <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center text-3xl border border-orange-100">
                                        {{ mb_substr($restaurant->name, 0, 1) }}
                                    </div>
                                @endif

                                <div>
                                    @if($item->product)
                                        <h4 class="font-bold text-lg text-gray-800">{{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif</h4>
                                        <p class="text-sm text-gray-500">
                                            الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ل.س
                                        </p>

                                        <a href="{{ route('product.show', [$restaurant->slug, $item->product->id]) }}"
                                            class="inline-block mt-2 text-sm text-primary hover:underline font-bold">
                                            عرض المنتج ←
                                        </a>
                                    @else
                                        <h4 class="font-bold text-lg text-gray-800">{{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif</h4>
                                        <p class="text-sm text-gray-500">
                                            الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ل.س
                                        </p>
                                        <p class="text-xs text-red-500 mt-1">هذا المنتج لم يعد متاحاً</p>
                                    @endif
                                </div>
                            </div>

                            <span class="font-black text-xl text-gray-800">{{ number_format($item->total, 2) }} ل.س</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- QR Code للتتبع السريع -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 text-center">
                <h3 class="font-bold text-xl mb-4 text-gray-800 flex items-center justify-center gap-2">
                    <span>شارك رمز التتبع</span>
                </h3>
                <p class="text-gray-500 text-sm mb-6">امسح هذا الرمز بكاميرا الهاتف للوصول السريع لصفحة التتبع</p>

                <div id="qrcode-track" class="flex justify-center mb-4"></div>

                <div class="flex gap-3 justify-center flex-wrap">
                    <button onclick="downloadTrackQR()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <span>تحميل الصورة</span>
                    </button>

                    <button onclick="shareTrackQR()"
                        class="btn-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                        <span>مشاركة الرابط</span>
                    </button>
                </div>
            </div>

            @if($order->status === 'delivered' && !$order->is_reviewed && $order->delivered_at && $order->delivered_at->diffInMinutes(now()) >= 10)
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-3xl shadow-lg p-8 mb-8 text-center border-2 border-yellow-200">
                    <h3 class="text-2xl font-black text-gray-800 mb-2">كيف كانت تجربتك؟</h3>
                    <p class="text-gray-600 mb-6">شاركنا رأيك لنساعدنا على التحسين</p>
                    <a href="{{ route('review.create', [$restaurant->slug, $order->tracking_code]) }}"
                        class="btn-primary px-8 py-3 rounded-xl font-bold inline-block hover:shadow-xl transition">
                        قيّم تجربتك الآن
                    </a>
                </div>
            @endif

            <!-- خيارات المساعدة -->
            <div class="text-center">
                <p class="text-gray-500 mb-4">هل لديك استفسار حول هذا الطلب؟</p>
                <div class="flex justify-center gap-4 flex-wrap">
                    <a href="tel:{{ $restaurant->phone }}"
                        class="bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 transition flex items-center gap-2">
                        اتصل بالمطعم
                    </a>
                    <a href="{{ route('restaurant.home', $restaurant->slug) }}"
                        class="btn-primary px-6 py-3 rounded-xl font-bold hover:shadow-lg transition">
                        العودة للرئيسية
                    </a>
                </div>
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
            <article class="rounded-2xl border border-gray-200 bg-white p-4 text-right shadow-xl" role="status">
                <h2 class="font-black text-gray-900" x-text="notification.title"></h2>
                <p class="mt-1 text-sm leading-6 text-gray-600" x-text="notification.message"></p>
            </article>
        </template>
    </div>

    @if($order->delivery_latitude && $order->delivery_longitude && ! in_array($order->status, ['on_way', 'out_for_delivery', 'in_transit'], true))
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <script src="{{ asset('js/offline-map-tiles.js') }}"></script>
        <script>
            const customerPoint = [{{ $order->delivery_latitude }}, {{ $order->delivery_longitude }}];
            const trackingMap = L.map('tracking-map').fitBounds([customerPoint], { padding: [40, 40] });
            requestAnimationFrame(() => trackingMap.invalidateSize());
            window.setTimeout(() => trackingMap.invalidateSize(), 150);
            window.addOfflineAwareMapTiles(trackingMap);
            L.marker(customerPoint).addTo(trackingMap).bindPopup('موقع التوصيل').openPopup();
            let driverMarker;

            async function refreshDriverLocation() {
                const response = await fetch(@js(route('order.track.location', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token])));
                if (!response.ok) return;
                const data = await response.json();
                if (data.driver.latitude && data.driver.longitude) {
                    const point = [Number(data.driver.latitude), Number(data.driver.longitude)];
                    driverMarker = driverMarker || L.marker(point).addTo(trackingMap).bindPopup('المندوب');
                    driverMarker.setLatLng(point);
                    trackingMap.fitBounds([customerPoint, point], { padding: [40, 40] });
                    document.getElementById('tracking-status').textContent = data.driver.updated_at ? `آخر تحديث: ${new Date(data.driver.updated_at).toLocaleTimeString('ar', { timeZone: @js(config('app.timezone')) })}` : 'تم تحديث موقع المندوب.';
                } else {
                    document.getElementById('tracking-status').textContent = 'سيظهر موقع المندوب بعد بدء التوصيل.';
                }
            }

            refreshDriverLocation();
            setInterval(refreshDriverLocation, 10000);
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trackUrl = "{{ route('order.track', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token]) }}";

            new QRCode(document.getElementById("qrcode-track"), {
                text: trackUrl,
                width: 180,
                height: 180,
                colorDark: "{{ $restaurant->primary_color ?? '#FF6B35' }}",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });

        function downloadTrackQR() {
            const canvas = document.querySelector('#qrcode-track canvas');
            if (!canvas) {
                alert('انتظر قليلاً');
                return;
            }
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `tracking-{{ $order->tracking_code }}.png`;
            link.href = url;
            link.click();
        }

        async function shareTrackQR() {
            const trackUrl = "{{ route('order.track', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token]) }}";
            const shareData = {
                title: 'تتبع طلبي من {{ $restaurant->name }}',
                text: `تتبع طلبك برمز: {{ $order->tracking_code }}`,
                url: trackUrl
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else {
                    navigator.clipboard.writeText(trackUrl);
                    alert('تم نسخ رابط التتبع!');
                }
            } catch (err) {
                console.log('Error:', err);
            }
        }
    </script>
@endsection