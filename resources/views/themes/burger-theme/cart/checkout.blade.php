@extends("themes.burger-theme.layout")

@section('content')
<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-4xl font-black text-center mb-12 text-gray-800">إتمام الطلب 📝</h1>

        <form action="{{ route('checkout.store', $restaurant->slug) }}" method="POST" id="checkout-form" class="bg-white p-8 rounded-2xl shadow-lg">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- بيانات العميل -->
                <div class="space-y-4">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2">بيانات التوصيل</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الاسم الكامل</label>
                        <input type="text" name="name" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف</label>
                        <input type="tel" name="phone" id="customer-phone" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea name="address" rows="3" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    </div>
                </div>

                <!-- ملخص الدفع -->
                <div class="bg-gray-50 p-6 rounded-xl h-fit">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">ملخص المبلغ</h3>
                    
                    @php
                        $baseSubtotal = $total / 1.15;
                        $baseTax = $total - $baseSubtotal;
                        $deliveryFee = $restaurant->delivery_fee ?? 0;
                        
                        // حساب خصم العرض التلقائي إن وجد
                        $activeOffer = $restaurant->activeOffers()
                            ->where('min_order_amount', '<=', $baseSubtotal)
                            ->first();
                            
                        $autoDiscount = $activeOffer ? $activeOffer->getDiscountAmount($baseSubtotal) : 0;
                        
                        // الإجمالي الأولي (قبل تطبيق أي كوبون يدوي)
                        $initialFinalTotal = $baseSubtotal + $baseTax + $deliveryFee - $autoDiscount;
                    @endphp

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>المجموع الفرعي:</span>
                            <span id="subtotal-display">{{ number_format($baseSubtotal, 2) }} ر.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span>الضريبة (١٥٪):</span>
                            <span id="tax-display">{{ number_format($baseTax, 2) }} ر.س</span>
                        </div>
                        @if($deliveryFee > 0)
                        <div class="flex justify-between">
                            <span>رسوم التوصيل:</span>
                            <span id="delivery-display">{{ number_format($deliveryFee, 2) }} ر.س</span>
                        </div>
                        @endif
                        
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
                            <!-- تم تصحيح القيمة الأولية المعروضة -->
                            <span id="final-total">{{ number_format($initialFinalTotal, 2) }} ر.س</span>
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
    </div>
</section>

<script>
    let appliedCouponId = null;
    let couponDiscount = 0;
    
    // تعريف المتغيرات بدقة من PHP
    const subtotal = {{ $baseSubtotal }};
    const tax = {{ $baseTax }};
    const deliveryFee = {{ $deliveryFee }};
    const offerDiscount = {{ $autoDiscount }};

    // تطبيق كوبون الخصم
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
                
                // عرض الخصم
                document.getElementById('coupon-section').classList.remove('hidden');
                document.getElementById('coupon-label').textContent = '🎫 كوبون الخصم (' + response.data.coupon_code + '):';
                document.getElementById('coupon-amount').textContent = '- ' + couponDiscount.toFixed(2) + ' ر.س';
                
                // تحديث الإجمالي
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

    // إزالة الكوبون
    function removeCoupon() {
        appliedCouponId = null;
        couponDiscount = 0;
        
        document.getElementById('selected-coupon-id').value = '';
        document.getElementById('selected-coupon-code').value = '';
        document.getElementById('coupon-section').classList.add('hidden');
        document.getElementById('coupon-code-input').value = '';
        document.getElementById('coupon-message').classList.add('hidden');
        
        // إعادة حساب الإجمالي
        updateFinalTotal();
    }

    // ✅ تحديث الإجمالي النهائي (تم تصحيح المعادلة هنا)
    function updateFinalTotal() {
        // المعادلة الصحيحة: المجموع + الضريبة + التوصيل - خصم العرض - خصم الكوبون
        const finalTotal = subtotal + tax + deliveryFee - offerDiscount - couponDiscount;
        document.getElementById('final-total').textContent = finalTotal.toFixed(2) + ' ر.س';
    }

    // عرض رسائل الكوبون
    function showCouponMessage(message, type) {
        const messageDiv = document.getElementById('coupon-message');
        messageDiv.textContent = message;
        messageDiv.className = 'text-sm mt-2 ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
        messageDiv.classList.remove('hidden');
    }
</script>
@endsection