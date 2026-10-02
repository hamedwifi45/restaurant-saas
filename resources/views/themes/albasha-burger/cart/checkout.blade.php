@extends("themes.burger-theme.layout")

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@section('content')
<section class="py-16 bg-gray-50 min-h-screen" x-data="checkoutPricing({
    subtotal: @js($baseSubtotal),
    tax: @js($baseTax),
    legacyFee: @js($deliveryFee),
    offerDiscount: @js($autoDiscount),
    restaurantLatitude: @js($restaurant->latitude),
    restaurantLongitude: @js($restaurant->longitude),
    deliveryRadius: @js($restaurant->delivery_radius_km),
    baseDeliveryFee: @js($restaurant->base_delivery_fee),
    baseDeliveryKilometers: @js($restaurant->base_delivery_km),
    feePerAdditionalKilometer: @js($restaurant->per_km_delivery_fee),
    currency: @js($restaurant->currency_symbol ?? 'ر.س'),
    isDineIn: @js((bool) $table)
})">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-4xl font-black text-center mb-12 text-gray-800">إتمام الطلب 📝</h1>

        <form action="{{ route('checkout.store', $restaurant->slug) }}" method="POST" id="checkout-form" class="bg-white p-8 rounded-2xl shadow-lg" @submit="validateSubmission($event)">
            @csrf
            
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
                        <input type="tel" name="phone" id="customer-phone" value="{{ old('phone') }}" x-model="customerPhone" @input="persistCustomer()" required pattern="(09|\+?9639)\d{8}" title="أدخل رقم هاتف سوري يبدأ بـ 09 أو 9639" inputmode="tel" autocomplete="tel" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    @if(!$table)<div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea name="address" id="address" rows="3" x-model="customerAddress" @input="persistCustomer()" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="وصف اختياري: الطابق، رقم الشقة، علامة مميزة...">{{ old('address') }}</textarea>
                        <div id="checkout-map" class="mt-3 h-64 rounded-xl border border-gray-200"></div>
                        <p id="map-status" class="mt-2 text-sm text-gray-500">اضغط على الخريطة لتحديد موقع المنزل.</p>
                        <p x-cloak x-show="geofenceError" x-ref="geofenceAlert" x-text="geofenceError" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-800" role="alert"></p>
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}" x-model="customerLatitude" @input="persistCustomer(); recalculate()" required>
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}" x-model="customerLongitude" @input="persistCustomer(); recalculate()" required>
                    </div>@endif
                </div>

                <!-- ملخص الدفع -->
                <div class="bg-gray-50 p-6 rounded-xl h-fit">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">ملخص المبلغ</h3>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>المجموع الفرعي:</span>
                            <span id="subtotal-display">{{ number_format($baseSubtotal, 2) }} ر.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span>الضريبة ({{ $restaurant->tax_percentage ?? 0 }}٪):</span>
                            <span id="tax-display">{{ number_format($baseTax, 2) }} ر.س</span>
                        </div>
                        <div class="flex justify-between" x-show="deliveryFee > 0">
                            <span>رسوم التوصيل:</span>
                            <span id="delivery-display" x-text="deliveryFeeDisplay">{{ number_format($deliveryFee, 2) }} ر.س</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600" x-cloak x-show="distanceKm !== null">
                            <span>المسافة إلى المطعم:</span>
                            <span x-text="formatDistance()"></span>
                        </div>
                        
                        <!-- قسم العروض التلقائية -->
                        @if($activeOffer)
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between text-green-600 font-bold">
                                    <span>🎉 عرض خاص ({{ $activeOffer->title }}):</span>
                                    <span id="offer-discount-display">- {{ number_format($autoDiscount, 2) }} ر.س</span>
                                </div>
                            </div>
                        @endif
                        
                        <!-- قسم خصم الكوبون -->
                        <div id="coupon-section" class="hidden border-t pt-2 mt-2">
                            <div class="flex justify-between text-blue-600 font-bold">
                                <span id="coupon-label">🎫 كوبون الخصم:</span>
                                <span id="coupon-amount">- 0.00 ر.س</span>
                            </div>
                            <button type="button" onclick="removeCoupon()" class="text-xs text-red-500 hover:underline mt-1">
                                إزالة الكوبون
                            </button>
                        </div>
                        
                        <div class="flex justify-between text-xl font-black text-primary mt-4 pt-4 border-t">
                            <span>الإجمالي:</span>
                            <span id="final-total" x-text="totalDisplay">{{ number_format($initialFinalTotal, 2) }} ر.س</span>
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
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="transfer" class="text-primary focus:ring-primary">
                        <span>تحويل بنكي 🏦</span>
                    </label>
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

            <button type="submit" class="w-full btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition">تأكيد الطلب ✅</button>
        </form>
        <div id="checkout-processing" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" role="status" aria-live="assertive" aria-hidden="true">
            <div class="flex items-center gap-4 rounded-xl bg-white px-6 py-5 text-gray-800 shadow-xl">
                <span class="inline-block h-7 w-7 animate-spin rounded-full border-4 border-current border-r-transparent" aria-hidden="true"></span>
                <span>جارٍ معالجة طلبك...</span>
            </div>
        </div>
    </div>
