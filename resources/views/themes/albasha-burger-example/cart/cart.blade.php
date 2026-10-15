@extends('themes.albasha-burger.layout')

@section('content')

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-black text-center mb-12 text-gray-800">سلة التسوق 🛒</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(empty($products))
            <!-- حالة السلة الفارغة -->
            <div class="text-center py-20 bg-white rounded-2xl shadow-lg">
                <div class="text-8xl mb-6">🛒</div>
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
                    taxPercentage: @js((float) ($restaurant->tax_percentage ?? 0)),
                    deliveryFee: @js((float) $deliveryFee),
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
                                    <div class="w-full h-full bg-gray-200 rounded-xl flex items-center justify-center text-2xl">🍽️</div>
                                @endif
                            </div>

                            <!-- تفاصيل المنتج -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-800">{{ $item['product']->name }}</h3>
                                @if($item['variant_name'])
                                    <p class="text-sm text-gray-500">{{ $item['variant_name'] }}</p>
                                @endif
                                <p class="text-primary font-bold mt-1">{{ number_format($item['price'], 2) }} ل.س</p>
                            </div>

                            <!-- التحكم بالكمية -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="changeQuantity(@js($item['cart_key']), -1)" aria-label="تقليل كمية {{ $item['product']->name }}"
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">-</button>

                                    <span class="inline-flex w-8 min-w-8 shrink-0 items-center justify-center text-center font-bold text-gray-800 tabular-nums" aria-live="polite" x-text="quantities[@js($item['cart_key'])] ?? @js((int) $item['qty'])">{{ $item['qty'] }}</span>

                                    <button type="button" @click="changeQuantity(@js($item['cart_key']), 1)" aria-label="زيادة كمية {{ $item['product']->name }}"
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">+</button>
                                </div>

                                <form action="{{ route('cart.remove' , $restaurant->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2">
                                        🗑️
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
                            <div class="flex items-center justify-between gap-4 text-gray-600">
                                <span>المجموع الفرعي</span>
                                <span class="shrink-0 whitespace-nowrap text-right tabular-nums" dir="ltr" x-text="formatMoney(Number.isFinite(subtotal) ? subtotal : @js((float) $total)) + ' ' + currencySymbol">{{ number_format($total, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-gray-600">
                                <span>التوصيل</span>
                                <span dir="ltr" class="shrink-0 whitespace-nowrap text-right tabular-nums {{ $deliveryFee > 0 ? 'font-bold' : 'text-green-600 font-bold' }}">
                                    {{ $deliveryFee > 0 ? number_format($deliveryFee, 2).' '.($restaurant->currency_symbol ?? 'ل.س') : 'مجاني' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-gray-600">
                                <span>الضريبة ({{ $restaurant->tax_percentage ?? 0 }}٪)</span>
                                <span class="shrink-0 whitespace-nowrap text-right tabular-nums" dir="ltr" x-text="formatMoney(Number.isFinite(taxAmount) ? taxAmount : @js((float) ($total * (($restaurant->tax_percentage ?? 0) / 100)))) + ' ' + currencySymbol">{{ number_format($total * (($restaurant->tax_percentage ?? 0) / 100), 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t pt-4 text-xl font-black text-gray-800">
                                <span>الإجمالي</span>
                                <span class="shrink-0 whitespace-nowrap text-right text-primary tabular-nums" dir="ltr" x-text="formatMoney(Number.isFinite(grandTotal) ? grandTotal : @js((float) ($total * (1 + (($restaurant->tax_percentage ?? 0) / 100)) + $deliveryFee))) + ' ' + currencySymbol">{{ number_format($total * (1 + (($restaurant->tax_percentage ?? 0) / 100)) + $deliveryFee, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                            </div>
                            <p class="text-sm text-red-600" role="alert" aria-live="polite" x-show="errorMessage" x-text="errorMessage"></p>
                            <p class="text-sm text-gray-500" aria-live="polite" x-show="isSaving">جارٍ حفظ الكميات...</p>
                        </div>

                        @if($restaurant->canAcceptOrders())
                            <a href="{{ route('checkout' , $restaurant->slug) }}"
                               class="w-full btn-primary py-4 rounded-xl font-bold text-center block hover:shadow-lg transition">
                                إتمام الطلب
                            </a>
                        @else
                            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-gray-300 py-4 text-center font-bold text-gray-600">
                                إتمام الطلب
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        @endif
    </div>
</section>

@endsection