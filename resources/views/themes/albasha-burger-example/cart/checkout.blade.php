@extends('themes.albasha-burger.layout')

@push('head')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <script>
        // تعريف مكون Alpine في النطاق العام قبل بدء تشغيل Alpine/Vite
        window.checkoutPricing = function(config) {
            return {
                subtotal: Number(config.subtotal || 0),
                tax: Number(config.tax || 0),
                legacyFee: Number(config.legacyFee || 0),
                offerDiscount: Number(config.offerDiscount || 0),
                restaurantLatitude: Number(config.restaurantLatitude),
                restaurantLongitude: Number(config.restaurantLongitude),
                deliveryRadius: Number(config.deliveryRadius || 10),
                baseDeliveryFee: Number(config.baseDeliveryFee || 2.0),
                baseDeliveryKilometers: Number(config.baseDeliveryKilometers || 2.0),
                feePerAdditionalKilometer: Number(config.feePerAdditionalKilometer || 0.5),
                currency: config.currency || 'ل.س',
                isDineIn: Boolean(config.isDineIn),

                customerName: '',
                customerPhone: '',
                customerAddress: '',
                customerLat: '',
                customerLng: '',

                distance: null,
                extraKilometers: 0,
                deliveryFeeHelper: '',
                deliveryFee: Number(config.legacyFee || 0),
                total: 0,
                deliveryFeeDisplay: '',
                totalDisplay: '',
                isOutOfRange: false,
                warningMessage: '',
                couponDiscount: 0,
                processing: false,

                init() {
                    window.checkoutPricingState = this;

                    // الاستماع لأحداث الخريطة
                    window.addEventListener('customer-location-updated', (event) => {
                        const { latitude, longitude } = event.detail || {};
                        if (latitude && longitude) {
                            this.setCustomerLocation(latitude, longitude);
                        }
                    });

                    this.syncFromForm();
                    this.recalculate();
                },

                syncFromForm() {
                    const form = document.getElementById('checkout-form');
                    if (!form) return;
                    
                    const latInput = form.querySelector('[name="latitude"]');
                    const lngInput = form.querySelector('[name="longitude"]');
                    const nameInput = form.querySelector('[name="name"]');
                    const phoneInput = form.querySelector('[name="phone"]');
                    const addrInput = form.querySelector('[name="address"]');

                    if (latInput?.value) this.customerLat = latInput.value;
                    if (lngInput?.value) this.customerLng = lngInput.value;
                    if (nameInput?.value) this.customerName = nameInput.value;
                    if (phoneInput?.value) this.customerPhone = phoneInput.value;
                    if (addrInput?.value) this.customerAddress = addrInput.value;
                },

                persistCustomer() {
                    this.syncFromForm();
                    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                    document.cookie = `customer_lat=${encodeURIComponent(this.customerLat || '')}; Max-Age=2592000; Path=/; SameSite=Lax${secure}`;
                    document.cookie = `customer_lng=${encodeURIComponent(this.customerLng || '')}; Max-Age=2592000; Path=/; SameSite=Lax${secure}`;
                },

                setCustomerLocation(latitude, longitude) {
                    this.customerLat = String(latitude);
                    this.customerLng = String(longitude);

                    const latInput = document.getElementById('latitude');
                    const lngInput = document.getElementById('longitude');
                    if (latInput) latInput.value = this.customerLat;
                    if (lngInput) lngInput.value = this.customerLng;

                    this.persistCustomer();
                    this.recalculate();
                },

                recalculate() {
                    const lat = Number(this.customerLat);
                    const lng = Number(this.customerLng);
                    const restLat = this.restaurantLatitude;
                    const restLng = this.restaurantLongitude;

                    const hasCustomer = this.customerLat !== '' && this.customerLng !== '' && Number.isFinite(lat) && Number.isFinite(lng);
                    const hasRestaurant = Number.isFinite(restLat) && Number.isFinite(restLng);

                    this.isOutOfRange = false;
                    this.warningMessage = '';
                    this.distance = null;
                    this.extraKilometers = 0;

                    if (this.isDineIn) {
                        this.deliveryFee = 0;
                    } else if (hasCustomer && hasRestaurant) {
                        // حساب المسافة بدقة عبر معادلة Haversine
                        const dLat = (lat - restLat) * Math.PI / 180;
                        const dLng = (lng - restLng) * Math.PI / 180;
                        const a = Math.sin(dLat / 2) ** 2 + Math.cos(restLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
                        const c = 2 * Math.atan2(Math.sqrt(Math.min(1, Math.max(0, a))), Math.sqrt(1 - Math.min(1, Math.max(0, a))));

                        this.distance = 6371 * c;
                        this.extraKilometers = Math.max(0, this.distance - this.baseDeliveryKilometers);
                        this.deliveryFee = Math.round((this.baseDeliveryFee + (this.extraKilometers * this.feePerAdditionalKilometer)) * 100) / 100;

                        if (this.distance > this.deliveryRadius) {
                            this.isOutOfRange = true;
                            this.warningMessage = `نعتذر، موقعك يبعد ${this.distance.toFixed(2)} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم (الحد الأقصى ${this.deliveryRadius.toFixed(2)} كم)`;
                        }
                    } else {
                        this.deliveryFee = this.baseDeliveryFee;
                    }

                    this.deliveryFeeHelper = `(شاملة الرسم الأساسي + ${this.extraKilometers.toFixed(2)} كم إضافي)`;
                    this.deliveryFeeDisplay = `${this.deliveryFee.toFixed(2)} ${this.currency}`;
                    this.total = this.subtotal + this.tax + this.deliveryFee - this.offerDiscount - this.couponDiscount;
                    this.totalDisplay = `${this.total.toFixed(2)} ${this.currency}`;

                    const form = document.getElementById('checkout-form');
                    if (form) {
                        form.dataset.outOfRange = this.isOutOfRange ? 'true' : 'false';
                    }
                },

                formatDistance() {
                    return this.distance === null ? '' : `${this.distance.toFixed(2)} كم`;
                },

                validateSubmission(event) {
                    this.persistCustomer();
                    this.recalculate();

                    if (this.isOutOfRange) {
                        event.preventDefault();
                        this.$nextTick(() => {
                            this.$refs.geofenceAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                        return;
                    }

                    this.processing = true;
                },

                setCouponDiscount(discount) {
                    this.couponDiscount = Number(discount) || 0;
                    this.recalculate();
                }
            };
        };
    </script>
@endpush

@section('content')
<section class="py-16 bg-gray-50 min-h-screen" x-data="checkoutPricing({
    subtotal: @js($baseSubtotal),
    tax: @js($baseTax),
    legacyFee: @js($deliveryFee),
    offerDiscount: @js($autoDiscount),
    restaurantLatitude: @js($restaurantLatitude),
    restaurantLongitude: @js($restaurantLongitude),
    deliveryRadius: @js($restaurant->delivery_radius_km ?? 10),
    baseDeliveryFee: @js($restaurant->base_delivery_fee ?? 2.0),
    baseDeliveryKilometers: @js($restaurant->base_delivery_km ?? 2.0),
    feePerAdditionalKilometer: @js($restaurant->per_km_delivery_fee ?? 0.5),
    currency: @js($restaurant->currency_symbol ?? 'ل.س'),
    isDineIn: @js((bool) $table)
})" data-restaurant-latitude="{{ $restaurantLatitude }}" data-restaurant-longitude="{{ $restaurantLongitude }}">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-4xl font-black text-center mb-12 text-gray-800">إتمام الطلب 📝</h1>

        <form action="{{ route('checkout.store', $restaurant->slug) }}" method="POST" id="checkout-form" class="bg-white p-8 rounded-2xl shadow-lg" @submit="validateSubmission($event)">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- بيانات العميل -->
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2">{{ $table ? 'طلب داخل المطعم' : 'بيانات التوصيل' }}</h3>
                    @if($table)
                        <div class="rounded-xl bg-orange-50 p-4 text-sm text-orange-800">أنت تطلب من الطاولة <strong>{{ $table->number }}</strong>. سيصل الطلب إلى المطبخ مباشرة.</div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                        <input type="text" name="name" id="customer-name" value="{{ old('name') }}" x-model="customerName" @input="persistCustomer()" required autocomplete="name" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" id="customer-phone" value="{{ old('phone') }}" x-model="customerPhone" @input="persistCustomer()" required maxlength="20" pattern="\s*(09[0-9]{8}|\+?[1-9][0-9]{7,14})\s*" title="أدخل رقماً محلياً مثل 09xxxxxxxx أو دولياً مثل +963930123456" inputmode="tel" autocomplete="tel" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    @if(!$table)<div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea name="address" id="address" rows="3" x-model="customerAddress" @input="persistCustomer()" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="وصف اختياري: الطابق، رقم الشقة، علامة مميزة...">{{ old('address') }}</textarea>
                        <div id="checkout-map" class="mt-3 h-64 rounded-xl border border-gray-200"></div>
                        <p id="map-status" class="mt-2 text-sm text-gray-500">اضغط على الخريطة لتحديد موقع المنزل.</p>
                        <button type="button" id="use-current-location" class="mt-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">تحديد موقعي الحالي</button>
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" x-model="customerLat" @input="persistCustomer(); recalculate()" required>
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" x-model="customerLng" @input="persistCustomer(); recalculate()" required>
                    </div>@endif
                </div>

                <!-- ملخص الدفع -->
                <div class="bg-gray-50 p-6 rounded-xl h-fit">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">ملخص المبلغ</h3>

                    <div class="space-y-2 mb-4">
                        @foreach($checkoutItems as $item)
                            <div class="flex justify-between gap-4 text-sm text-gray-600">
                                <span>{{ $item['name'] }}@if($item['variant_name']) ({{ $item['variant_name'] }})@endif × {{ $item['quantity'] }} <span class="text-xs">({{ number_format($item['unit_price'], 2) }} ل.س)</span></span>
                                <span class="shrink-0">{{ number_format($item['subtotal'], 2) }} ل.س</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between">
                            <span>المجموع الفرعي:</span>
                            <span id="subtotal-display">{{ number_format($baseSubtotal, 2) }} ل.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span>الضريبة ({{ $restaurant->tax_percentage ?? 0 }}٪):</span>
                            <span id="tax-display">{{ number_format($baseTax, 2) }} ل.س</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>المسافة إلى المطعم:</span>
                            <span x-text="formatDistance() || 'حدد موقع التوصيل'">حدد موقع التوصيل</span>
                        </div>
                        <div class="flex justify-between">
                            <span>رسوم التوصيل:</span>
                            <span class="text-right"><span id="delivery-display" x-text="deliveryFeeDisplay">{{ number_format($deliveryFee, 2) }} ل.س</span><small x-cloak x-show="extraKilometers > 0" x-text="deliveryFeeHelper" class="block text-xs font-normal text-gray-500"></small></span>
                        </div>
                        <template x-if="warningMessage.length > 0">
                            <div x-cloak x-show="isOutOfRange" x-ref="geofenceAlert" x-text="warningMessage" class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm font-bold text-red-800" role="alert"></div>
                        </template>
                        
                        <!-- قسم العروض التلقائية -->
                        @if($activeOffer)
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between text-green-600 font-bold">
                                    <span>🎉 عرض خاص ({{ $activeOffer->title }}):</span>
                                    <span id="offer-discount-display">- {{ number_format($autoDiscount, 2) }} ل.س</span>
                                </div>
                            </div>
                        @endif
                        
                        <!-- قسم خصم الكوبون -->
                        <div id="coupon-section" class="hidden border-t pt-2 mt-2">
                            <div class="flex justify-between text-blue-600 font-bold">
                                <span id="coupon-label">🎫 كوبون الخصم:</span>
                                <span id="coupon-amount">- 0.00 ل.س</span>
                            </div>
                            <button type="button" onclick="removeCoupon()" class="text-xs text-red-500 hover:underline mt-1">
                                إزالة الكوبون
                            </button>
                        </div>
                        
                        <div class="flex justify-between text-xl font-black text-primary mt-4 pt-4 border-t">
                            <span>الإجمالي:</span>
                            <span id="final-total" x-text="totalDisplay">{{ number_format($initialFinalTotal, 2) }} ل.س</span>
                        </div>
                    </div>

                    <!-- حقل كوبون الخصم -->
                    <div class="mt-6 pt-6 border-t">
                        <label class="block text-sm font-bold text-gray-700 mb-2">هل لديك كوبون خصم؟</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="coupon-code-input"
                                placeholder="أدخل كود الخصم"
                                class="flex-1 border-gray-300 rounded-lg focus:ring-primary focus:border-primary uppercase"
                            >
                            <button 
                                type="button"
                                onclick="applyCouponCode()"
                                id="apply-coupon-btn"
                                class="btn-primary px-4 py-2 rounded-lg font-bold hover:shadow-lg transition"
                            >
                                تطبيق
                            </button>
                        </div>
                        <p id="coupon-message" class="text-sm mt-2 hidden"></p>
                    </div>
                </div>
            </div>

            <!-- طريقة الدفع والملاحظات -->
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">طريقة الدفع</h3>
                <div class="flex gap-4 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked class="text-primary focus:ring-primary">
                        <span>دفع عند الاستلام 💵</span>
                    </label>
                    {{-- <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="transfer" class="text-primary focus:ring-primary">
                        <span>تحويل بنكي 🏦</span>
                    </label> --}}
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات للطلب (اختياري)</label>
                    <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="مثال: بدون بصل، توصيل سريع..."></textarea>
                </div>
            </div>

            <!-- حقول مخفية -->
            @if($activeOffer)
                <input type="hidden" name="offer_id" value="{{ $activeOffer->id }}">
            @endif
            <input type="hidden" name="coupon_id" id="selected-coupon-id" value="">
            <input type="hidden" name="coupon_code" id="selected-coupon-code" value="">

            <button type="submit" @disabled(! $restaurant->canAcceptOrders()) class="w-full btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition disabled:cursor-not-allowed disabled:opacity-50" :disabled="processing || isOutOfRange || {{ $restaurant->canAcceptOrders() ? 'false' : 'true' }}" :class="{'cursor-not-allowed opacity-50': isOutOfRange}">تأكيد الطلب ✅</button>
        </form>
        <div id="checkout-processing" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" role="status" aria-live="assertive" aria-hidden="true">
            <div class="flex items-center gap-4 rounded-xl bg-white px-6 py-5 text-gray-800 shadow-xl">
                <span class="inline-block h-7 w-7 animate-spin rounded-full border-4 border-current border-r-transparent" aria-hidden="true"></span>
                <span>جارٍ معالجة طلبك...</span>
            </div>
        </div>
    </div>
</section>

@if(!$table)
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/offline-map-tiles.js') }}"></script>
@endif

<script>
    document.getElementById('checkout-form')?.addEventListener('submit', function (e) {
        if (this.dataset.outOfRange === 'true') {
            e.preventDefault();
            return false;
        }

        const overlay = document.getElementById('checkout-processing');
        const submitButton = this.querySelector('[type="submit"]');

        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            overlay.setAttribute('aria-hidden', 'false');
        }
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'جارٍ معالجة الطلب...';
        }
    });

    @if(!$table)
    const restaurantLatitude = Number(@js($restaurantLatitude));
    const restaurantLongitude = Number(@js($restaurantLongitude));
    const defaultLocation = [restaurantLatitude, restaurantLongitude];

    const checkoutMap = L.map('checkout-map', { maxZoom: 19 }).setView(defaultLocation, 16);
    window.addOfflineAwareMapTiles(checkoutMap);

    const restaurantMarkerIcon = L.divIcon({
        className: 'restaurant-map-marker',
        html: '<svg viewBox="0 0 32 42" width="32" height="42" aria-hidden="true"><path d="M16 1C7.7 1 1 7.7 1 16c0 10 15 25 15 25s15-15 15-25C31 7.7 24.3 1 16 1z" fill="#dc2626"/><circle cx="16" cy="16" r="5" fill="white"/></svg>',
        iconSize: [32, 42],
        iconAnchor: [16, 42],
        popupAnchor: [0, -38]
    });

    const customerMarkerIcon = L.divIcon({
        className: 'customer-map-marker',
        html: '<svg viewBox="0 0 32 42" width="32" height="42" aria-hidden="true"><path d="M16 1C7.7 1 1 7.7 1 16c0 10 15 25 15 25s15-15 15-25C31 7.7 24.3 1 16 1z" fill="#2563eb"/><circle cx="16" cy="16" r="5" fill="white"/></svg>',
        iconSize: [32, 42],
        iconAnchor: [16, 42],
        popupAnchor: [0, -38]
    });

    L.marker(defaultLocation, { icon: restaurantMarkerIcon, keyboard: false })
        .addTo(checkoutMap)
        .bindPopup('موقع المطعم');

    const radiusKm = Number(@js($restaurant->delivery_radius_km ?? 10));
    L.circle(defaultLocation, {
        color: '#dc2626',
        fillColor: '#ef4444',
        fillOpacity: 0.1,
        radius: radiusKm * 1000
    }).addTo(checkoutMap);

    let checkoutMarker;
    let hasRestoredCheckoutLocation = false;

    function setCheckoutLocation(latitude, longitude, recenterMap = true) {
        hasRestoredCheckoutLocation = true;
        const lat = Number(latitude);
        const lng = Number(longitude);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        // التقريب وإعادة تركيز الكاميرا بمرونة على الموقع المحدد
        if (recenterMap) {
            checkoutMap.flyTo([lat, lng], 16, {
                animate: true,
                duration: 0.8
            });
        }

        if (!checkoutMarker) {
            checkoutMarker = L.marker([lat, lng], { draggable: true, icon: customerMarkerIcon })
                .addTo(checkoutMap)
                .bindPopup('موقع التوصيل الخاص بك');

            checkoutMarker.on('drag', (e) => {
                const pos = e.target.getLatLng();
                updateLocationAndPricing(pos.lat, pos.lng);
            });

            checkoutMarker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                updateLocationAndPricing(pos.lat, pos.lng);
            });
        } else {
            checkoutMarker.setLatLng([lat, lng]);
        }

        updateLocationAndPricing(lat, lng);
    }

    function updateLocationAndPricing(latitude, longitude) {
        const latStr = String(latitude);
        const lngStr = String(longitude);
        
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const statusText = document.getElementById('map-status');

        if (latInput) latInput.value = latStr;
        if (lngInput) lngInput.value = lngStr;

        if (statusText) {
            statusText.textContent = `تم تحديد الموقع: ${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}`;
        }

        if (window.checkoutPricingState) {
            window.checkoutPricingState.setCustomerLocation(latitude, longitude);
        } else {
            window.dispatchEvent(new CustomEvent('customer-location-updated', {
                detail: { latitude: Number(latitude), longitude: Number(longitude) }
            }));
        }
    }

    checkoutMap.on('click', (event) => {
        setCheckoutLocation(event.latlng.lat, event.latlng.lng, true);
    });

    function showManualLocationHint() {
        const statusText = document.getElementById('map-status');
        if (statusText) {
            statusText.textContent = 'يمكنك سحب الدبوس أو النقر على الخريطة لتحديد موقعك مباشرة';
        }
    }

    function requestCurrentLocation(force = false) {
        if (!navigator.geolocation) {
            showManualLocationHint();
            return;
        }

        try {
            navigator.geolocation.getCurrentPosition((position) => {
                if (hasRestoredCheckoutLocation && !force) return;
                setCheckoutLocation(position.coords.latitude, position.coords.longitude, true);
            }, () => {
                showManualLocationHint();
            }, { enableHighAccuracy: true, timeout: 5000 });
        } catch {
            showManualLocationHint();
        }
    }

    document.getElementById('use-current-location')?.addEventListener('click', () => requestCurrentLocation(true));

    const initialLatInput = document.getElementById('latitude');
    const initialLngInput = document.getElementById('longitude');
    const initialLatitude = Number(initialLatInput?.value);
    const initialLongitude = Number(initialLngInput?.value);

    if (initialLatInput?.value && initialLngInput?.value
        && Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude)
        && Math.abs(initialLatitude) <= 90 && Math.abs(initialLongitude) <= 180) {
        hasRestoredCheckoutLocation = true;
        setCheckoutLocation(initialLatitude, initialLongitude, true);
    }

    if (!initialLatInput?.value) {
        requestCurrentLocation();
    }
    @endif

    // معالجة العروض والكوبونات
    let appliedCouponId = null;
    let couponDiscount = 0;
    const subtotal = {{ $baseSubtotal }};

    async function applyCouponCode() {
        const code = document.getElementById('coupon-code-input')?.value.trim();
        const applyBtn = document.getElementById('apply-coupon-btn');
        const customerPhone = document.getElementById('customer-phone')?.value;

        if (!code) {
            showCouponMessage('الرجاء إدخال كود الخصم', 'error');
            return;
        }

        if (applyBtn) {
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="animate-spin">⏳</span>';
        }

        try {
            const response = await axios.post('{{ route("coupon.apply", $restaurant->slug) }}', {
                coupon_code: code,
                subtotal: subtotal,
                customer_phone: customerPhone
            });

            if (response.data.success) {
                appliedCouponId = response.data.coupon_id;
                couponDiscount = response.data.discount_amount;

                const selectedCouponId = document.getElementById('selected-coupon-id');
                const selectedCouponCode = document.getElementById('selected-coupon-code');
                if (selectedCouponId) selectedCouponId.value = response.data.coupon_id;
                if (selectedCouponCode) selectedCouponCode.value = response.data.coupon_code;
                
                const couponSection = document.getElementById('coupon-section');
                const couponLabel = document.getElementById('coupon-label');
                const couponAmount = document.getElementById('coupon-amount');

                if (couponSection) couponSection.classList.remove('hidden');
                if (couponLabel) couponLabel.textContent = '🎫 كوبون الخصم (' + response.data.coupon_code + '):';
                if (couponAmount) couponAmount.textContent = '- ' + couponDiscount.toFixed(2) + ' ل.س';
                
                updateFinalTotal();
                showCouponMessage(response.data.message, 'success');
            } else {
                showCouponMessage(response.data.message, 'error');
            }
        } catch (error) {
            showCouponMessage(error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى', 'error');
        } finally {
            if (applyBtn) {
                applyBtn.disabled = false;
                applyBtn.innerHTML = 'تطبيق';
            }
        }
    }

    function removeCoupon() {
        appliedCouponId = null;
        couponDiscount = 0;
        
        const selectedCouponId = document.getElementById('selected-coupon-id');
        const selectedCouponCode = document.getElementById('selected-coupon-code');
        const couponSection = document.getElementById('coupon-section');
        const couponCodeInput = document.getElementById('coupon-code-input');
        const couponMessage = document.getElementById('coupon-message');

        if (selectedCouponId) selectedCouponId.value = '';
        if (selectedCouponCode) selectedCouponCode.value = '';
        if (couponSection) couponSection.classList.add('hidden');
        if (couponCodeInput) couponCodeInput.value = '';
        if (couponMessage) couponMessage.classList.add('hidden');
        
        updateFinalTotal();
    }

    function updateFinalTotal() {
        window.checkoutPricingState?.setCouponDiscount(couponDiscount);
    }

    function showCouponMessage(message, type) {
        const messageDiv = document.getElementById('coupon-message');
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = 'text-sm mt-2 ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
            messageDiv.classList.remove('hidden');
        }
    }
</script>
@endsection