</section>

@if(!$table)<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>@endif

<script>
    document.getElementById('checkout-form').addEventListener('submit', function () {
        if (this.dataset.outOfRange === 'true') {
            return;
        }

        const overlay = document.getElementById('checkout-processing');
        const submitButton = this.querySelector('[type="submit"]');

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.setAttribute('aria-hidden', 'false');
        submitButton.disabled = true;
        submitButton.textContent = 'جارٍ معالجة الطلب...';
    });

    @if(!$table)
    const defaultLocation = [
        Number(@js($restaurant->latitude)) || 33.5138,
        Number(@js($restaurant->longitude)) || 36.2765
    ];
    const checkoutMap = L.map('checkout-map').setView(defaultLocation, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(checkoutMap);
    let checkoutMarker;
    let hasRestoredCheckoutLocation = document.cookie.includes('customer_lat=')
        && document.cookie.includes('customer_lng=');

    function setCheckoutLocation(latitude, longitude) {
        document.getElementById('latitude').value = latitude;
        document.getElementById('longitude').value = longitude;
        document.getElementById('latitude').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('longitude').dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById('map-status').textContent = `تم تحديد الموقع: ${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}`;
        checkoutMarker = checkoutMarker || L.marker([latitude, longitude]).addTo(checkoutMap);
        checkoutMarker.setLatLng([latitude, longitude]);
    }

    checkoutMap.on('click', (event) => setCheckoutLocation(event.latlng.lat, event.latlng.lng));
    window.addEventListener('checkout-customer-cookies-restored', (event) => {
        const { latitude, longitude } = event.detail;
        const restoredLatitude = Number(latitude);
        const restoredLongitude = Number(longitude);
        if (latitude !== '' && longitude !== ''
            && Number.isFinite(restoredLatitude) && Number.isFinite(restoredLongitude)
            && Math.abs(restoredLatitude) <= 90 && Math.abs(restoredLongitude) <= 180) {
            hasRestoredCheckoutLocation = true;
            checkoutMap.setView([restoredLatitude, restoredLongitude], 16);
            setCheckoutLocation(restoredLatitude, restoredLongitude);
        }
    });
    if (!document.getElementById('latitude').value && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            if (hasRestoredCheckoutLocation) return;
            checkoutMap.setView([position.coords.latitude, position.coords.longitude], 16);
            setCheckoutLocation(position.coords.latitude, position.coords.longitude);
        });
    }
    @endif

    let appliedCouponId = null;
    let couponDiscount = 0;
    
    const subtotal = {{ $baseSubtotal }};

    async function applyCouponCode() {
        const code = document.getElementById('coupon-code-input').value.trim();
        const messageDiv = document.getElementById('coupon-message');
        const applyBtn = document.getElementById('apply-coupon-btn');
        const customerPhone = document.getElementById('customer-phone').value;

        if (!code) {
            showCouponMessage('الرجاء إدخال كود الخصم', 'error');
            return;
        }

        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="animate-spin">⏳</span>';

        try {
            const response = await axios.post('{{ route("coupon.apply", $restaurant->slug) }}', {
                coupon_code: code,
                subtotal: subtotal,
                customer_phone: customerPhone
            });

            if (response.data.success) {
                appliedCouponId = response.data.coupon_id;
                couponDiscount = response.data.discount_amount;

                document.getElementById('selected-coupon-id').value = response.data.coupon_id;
                document.getElementById('selected-coupon-code').value = response.data.coupon_code;
                
                document.getElementById('coupon-section').classList.remove('hidden');
                document.getElementById('coupon-label').textContent = '🎫 كوبون الخصم (' + response.data.coupon_code + '):';
                document.getElementById('coupon-amount').textContent = '- ' + couponDiscount.toFixed(2) + ' ر.س';
                
                updateFinalTotal();

                showCouponMessage(response.data.message, 'success');
            } else {
                showCouponMessage(response.data.message, 'error');
            }
        } catch (error) {
            showCouponMessage(error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى', 'error');
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = 'تطبيق';
        }
    }

    function removeCoupon() {
        appliedCouponId = null;
        couponDiscount = 0;
        
        document.getElementById('selected-coupon-id').value = '';
        document.getElementById('selected-coupon-code').value = '';
        document.getElementById('coupon-section').classList.add('hidden');
        document.getElementById('coupon-code-input').value = '';
        document.getElementById('coupon-message').classList.add('hidden');
        
        updateFinalTotal();
    }

    function updateFinalTotal() {
        window.checkoutPricingState?.setCouponDiscount(couponDiscount);
    }

    function showCouponMessage(message, type) {
        const messageDiv = document.getElementById('coupon-message');
        messageDiv.textContent = message;
        messageDiv.className = 'text-sm mt-2 ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
        messageDiv.classList.remove('hidden');
    }
</script>
@endsection