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
                        <input type="tel" name="phone" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea name="address" rows="3" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    </div>
                </div>

                <!-- ملخص الدفع -->
                <div class="bg-gray-50 p-6 rounded-xl h-fit">
                    <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">ملخص المبلغ</h3>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>المجموع الفرعي:</span>
                            <span id="subtotal">{{ number_format($total / 1.15, 2) }} ر.س</span>
                        </div>
                        <div class="flex justify-between">
                            <span>الضريبة (١٥٪):</span>
                            <span id="tax">{{ number_format($total - ($total / 1.15), 2) }} ر.س</span>
                        </div>
                        
                        <!-- قسم الخصم (يظهر عند تطبيق العرض) -->
                        <div id="discount-section" class="hidden border-t pt-2 mt-2">
                            <div class="flex justify-between text-green-600 font-bold">
                                <span id="discount-label">الخصم:</span>
                                <span id="discount-amount">- 0.00 ر.س</span>
                            </div>
                            <button type="button" onclick="removeOffer()" class="text-xs text-red-500 hover:underline mt-1">
                                إزالة الخصم
                            </button>
                        </div>
                        
                        <div class="flex justify-between text-xl font-black text-primary mt-4 pt-4 border-t">
                            <span>الإجمالي:</span>
                            <span id="final-total">{{ number_format($total, 2) }} ر.س</span>
                        </div>
                    </div>

                    <!-- حقل كود الخصم -->
                    <div class="mt-6 pt-6 border-t">
                        <label class="block text-sm font-bold text-gray-700 mb-2">هل لديك كود خصم؟</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                id="offer-code-input"
                                placeholder="أدخل كود الخصم"
                                class="flex-1 border-gray-300 rounded-lg focus:ring-primary focus:border-primary uppercase"
                            >
                            <button 
                                type="button"
                                onclick="applyOfferCode()"
                                id="apply-offer-btn"
                                class="btn-primary px-4 py-2 rounded-lg font-bold hover:shadow-lg transition"
                            >
                                تطبيق
                            </button>
                        </div>
                        <p id="offer-message" class="text-sm mt-2 hidden"></p>
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

            <!-- حقل مخفي للعرض المختار -->
            <input type="hidden" name="offer_id" id="selected-offer-id" value="">

            <button type="submit" class="w-full btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition">تأكيد الطلب ✅</button>
        </form>
    </div>
</section>

<script>
    let appliedOfferId = null;
    let discountAmount = 0;
    const subtotal = {{ $total / 1.15 }};
    const tax = {{ $total - ($total / 1.15) }};

    // تطبيق كود الخصم
    async function applyOfferCode() {
        const code = document.getElementById('offer-code-input').value.trim();
        const messageDiv = document.getElementById('offer-message');
        const applyBtn = document.getElementById('apply-offer-btn');

        if (!code) {
            showMessage('الرجاء إدخال كود الخصم', 'error');
            return;
        }

        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="animate-spin">⏳</span>';

        try {
            const response = await axios.post('{{ route("offer.apply", $restaurant->slug) }}', {
                offer_code: code,
                subtotal: subtotal
            });

            if (response.data.success) {
                appliedOfferId = response.data.offer_id;
                discountAmount = response.data.discount_amount;

                document.getElementById('selected-offer-id').value = response.data.offer_id;
                
                // عرض الخصم
                document.getElementById('discount-section').classList.remove('hidden');
                document.getElementById('discount-label').textContent = response.data.discount_label + ':';
                document.getElementById('discount-amount').textContent = '- ' + discountAmount.toFixed(2) + ' ر.س';
                
                // تحديث الإجمالي
                const finalTotal = subtotal + tax - discountAmount;
                document.getElementById('final-total').textContent = finalTotal.toFixed(2) + ' ر.س';

                showMessage(response.data.message, 'success');
            } else {
                showMessage(response.data.message, 'error');
            }
        } catch (error) {
            showMessage(error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى', 'error');
        } finally {
            applyBtn.disabled = false;
            applyBtn.innerHTML = 'تطبيق';
        }
    }

    // إزالة الخصم
    function removeOffer() {
        appliedOfferId = null;
        discountAmount = 0;
        
        document.getElementById('selected-offer-id').value = '';
        document.getElementById('discount-section').classList.add('hidden');
        document.getElementById('offer-code-input').value = '';
        document.getElementById('offer-message').classList.add('hidden');
        
        // إعادة الإجمالي الأصلي
        const finalTotal = subtotal + tax;
        document.getElementById('final-total').textContent = finalTotal.toFixed(2) + ' ر.س';
    }

    // عرض الرسائل
    function showMessage(message, type) {
        const messageDiv = document.getElementById('offer-message');
        messageDiv.textContent = message;
        messageDiv.className = 'text-sm mt-2 ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
        messageDiv.classList.remove('hidden');
    }
</script>
@endsection