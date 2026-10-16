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
            restaurantHasLocation: Boolean(config.restaurantHasLocation),
            deliveryRadius: Number(config.deliveryRadius || 10),
            baseDeliveryFee: Number(config.baseDeliveryFee || 2.0),
            baseDeliveryKilometers: Number(config.baseDeliveryKilometers || 2.0),
            feePerAdditionalKilometer: Number(config.feePerAdditionalKilometer || 0.5),
            currency: config.currency || 'ل.س',
            isDineIn: Boolean(config.isDineIn),
            isRestaurantTableOrder: Boolean(config.isRestaurantTableOrder),
            deliveryType: config.deliveryType || 'delivery',
            depositPercentage: Number(config.depositPercentage || 0),

            // استرجاع البيانات المحفوظة من localStorage تلقائياً مع فحصها
            customerName: localStorage.getItem('customer_name') || '',
            customerPhone: localStorage.getItem('customer_phone') || '',
            customerAddress: localStorage.getItem('customer_address') || '',
            customerLat: localStorage.getItem('customer_lat') || '',
            customerLng: localStorage.getItem('customer_lng') || '',

            distance: null,
            extraKilometers: 0,
            deliveryFeeHelper: '',
            deliveryFee: Number(config.legacyFee || 0),
            total: 0,
            deliveryFeeDisplay: '',
            totalDisplay: '',
            depositDisplay: '',
            requiresDeposit() {
                return !this.isRestaurantTableOrder && (this.deliveryType === 'dine_in' || this.deliveryType === 'takeaway');
            },
            selectedPaymentMethod() {
                return this.requiresDeposit() ? 'bank_transfer' : 'cash';
            },
            isOutOfRange: false,
            warningMessage: '',
            couponDiscount: 0,
            processing: false,

            init() {
                console.log('🔍 [TEST-INIT] Component initialized with config:', config);
                console.log('🔍 [TEST-INIT] Initial deliveryType:', this.deliveryType);
                console.log('🔍 [TEST-INIT] Initial raw LocalStorage Coords -> Lat:', localStorage.getItem('customer_lat'), '| Lng:', localStorage.getItem('customer_lng'));

                window.checkoutPricingState = this;
                this.isDineIn = this.deliveryType === 'dine_in';

                // استعادة البيانات عند التحميل ومزامنتها مع الحقول
                this.$nextTick(() => {
                    console.log('🔍 [TEST-NEXT-TICK] Running restoreCustomerData...');
                    this.restoreCustomerData();
                });

                // الاستماع لأحداث الخريطة
                window.addEventListener('customer-location-updated', (event) => {
                    console.log('⚡ [TEST-EVENT] customer-location-updated triggered:', event.detail);
                    const { latitude, longitude } = event.detail || {};
                    if (latitude && longitude) {
                        this.setCustomerLocation(latitude, longitude);
                    } else {
                        console.warn('⚠️ [TEST-EVENT] Received invalid latitude or longitude in event detail:', event.detail);
                    }
                });

                this.recalculate();
            },

            restoreCustomerData() {
                console.log('🔄 [TEST-RESTORE] Entering restoreCustomerData...');
                const form = document.getElementById('checkout-form');
                if (!form) {
                    console.warn('⚠️ [TEST-RESTORE] checkout-form element not found in DOM!');
                    return;
                }

                const nameInput = form.querySelector('[name="name"]');
                const phoneInput = form.querySelector('[name="phone"]');
                const addrInput = form.querySelector('[name="address"]');
                const latInput = form.querySelector('[name="latitude"]');
                const lngInput = form.querySelector('[name="longitude"]');

                console.log('📥 [TEST-RESTORE] DOM Input values before restore:', {
                    name: nameInput?.value,
                    phone: phoneInput?.value,
                    address: addrInput?.value,
                    lat: latInput?.value,
                    lng: lngInput?.value
                });

                if (nameInput?.value) this.customerName = nameInput.value;
                else if (this.customerName && nameInput) nameInput.value = this.customerName;

                if (phoneInput?.value) this.customerPhone = phoneInput.value;
                else if (this.customerPhone && phoneInput) phoneInput.value = this.customerPhone;

                if (addrInput?.value) this.customerAddress = addrInput.value;
                else if (this.customerAddress && addrInput) addrInput.value = this.customerAddress;

                if (latInput?.value) this.customerLat = latInput.value;
                else if (this.customerLat && latInput) latInput.value = this.customerLat;

                if (lngInput?.value) this.customerLng = lngInput.value;
                else if (this.customerLng && lngInput) lngInput.value = this.customerLng;

                const parsedLat = Number(this.customerLat);
                const parsedLng = Number(this.customerLng);

                console.log('🎯 [TEST-RESTORE] Evaluated coordinates state:', {
                    customerLat: this.customerLat,
                    customerLng: this.customerLng,
                    parsedLat: parsedLat,
                    parsedLng: parsedLng,
                    isFiniteLat: Number.isFinite(parsedLat),
                    isFiniteLng: Number.isFinite(parsedLng),
                    deliveryType: this.deliveryType
                });

                // الاختبار الحاسم: منع إرسال إحداثيات فارغة أو NaN أو إن لم يكن النمط توصيلاً
                if (
                    this.deliveryType === 'delivery' &&
                    this.customerLat !== '' &&
                    this.customerLng !== '' &&
                    Number.isFinite(parsedLat) &&
                    Number.isFinite(parsedLng) &&
                    parsedLat !== 0 &&
                    parsedLng !== 0 &&
                    typeof setCheckoutLocation === 'function'
                ) {
                    console.log('🚀 [TEST-RESTORE] Conditions met! Calling setCheckoutLocation with:', parsedLat, parsedLng);
                    setCheckoutLocation(parsedLat, parsedLng, true);
                } else {
                    console.log('ℹ️ [TEST-RESTORE] Skipped calling setCheckoutLocation (Coordinates invalid, zero, NaN, or order type is not delivery).');
                }
            },

            clearCustomerLocation() {
                console.log('🧹 [TEST-CLEAR] Clearing customer location data and cookies...');
                this.customerLat = '';
                this.customerLng = '';

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');

                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';

                localStorage.removeItem('customer_lat');
                localStorage.removeItem('customer_lng');
                document.cookie = 'customer_lat=; Max-Age=0; Path=/; SameSite=Lax';
                document.cookie = 'customer_lng=; Max-Age=0; Path=/; SameSite=Lax';
                console.log('✨ [TEST-CLEAR] Location cleared successfully.');
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
                console.log('💾 [TEST-PERSIST] Saving customer data to localStorage and cookies:', {
                    name: this.customerName,
                    phone: this.customerPhone,
                    lat: this.customerLat,
                    lng: this.customerLng
                });

                localStorage.setItem('customer_name', this.customerName || '');
                localStorage.setItem('customer_phone', this.customerPhone || '');
                localStorage.setItem('customer_address', this.customerAddress || '');
                localStorage.setItem('customer_lat', this.customerLat || '');
                localStorage.setItem('customer_lng', this.customerLng || '');

                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                const cookieOptions = `; Max-Age=2592000; Path=/; SameSite=Lax${secure}`;

                document.cookie = `customer_name=${encodeURIComponent(this.customerName || '')}${cookieOptions}`;
                document.cookie = `customer_phone=${encodeURIComponent(this.customerPhone || '')}${cookieOptions}`;
                document.cookie = `customer_address=${encodeURIComponent(this.customerAddress || '')}${cookieOptions}`;
                document.cookie = `customer_lat=${encodeURIComponent(this.customerLat || '')}${cookieOptions}`;
                document.cookie = `customer_lng=${encodeURIComponent(this.customerLng || '')}${cookieOptions}`;
            },

            setCustomerLocation(latitude, longitude) {
                console.log('📍 [TEST-SET-LOC] setCustomerLocation invoked with:', latitude, longitude);
                this.customerLat = String(latitude);
                this.customerLng = String(longitude);

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                if (latInput) latInput.value = this.customerLat;
                if (lngInput) lngInput.value = this.customerLng;

                this.persistCustomer();
                this.recalculate();
            },

            setDeliveryType(deliveryType) {
                console.log('🔀 [TEST-DELIVERY-TYPE] Delivery type changed to:', deliveryType);
                this.deliveryType = deliveryType;
                this.isDineIn = deliveryType === 'dine_in';
                this.recalculate();
                window.dispatchEvent(new Event('checkout-delivery-mode-changed'));
            },

            recalculate() {
                const lat = Number(this.customerLat);
                const lng = Number(this.customerLng);
                const restLat = this.restaurantLatitude;
                const restLng = this.restaurantLongitude;

                console.log('🧮 [TEST-RECALC] Recalculating pricing & distance with:', { lat, lng, restLat, restLng, deliveryType: this.deliveryType });

                const hasCustomer = this.customerLat !== '' && this.customerLng !== ''
                    && Number.isFinite(lat) && Number.isFinite(lng);
                const hasRestaurant = Number.isFinite(restLat) && Number.isFinite(restLng);

                this.isOutOfRange = false;
                this.warningMessage = '';
                this.distance = null;
                this.extraKilometers = 0;

                if (this.isDineIn || this.deliveryType === 'takeaway') {
                    this.deliveryFee = 0;
                    console.log('🏷️ [TEST-RECALC] Mode is Dine-In or Takeaway. Delivery fee set to 0.');
                } else if (!this.restaurantHasLocation) {
                    this.deliveryFee = this.legacyFee;
                    this.deliveryFeeHelper = '(رسوم توصيل ثابتة)';
                    console.log('🏷️ [TEST-RECALC] Restaurant has no location. Using legacy fee:', this.deliveryFee);
                } else if (hasCustomer && hasRestaurant) {
                    const dLat = (lat - restLat) * Math.PI / 180;
                    const dLng = (lng - restLng) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 + Math.cos(restLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
                    const c = 2 * Math.atan2(Math.sqrt(Math.min(1, Math.max(0, a))), Math.sqrt(1 - Math.min(1, Math.max(0, a))));

                    this.distance = 6371 * c;
                    this.extraKilometers = Math.max(0, this.distance - this.baseDeliveryKilometers);
                    this.deliveryFee = Math.round((this.baseDeliveryFee + (this.extraKilometers * this.feePerAdditionalKilometer)) * 100) / 100;

                    console.log('📏 [TEST-RECALC] Distance calculated:', this.distance, 'km | Fee:', this.deliveryFee);

                    if (this.distance > this.deliveryRadius) {
                        this.isOutOfRange = true;
                        this.warningMessage = `نعتذر، موقعك يبعد ${this.distance.toFixed(2)} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم (الحد الأقصى ${this.deliveryRadius.toFixed(2)} كم)`;
                        console.warn('🚨 [TEST-RECALC] Warning: Customer is out of range!', this.warningMessage);
                    }
                } else {
                    this.deliveryFee = this.baseDeliveryFee;
                    console.log('🏷️ [TEST-RECALC] Using base delivery fee:', this.deliveryFee);
                }

                if (this.restaurantHasLocation) {
                    this.deliveryFeeHelper = `(شاملة الرسم الأساسي + ${this.extraKilometers.toFixed(2)} كم إضافي)`;
                }
                this.deliveryFeeDisplay = `${this.deliveryFee.toFixed(2)} ${this.currency}`;
                this.total = this.subtotal + this.tax + this.deliveryFee - this.offerDiscount - this.couponDiscount;
                this.totalDisplay = `${this.total.toFixed(2)} ${this.currency}`;
                this.depositDisplay = `${(Math.max(0, this.total) * this.depositPercentage / 100).toFixed(2)} ${this.currency}`;

                const form = document.getElementById('checkout-form');
                if (form) {
                    form.dataset.outOfRange = this.isOutOfRange ? 'true' : 'false';
                }
            },

            formatDistance() {
                return this.distance === null ? '' : `${this.distance.toFixed(2)} كم`;
            },

            validateSubmission(event) {
                console.log('📤 [TEST-SUBMIT] Form submission intercepted. Validating...');
                this.persistCustomer();
                this.recalculate();

                if (this.isOutOfRange) {
                    console.error('❌ [TEST-SUBMIT] Blocked submission because customer is out of range.');
                    event.preventDefault();
                    this.$nextTick(() => {
                        this.$refs.geofenceAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                    return;
                }

                console.log('✅ [TEST-SUBMIT] Validation passed. Proceeding with submission.');
                this.processing = true;
            },

            setCouponDiscount(discount) {
                console.log('🎟️ [TEST-COUPON] Coupon discount applied:', discount);
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
    restaurantHasLocation: @js(filled($restaurant->latitude) && filled($restaurant->longitude)),
    deliveryRadius: @js($restaurant->delivery_radius_km ?? 10),
    baseDeliveryFee: @js($restaurant->base_delivery_fee ?? 2.0),
    baseDeliveryKilometers: @js($restaurant->base_delivery_km ?? 2.0),
    feePerAdditionalKilometer: @js($restaurant->per_km_delivery_fee ?? 0.5),
    currency: @js($restaurant->currency_symbol ?? 'ل.س'),
    isDineIn: @js((bool) $table),
    isRestaurantTableOrder: @js((bool) $table),
    deliveryType: @js($initialDeliveryType),
    depositPercentage: @js($depositPercentage)
})" data-restaurant-latitude="{{ $restaurantLatitude }}" data-restaurant-longitude="{{ $restaurantLongitude }}">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <header class="mb-8 text-center">
            <span class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-2xl text-primary">
                <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
            </span>
            <h1 class="text-3xl font-black text-gray-900">إتمام الطلب</h1>
            <p class="mt-2 text-sm text-gray-500">اختر الطريقة المناسبة وأكمل بيانات طلبك</p>
        </header>

        <form action="{{ route('checkout.store', $restaurant->slug) }}" method="POST" enctype="multipart/form-data" id="checkout-form" class="space-y-6" @submit="validateSubmission($event)">
            @csrf
            @if(!$table)
                <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-primary"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
                        <div>
                            <h2 class="text-lg font-black text-gray-900">طريقة استلام الطلب</h2>
                            <p class="text-sm text-gray-500">اختر الطريقة المناسبة لك</p>
                        </div>
                    </div>
                    <input type="hidden" name="delivery_type" :value="deliveryType">
                    <div class="grid grid-cols-1 gap-3 {{ $supportsTableReservations || $supportsScheduledPickup ? 'sm:grid-cols-3' : 'sm:grid-cols-1' }}">
                        <label class="cursor-pointer rounded-2xl border-2 p-4 transition" :class="deliveryType === 'delivery' ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-100' : 'border-gray-200 hover:border-orange-200'">
                            <input type="radio" value="delivery" x-model="deliveryType" @change="setDeliveryType('delivery')" class="sr-only">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-motorcycle text-xl text-primary" aria-hidden="true"></i>
                                <span><strong class="block text-gray-900">توصيل للمنزل</strong><small class="text-gray-500">يصلك الطلب إلى عنوانك</small></span>
                            </span>
                        </label>
                        @if($supportsTableReservations)
                            <label class="cursor-pointer rounded-2xl border-2 p-4 transition" :class="deliveryType === 'dine_in' ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-100' : 'border-gray-200 hover:border-orange-200'">
                                <input type="radio" value="dine_in" x-model="deliveryType" @change="setDeliveryType('dine_in')" class="sr-only">
                                <span class="flex items-center gap-3">
                                    <i class="fa-solid fa-chair text-xl text-primary" aria-hidden="true"></i>
                                    <span><strong class="block text-gray-900">حجز طاولة</strong><small class="text-gray-500">اختر الموعد والطاولة المناسبة</small></span>
                                </span>
                            </label>
                        @endif
                        @if($supportsScheduledPickup)
                            <label class="cursor-pointer rounded-2xl border-2 p-4 transition" :class="deliveryType === 'takeaway' ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-100' : 'border-gray-200 hover:border-orange-200'">
                                <input type="radio" value="takeaway" x-model="deliveryType" @change="setDeliveryType('takeaway')" class="sr-only">
                                <span class="flex items-center gap-3">
                                    <i class="fa-solid fa-store text-xl text-primary" aria-hidden="true"></i>
                                    <span><strong class="block text-gray-900">استلام من المطعم</strong><small class="text-gray-500">حدد وقت استلام طلبك</small></span>
                                </span>
                            </label>
                        @endif
                    </div>
                </section>
            @else
                <input type="hidden" name="delivery_type" value="dine_in">
                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
                    طلب الطاولة <strong>{{ $table->number }}</strong> سيصل مباشرة إلى المطبخ.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- بيانات العميل -->
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2" x-text="isRestaurantTableOrder ? 'بيانات طلب الطاولة' : (deliveryType === 'dine_in' ? 'بيانات حجز الطاولة' : (deliveryType === 'takeaway' ? 'بيانات استلام الطلب' : 'بيانات التوصيل'))">{{ $table ? 'طلب داخل المطعم' : 'بيانات التواصل' }}</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                        <input type="text" name="name" id="customer-name" value="{{ old('name') }}" x-model="customerName" @input="persistCustomer()" required autocomplete="name" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" id="customer-phone" value="{{ old('phone') }}" x-model="customerPhone" @input="persistCustomer()" required pattern="(09|\+?9639)\d{8}" title="أدخل رقم هاتف سوري يبدأ بـ 09 أو 9639" inputmode="tel" autocomplete="tel" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    @if(!$table)<div x-show="deliveryType === 'delivery'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea name="address" id="address" rows="3" x-model="customerAddress" @input="persistCustomer()" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="وصف اختياري: الطابق، رقم الشقة، علامة مميزة...">{{ old('address') }}</textarea>
                        <div id="checkout-map" class="mt-3 h-64 rounded-xl border border-gray-200"></div>
                        <p id="map-status" class="mt-2 text-sm text-gray-500">اضغط على الخريطة لتحديد موقع المنزل.</p>
                        <button type="button" id="use-current-location" class="mt-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">تحديد موقعي الحالي</button>
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" x-model="customerLat" @input="persistCustomer(); recalculate()" :required="deliveryType === 'delivery'">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" x-model="customerLng" @input="persistCustomer(); recalculate()" :required="deliveryType === 'delivery'">
                    </div>@endif
                    @if($supportsTableReservations && !$table)
                        <fieldset x-show="deliveryType === 'dine_in'" x-cloak x-bind:disabled="deliveryType !== 'dine_in'" class="space-y-4 rounded-xl border border-orange-200 bg-orange-50 p-4">
                            <h4 class="font-bold text-orange-900">موعد الحجز وتفاصيل الجلسة</h4>
                            @error('dine_in_arrival_at')
                                <p class="rounded-lg bg-red-100 p-3 text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>
                            @enderror
                            @error('dine_in_guest_count')
                                <p class="rounded-lg bg-red-100 p-3 text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>
                            @enderror
                            <div>
                                <label for="dine-in-arrival-at" class="mb-1 block text-sm font-medium text-gray-700">وقت الوصول <span class="text-red-500">*</span></label>
                                <input id="dine-in-arrival-at" type="datetime-local" name="dine_in_arrival_at" value="{{ old('dine_in_arrival_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" :required="deliveryType === 'dine_in'" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="dine-in-guest-count" class="mb-1 block text-sm font-medium text-gray-700">عدد الضيوف</label>
                                    <input id="dine-in-guest-count" type="number" name="dine_in_guest_count" value="{{ old('dine_in_guest_count', 2) }}" min="1" max="20" :required="deliveryType === 'dine_in'" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                </div>
                                <div>
                                    <label for="dine-in-duration-minutes" class="mb-1 block text-sm font-medium text-gray-700">مدة الحجز</label>
                                    <select id="dine-in-duration-minutes" name="dine_in_duration_minutes" :required="deliveryType === 'dine_in'" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                        @foreach($experimentalOrderFeatures['reservation_duration_options'] ?: [90] as $durationOption)
                                            <option value="{{ $durationOption }}" @selected((int) old('dine_in_duration_minutes', $experimentalOrderFeatures['reservation_duration_minutes']) === $durationOption)>
                                                {{ $durationOption < 60 ? $durationOption.' دقيقة' : ($durationOption % 60 === 0 ? ($durationOption / 60).' ساعة' : intdiv($durationOption, 60).' ساعة و'.($durationOption % 60).' دقيقة') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="restaurant-table-id" class="mb-1 block text-sm font-medium text-gray-700">الطاولة المتاحة</label>
                                    <select id="restaurant-table-id" name="restaurant_table_id" required disabled class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                        <option value="">اختر موعد الوصول وعدد الضيوف أولاً</option>
                                    </select>
                                    @error('restaurant_table_id')
                                        <p class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>
                                    @enderror
                                    <p id="table-availability-status" class="mt-1 text-xs text-gray-600" aria-live="polite"></p>
                                </div>
                                @if($experimentalOrderFeatures['reservation_seating_preference'])
                                    <div>
                                    <label for="dine-in-seating-preference" class="mb-1 block text-sm font-medium text-gray-700">تفضيل الجلسة</label>
                                    <select id="dine-in-seating-preference" name="dine_in_seating_preference" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                        <option value="any">بدون تفضيل</option>
                                        <option value="indoor" @selected(old('dine_in_seating_preference') === 'indoor')>داخلية</option>
                                        <option value="outdoor" @selected(old('dine_in_seating_preference') === 'outdoor')>خارجية</option>
                                        <option value="window" @selected(old('dine_in_seating_preference') === 'window')>بجانب النافذة</option>
                                        <option value="quiet" @selected(old('dine_in_seating_preference') === 'quiet')>مكان هادئ</option>
                                        <option value="family" @selected(old('dine_in_seating_preference') === 'family')>جلسة عائلية</option>
                                    </select>
                                </div>
                            @endif
                            @if($experimentalOrderFeatures['reservation_occasion'])
                                <div>
                                    <label for="dine-in-occasion" class="mb-1 block text-sm font-medium text-gray-700">المناسبة</label>
                                    <select id="dine-in-occasion" name="dine_in_occasion" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                        <option value="none">زيارة عادية</option>
                                        <option value="birthday" @selected(old('dine_in_occasion') === 'birthday')>عيد ميلاد</option>
                                        <option value="anniversary" @selected(old('dine_in_occasion') === 'anniversary')>ذكرى سنوية</option>
                                        <option value="family" @selected(old('dine_in_occasion') === 'family')>لقاء عائلي</option>
                                        <option value="business" @selected(old('dine_in_occasion') === 'business')>اجتماع عمل</option>
                                        <option value="other" @selected(old('dine_in_occasion') === 'other')>أخرى</option>
                                    </select>
                                </div>
                            @endif
                            @if($experimentalOrderFeatures['reservation_high_chairs'])
                                <div>
                                    <label for="dine-in-high-chair-count" class="mb-1 block text-sm font-medium text-gray-700">كراسي أطفال</label>
                                    <input id="dine-in-high-chair-count" type="number" name="dine_in_high_chair_count" value="{{ old('dine_in_high_chair_count', 0) }}" min="0" max="10" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                </div>
                            @endif
                            @if($experimentalOrderFeatures['reservation_dietary_preference'])
                                <div>
                                    <label for="dine-in-dietary-preference" class="mb-1 block text-sm font-medium text-gray-700">التفضيل الغذائي</label>
                                    <select id="dine-in-dietary-preference" name="dine_in_dietary_preference" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                                        <option value="none">لا يوجد</option>
                                        <option value="vegetarian" @selected(old('dine_in_dietary_preference') === 'vegetarian')>نباتي</option>
                                        <option value="vegan" @selected(old('dine_in_dietary_preference') === 'vegan')>نباتي صرف</option>
                                        <option value="gluten_free" @selected(old('dine_in_dietary_preference') === 'gluten_free')>خالٍ من الغلوتين</option>
                                        <option value="other" @selected(old('dine_in_dietary_preference') === 'other')>أخرى</option>
                                    </select>
                                </div>
                            @endif
                            </div>
                            @if($experimentalOrderFeatures['reservation_stroller_space'])
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="dine_in_stroller_space" value="1" @checked(old('dine_in_stroller_space')) class="rounded border-gray-300 text-primary focus:ring-primary">
                                أحتاج مساحة لعربة أطفال
                            </label>
                            @endif
                            @if($experimentalOrderFeatures['reservation_confirmation_call'])
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="dine_in_confirmation_call" value="1" @checked(old('dine_in_confirmation_call')) class="rounded border-gray-300 text-primary focus:ring-primary">
                                أرغب باتصال لتأكيد الحجز
                            </label>
                            @endif
                            @if($experimentalOrderFeatures['reservation_allergy_notes'])
                            <div>
                                <label for="dine-in-allergy-notes" class="mb-1 block text-sm font-medium text-gray-700">الحساسية أو المكونات الواجب تجنبها</label>
                                <textarea id="dine-in-allergy-notes" name="dine_in_allergy_notes" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">{{ old('dine_in_allergy_notes') }}</textarea>
                            </div>
                            @endif
                            @if($experimentalOrderFeatures['reservation_accessibility_notes'])
                            <div>
                                <label for="dine-in-accessibility-notes" class="mb-1 block text-sm font-medium text-gray-700">متطلبات الوصول أو الحركة</label>
                                <textarea id="dine-in-accessibility-notes" name="dine_in_accessibility_notes" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">{{ old('dine_in_accessibility_notes') }}</textarea>
                            </div>
                            @endif
                            @if($experimentalOrderFeatures['reservation_celebration_notes'])
                            <div>
                                <label for="dine-in-celebration-notes" class="mb-1 block text-sm font-medium text-gray-700">تجهيزات أو طلبات للمناسبة</label>
                                <textarea id="dine-in-celebration-notes" name="dine_in_celebration_notes" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary" placeholder="مثال: تجهيز شمعة صغيرة لعيد الميلاد">{{ old('dine_in_celebration_notes') }}</textarea>
                            </div>
                            @endif
                        </fieldset>
                    @endif
                    @if($supportsScheduledPickup && !$table)
                        <fieldset x-show="deliveryType === 'takeaway'" x-cloak x-bind:disabled="deliveryType !== 'takeaway'" class="space-y-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <h4 class="font-bold text-gray-900">موعد الاستلام من المطعم</h4>
                            <p class="text-sm text-gray-600">يتطلب الاستلام بموعد عربوناً مسبقاً ومراجعة المطعم للإيصال.</p>
                            @error('requested_pickup_at')<p class="text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>@enderror
                            <div>
                                <label for="requested-pickup-at" class="mb-1 block text-sm font-medium text-gray-700">وقت الاستلام <span class="text-red-500">*</span></label>
                                <input id="requested-pickup-at" type="datetime-local" name="requested_pickup_at" value="{{ old('requested_pickup_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" :required="deliveryType === 'takeaway'" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                            </div>
                        </fieldset>
                    @endif
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
                        <div x-show="deliveryType === 'delivery'" class="flex justify-between text-sm text-gray-600">
                            <span>المسافة إلى المطعم:</span>
                            <span x-text="formatDistance() || 'حدد موقع التوصيل'">حدد موقع التوصيل</span>
                        </div>
                        <div x-show="deliveryType === 'delivery'" class="flex justify-between">
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
                                    <span><i class="fa-solid fa-tags ml-1" aria-hidden="true"></i> عرض خاص ({{ $activeOffer->title }}):</span>
                                    <span id="offer-discount-display">- {{ number_format($autoDiscount, 2) }} ل.س</span>
                                </div>
                            </div>
                        @endif
                        
                        <!-- قسم خصم الكوبون -->
                        <div id="coupon-section" class="hidden border-t pt-2 mt-2">
                            <div class="flex justify-between text-blue-600 font-bold">
                                <span id="coupon-label"><i class="fa-solid fa-ticket ml-1" aria-hidden="true"></i> كوبون الخصم:</span>
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
                        @if(!$table && ($supportsTableReservations || $supportsScheduledPickup))
                            <div x-show="requiresDeposit()" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex justify-between gap-3 font-bold text-amber-900">
                                    <span>العربون المطلوب ({{ number_format($depositPercentage, 0) }}٪)</span>
                                    <span x-text="depositDisplay">{{ number_format($initialFinalTotal * $depositPercentage / 100, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-amber-800">يراجع المطعم إيصال التحويل ويؤكد استلام العربون قبل إرسال الطلب للمطبخ.</p>
                            </div>
                            @error('payment_receipt')<p class="rounded-lg bg-red-100 p-3 text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>@enderror
                            <fieldset x-show="requiresDeposit()" x-cloak x-bind:disabled="!requiresDeposit()" class="space-y-2 rounded-xl border border-gray-200 bg-white p-4">
                                <h4 class="font-bold text-gray-900">الدفع المسبق عبر تحويل</h4>
                                @if(filled($restaurant->bank_details))
                                    <p class="whitespace-pre-line text-sm text-gray-700">{{ $restaurant->bank_details }}</p>
                                @else
                                    <p class="text-sm text-red-700">لم يضف المطعم تفاصيل التحويل بعد. لا يمكن استخدام الخيارات التجريبية حالياً.</p>
                                @endif
                                <label for="payment-receipt" class="block text-sm font-semibold text-gray-700">أرفق إيصال التحويل <span class="text-red-500">*</span></label>
                                <input id="payment-receipt" type="file" name="payment_receipt" accept="image/jpeg,image/png,application/pdf" :required="requiresDeposit()" class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-100 file:px-4 file:py-2 file:font-bold file:text-gray-800">
                                <p class="text-xs text-gray-500">صورة JPG أو PNG أو ملف PDF بحد أقصى 5 ميغابايت.</p>
                            </fieldset>
                        @endif
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
                <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">الدفع وملاحظات الطلب</h3>
                <p x-show="deliveryType === 'delivery'" class="mb-4 rounded-xl bg-gray-100 p-3 text-sm text-gray-700">الدفع نقداً عند الاستلام.</p>
                <p x-show="requiresDeposit()" x-cloak class="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">يتطلب هذا الخيار تحويل العربون مسبقاً، ثم يراجع المطعم الإيصال ويؤكد الطلب.</p>
                <input type="hidden" name="payment_method" :value="selectedPaymentMethod()">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="order-notes">ملاحظات الطلب (اختياري)</label>
                    <textarea id="order-notes" name="notes" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="ملاحظات تخص الوجبات">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- حقول مخفية -->
            @if($activeOffer)
                <input type="hidden" name="offer_id" value="{{ $activeOffer->id }}">
            @endif
            <input type="hidden" name="coupon_id" id="selected-coupon-id" value="">
            <input type="hidden" name="coupon_code" id="selected-coupon-code" value="">

            <button type="submit" @disabled(! $restaurant->canAcceptOrders()) class="w-full btn-primary rounded-xl py-4 text-lg font-bold shadow-md transition hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50" :disabled="processing || isOutOfRange || {{ $restaurant->canAcceptOrders() ? 'false' : 'true' }}" :class="{'cursor-not-allowed opacity-50': isOutOfRange}"><span x-text="deliveryType === 'dine_in' ? 'المتابعة لحجز الطاولة' : (deliveryType === 'takeaway' ? 'المتابعة لحجز الاستلام' : 'تأكيد طلب التوصيل')">تأكيد طلب التوصيل</span> <i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i></button>
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
    @if($supportsTableReservations)
        <script>
            const tableAvailabilityForm = document.getElementById('checkout-form');
            const arrivalInput = document.getElementById('dine-in-arrival-at');
            const guestCountInput = document.getElementById('dine-in-guest-count');
            const durationInput = document.getElementById('dine-in-duration-minutes');
            const tableSelect = document.getElementById('restaurant-table-id');
            const tableAvailabilityStatus = document.getElementById('table-availability-status');

            async function refreshAvailableTables() {
                if (!arrivalInput?.value || !guestCountInput?.value || !durationInput?.value || !tableSelect) {
                    return;
                }

                tableSelect.disabled = true;
                tableSelect.replaceChildren(new Option('جارٍ البحث عن الطاولات المتاحة...', ''));
                tableAvailabilityStatus.textContent = '';

                try {
                    const response = await fetch(@js(route('checkout.table-availability', $restaurant->slug)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': tableAvailabilityForm.querySelector('[name="_token"]').value,
                        },
                        body: JSON.stringify({
                            arrival_at: arrivalInput.value,
                            guest_count: guestCountInput.value,
                            duration_minutes: durationInput.value,
                        }),
                    });
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'تعذر التحقق من الطاولات المتاحة.');
                    }

                    const tables = result.tables || [];
                    tableSelect.replaceChildren(new Option(
                        tables.length ? 'اختر طاولة' : 'لا توجد طاولات متاحة في هذا الموعد',
                        '',
                    ));

                    tables.forEach((table) => {
                        const label = `${table.name || `طاولة ${table.number}`} · ${table.seats} مقاعد`;
                        tableSelect.add(new Option(label, table.id, false, String(table.id) === @js((string) old('restaurant_table_id', ''))));
                    });

                    tableSelect.disabled = tables.length === 0;
                    tableAvailabilityStatus.textContent = tables.length
                        ? `وجدنا ${tables.length} طاولة متاحة للمدة المختارة (${durationInput.selectedOptions[0]?.textContent.trim()}).`
                        : 'غيّر الوقت أو عدد الضيوف للبحث عن طاولة أخرى.';
                } catch (error) {
                    tableSelect.replaceChildren(new Option('تعذر تحميل الطاولات', ''));
                    tableAvailabilityStatus.textContent = error.message;
                }
            }

            arrivalInput?.addEventListener('change', refreshAvailableTables);
            guestCountInput?.addEventListener('input', refreshAvailableTables);
            durationInput?.addEventListener('change', refreshAvailableTables);

            if (arrivalInput?.value && guestCountInput?.value && durationInput?.value) {
                refreshAvailableTables();
            }
        </script>
    @endif
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('js/offline-map-tiles.js') }}"></script>
@endif

<script>
    console.log('🚀 [MAP-SCRIPT] Map and coupon script loaded.');

    document.getElementById('checkout-form')?.addEventListener('submit', function (e) {
        console.log('📤 [TEST-FORM-SUBMIT] Form submit event intercepted.');
        if (this.dataset.outOfRange === 'true') {
            console.error('❌ [TEST-FORM-SUBMIT] Prevented submission: Customer is out of range.');
            e.preventDefault();
            return false;
        }

        const overlay = document.getElementById('checkout-processing');
        const submitButton = this.querySelector('[type="submit"]');

        if (overlay) {
            console.log('⏳ [TEST-FORM-SUBMIT] Showing processing overlay.');
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

    if (Number.isFinite(restaurantLatitude) && Number.isFinite(restaurantLongitude)) {
        const defaultLocation = [restaurantLatitude, restaurantLongitude];
        console.log('🏢 [TEST-MAP] Restaurant default location initialized:', defaultLocation);

        // تهيئة الخريطة مع حماية الأبعاد
        const checkoutMap = L.map('checkout-map', { maxZoom: 19 }).setView(defaultLocation, 16);
        window.addOfflineAwareMapTiles(checkoutMap);

        // حل مشكلة النصف الرمادي وعدم اكتمال قطع الخريطة عبر إعادة ضبط الحجم تلقائياً
        setTimeout(() => {
            checkoutMap.invalidateSize();
            console.log('🗺️ [TEST-MAP] Map size invalidated and refreshed to load missing tiles.');
        }, 300);

        // الاستماع لتغير وضعية التوصيل لإعادة ضبط الخريطة إذا ظهرت بعد إخفاء
        window.addEventListener('checkout-delivery-mode-changed', () => {
            setTimeout(() => {
                checkoutMap.invalidateSize();
            }, 200);
        });

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
        // رسم دائرة الحد الأقصى للتوصيل مع التأكد من إضافتها بنجاح
        L.circle(defaultLocation, {
            color: '#dc2626',
            fillColor: '#ef4444',
            fillOpacity: 0.1,
            radius: radiusKm * 1000
        }).addTo(checkoutMap);

        let checkoutMarker;
        let hasRestoredCheckoutLocation = false;

        function setCheckoutLocation(latitude, longitude, recenterMap = true) {
            console.log('📍 [TEST-SET-CHECKOUT-LOC] Called with raw args -> Lat:', latitude, '| Lng:', longitude);
            
            hasRestoredCheckoutLocation = true;
            const lat = Number(latitude);
            const lng = Number(longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat === 0 || lng === 0) {
                console.error('❌ [TEST-MAP ERROR] Blocked invalid/NaN/Zero LatLng from crashing Leaflet!', { latitude, longitude });
                return;
            }

            if (recenterMap && typeof checkoutMap !== 'undefined') {
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

        document.getElementById('use-current-location')?.addEventListener('click', () => {
            requestCurrentLocation(true);
        });

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
                if (couponLabel) {
                    const couponIcon = document.createElement('i');
                    couponIcon.className = 'fa-solid fa-ticket ml-1';
                    couponIcon.setAttribute('aria-hidden', 'true');
                    couponLabel.replaceChildren(couponIcon, document.createTextNode(' كوبون الخصم (' + response.data.coupon_code + '):'));
                }
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