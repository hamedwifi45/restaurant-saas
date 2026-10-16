@extends('themes.albasha-burger.layout')

@section('content')

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="mb-12 text-center text-4xl font-black text-gray-800">سلة التسوق <i class="fa-solid fa-bag-shopping mr-2 text-primary" aria-hidden="true"></i></h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(empty($products))
            <!-- حالة السلة الفارغة -->
            <div class="text-center py-20 bg-white rounded-2xl shadow-lg">
                <i class="fa-solid fa-bag-shopping mb-6 text-8xl text-gray-300" aria-hidden="true"></i>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">السلة فارغة</h2>
                <p class="text-gray-600 mb-8">ابدأ بإضافة منتجات لذيذة إلى سلتك</p>
                <a href="{{ route('restaurant.menu', $restaurant->slug ?? 'abboudi-mongolian') }}" 
                   class="btn-primary px-8 py-3 rounded-full font-bold inline-block">
                    تصفح القائمة
                </a>
            </div>
        @else
            <div
                class="grid grid-cols-1 lg:grid-cols-3 gap-8"
                x-data="cartQuantityManager({
                    items: @js(collect($products)->mapWithKeys(fn ($item) => [(string) $item['cart_key'] => ['quantity' => (int) $item['qty'], 'price' => (float) $item['price']]])->all()),
                    endpoint: @js(route('cart.update', $restaurant->slug)),
                    csrfToken: @js(csrf_token()),
                    currencySymbol: @js($restaurant->currency_symbol ?? 'ل.س')
                })"
                :aria-busy="isSaving"
            >
                
                <!-- قائمة المنتجات -->
                <div class="lg:col-span-2 space-y-4">
                    @foreach($products as $item)
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center gap-6">
                            <!-- صورة المنتج -->
                            <div class="w-24 h-24 flex-shrink-0">
                                @if($item['product']->image)
                                    <img src="{{ media_url($item['product']->image) }}"
                                         alt="{{ $item['product']->name }}" loading="lazy" decoding="async"
                                         class="w-full h-full object-cover rounded-xl">
                                @else
                                    <div class="flex h-full w-full items-center justify-center rounded-xl bg-gray-200 text-2xl text-gray-400"><i class="fa-solid fa-utensils" aria-hidden="true"></i></div>
                                @endif
                            </div>

                            <!-- تفاصيل المنتج -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-800">{{ $item['product']->name }}</h3>
                                @if(!empty($item['variant_name']))
                                    <p class="text-sm text-gray-500 font-semibold">{{ $item['variant_name'] }}</p>
                                @endif
                                <p class="text-primary font-bold mt-1">{{ number_format($item['price'], 0) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</p>
                            </div>

                            <!-- التحكم بالكمية -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="changeQuantity('{{ $item['cart_key'] }}', -1)" aria-label="تقليل كمية {{ $item['product']->name }}"
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">-</button>

                                    <span class="inline-flex w-8 min-w-8 shrink-0 items-center justify-center text-center font-bold text-gray-800 tabular-nums text-lg"
                                          x-text="quantities['{{ $item['cart_key'] }}'] ?? {{ (int) $item['qty'] }}">
                                        {{ $item['qty'] }}
                                    </span>

                                    <button type="button" @click="changeQuantity('{{ $item['cart_key'] }}', 1)" aria-label="زيادة كمية {{ $item['product']->name }}"
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">+</button>
                                </div>

                                <form action="{{ route('cart.remove' , $restaurant->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ملخص الطلب -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg sticky top-24">
                        <h3 class="text-2xl font-bold mb-6 text-gray-800">ملخص الطلب</h3>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center justify-between gap-4 border-b pb-4 text-xl font-black text-gray-800">
                                <span>مجموع الطلبات</span>
                                <span class="text-2xl font-black text-primary">
                                    <span x-text="formatMoney(subtotal)">{{ number_format($total, 0) }}</span> {{ $restaurant->currency_symbol ?? 'ل.س' }}
                                </span>
                            </div>

                            <!-- تنويه ذكي وواضح للزبون -->
                            <div class="text-xs text-gray-600 bg-blue-50 p-3 rounded-xl border border-blue-100 flex items-start gap-2">
                                <i class="fa-solid fa-location-dot mt-0.5 text-primary" aria-hidden="true"></i>
                                <span>تُحسب أجرة التوصيل في الخطوة التالية بناءً على موقعك وبعدك عن المطعم.</span>
                            </div>

                            <p class="text-sm text-red-600" role="alert" aria-live="polite" x-show="errorMessage" x-text="errorMessage"></p>
                            <p class="text-sm text-gray-500" aria-live="polite" x-show="isSaving">جارٍ حفظ الكميات...</p>
                        </div>

                        @if($restaurant->canAcceptOrders())
                            <a href="{{ route('checkout' , $restaurant->slug) }}"
                               class="w-full btn-primary py-4 rounded-xl font-bold text-center block hover:shadow-lg transition">
                                متابعة لإنهاء الطلب
                            </a>
                        @else
                            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-gray-300 py-4 text-center font-bold text-gray-600">
                                المطعم مغلق حالياً
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        @endif
    </div>
</section>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cartQuantityManager', (config) => ({
            quantities: {},
            prices: {},
            subtotal: 0,
            isSaving: false,
            errorMessage: '',

            init() {
                if (config.items) {
                    Object.keys(config.items).forEach(key => {
                        this.quantities[key] = Number(config.items[key].quantity);
                        this.prices[key] = Number(config.items[key].price);
                    });
                }
                this.recalculate();
            },

            changeQuantity(cartKey, delta) {
                let keyStr = String(cartKey);
                let current = Number(this.quantities[keyStr] || 1);
                let next = current + delta;
                if (next < 1) return;

                this.quantities[keyStr] = next;
                this.recalculate();
                this.syncWithServer(cartKey, next);
            },

            recalculate() {
                let total = 0;
                Object.keys(this.quantities).forEach(key => {
                    total += (Number(this.quantities[key] || 0) * Number(this.prices[key] || 0));
                });
                this.subtotal = total;
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(amount || 0);
            },

            async syncWithServer(cartKey, quantity) {
                this.isSaving = true;
                this.errorMessage = '';

                let rawProductId = typeof cartKey === 'string' && cartKey.includes('_') 
                    ? cartKey.split('_')[0] 
                    : cartKey;

                try {
                    let response = await fetch(config.endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            cart_key: cartKey, 
                            product_id: isNaN(rawProductId) ? null : Number(rawProductId),
                            quantity: Number(quantity) 
                        })
                    });

                    if (!response.ok) {
                        let errData = await response.json().catch(() => ({}));
                        this.errorMessage = errData.message || 'حدث خطأ أثناء تحديث السلة';
                    }
                } catch (e) {
                    this.errorMessage = 'فشل الاتصال بالسيرفر';
                } finally {
                    this.isSaving = false;
                }
            }
        }));
    });
</script>

@endsection