@extends('themes.albasha-burger.layout')

@section('content')

<!-- Hero Section -->
<section class="relative py-20 overflow-hidden">
    @if($restaurant->cover_image)
        <img src="{{ media_url($restaurant->cover_image) }}"
             alt="{{ $restaurant->name }}" 
             class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-black opacity-50"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600"></div>
    @endif
    
    <div class="relative z-10 text-center text-white px-4">
        <h1 class="text-5xl md:text-6xl font-black mb-4">قائمة الطعام</h1>
        <p class="text-xl md:text-2xl">اكتشف أشهى الأطباق لدينا</p>
    </div>
</section>

@if($table)
    <div class="mx-auto mt-6 max-w-5xl rounded-2xl border border-orange-200 bg-orange-50 px-5 py-4 text-center text-orange-900">
        أنت الآن تطلب من <strong>الطاولة {{ $table->number }}</strong>. بعد التأكيد سيصل الطلب مباشرة إلى المطبخ.
    </div>
@endif

<!-- Menu Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        
        @if($categories->isEmpty())
            <!-- حالة عدم وجود تصنيفات -->
            <div class="text-center py-20">
                <div class="text-6xl mb-4">🍽️</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">القائمة قيد التحضير</h2>
                <p class="text-gray-600">نعمل على إضافة أطباق جديدة قريباً</p>
            </div>
        @else
            <!-- عرض التصنيفات والمنتجات -->
            @foreach($categories as $category)
                @if($category->products->isNotEmpty())
                    <div class="mb-16">
                        <!-- عنوان التصنيف -->
                        <div class="mb-8">
                            <div class="flex items-center gap-4 mb-2">
                                <div class="w-12 h-1 bg-primary rounded"></div>
                                <span class="text-primary font-bold">الفئة</span>
                            </div>
                            <h2 class="text-4xl font-black text-gray-800">{{ $category->name }}</h2>
                            @if($category->description)
                                <p class="text-gray-600 mt-2">{{ $category->description }}</p>
                            @endif
                        </div>

                        <!-- شبكة المنتجات -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($category->products as $product)
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                                    
                                    <!-- صورة المنتج -->
                                    <a href="{{ route('product.show', [$restaurant->slug, $product->id]) }}"
                                       aria-label="عرض تفاصيل {{ $product->name }}"
                                       class="block">
                                        <div class="relative h-56 overflow-hidden">
                                            @if($product->image)
                                                <img src="{{ media_url($product->image) }}"
                                                     alt="{{ $product->name }}"
                                                     loading="lazy" decoding="async"
                                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                            @else
                                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                                    <span class="text-6xl">🍽️</span>
                                                </div>
                                            @endif

                                            <!-- شارات المنتج -->
                                            <div class="absolute top-4 right-4 flex flex-col gap-2">
                                                @if($product->old_price && $product->old_price > $product->price)
                                                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        خصم {{ round((($product->old_price - $product->price) / $product->old_price) * 100) }}%
                                                    </span>
                                                @endif

                                                @if($product->is_featured)
                                                    <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        مميز
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>

                                    <!-- محتوى البطاقة -->
                                    <div class="p-6">
                                        <a href="{{ route('product.show', [$restaurant->slug, $product->id]) }}"
                                           class="block rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $product->name }}</h3>

                                            @if($product->description)
                                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                                    {{ $product->description }}
                                                </p>
                                            @endif
                                        </a>

                                        <!-- السعر -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-3xl font-black text-primary">
                                                    {{ number_format($product->price, 2) }}
                                                </span>
                                                <span class="text-gray-600 text-sm">ل.س</span>
                                            </div>
                                            
                                            @if($product->old_price && $product->old_price > $product->price)
                                                <span class="text-gray-400 line-through text-lg">
                                                    {{ number_format($product->old_price, 2) }} ل.س
                                                </span>
                                            @endif
                                        </div>

                                        <!-- زر إضافة للسلة -->
                                        <form action="{{ route('cart.add' , $restaurant->slug) }}" method="POST" class="menu-add-form">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" @disabled(! $restaurant->canAcceptOrders()) aria-disabled="{{ $restaurant->canAcceptOrders() ? 'false' : 'true' }}" class="menu-add-button w-full btn-primary py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:shadow-lg transition disabled:cursor-not-allowed disabled:opacity-50">
                                                <span>🛒</span>
                                                <span>أضف للسلة</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

    </div>
</section>

<!-- Call to Action -->
<section class="py-16 pb-32 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-black mb-4">جاهز للطلب؟</h2>
        <p class="text-xl mb-8">أضف أطباقك المفضلة للسلة وأكمل طلبك الآن</p>
        <a href="{{ route('cart.index' , $restaurant->slug) }}" 
           class="inline-block bg-white text-primary px-8 py-4 rounded-full text-lg font-bold hover:shadow-2xl transition">
            عرض السلة 🛒
        </a>
    </div>
</section>

<div id="order-summary" class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white/95 p-3 shadow-[0_-8px_25px_rgba(0,0,0,0.12)] backdrop-blur">
    <div class="mx-auto max-w-5xl px-2">
        <button type="button" id="toggle-order-summary" class="flex w-full items-center justify-between gap-3 text-right">
            <div>
                <p id="cart-count" class="text-xs text-gray-500">{{ $cartCount }} وجبة في الطلب</p>
                <p id="cart-total" class="text-lg font-black text-gray-900">{{ number_format($cartTotal, 2) }} ل.س</p>
            </div>
            <span id="order-summary-toggle-label" class="text-sm font-bold text-primary">عرض الطلب</span>
        </button>

        <div id="order-summary-content" hidden class="mt-3 border-t border-gray-100 pt-3">
            <div id="cart-items-list">
            @forelse($cartItems as $item)
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-gray-800">{{ $item['product']->name }}@if($item['variant_name']) ({{ $item['variant_name'] }})@endif</p>
                        <p class="text-xs text-gray-500">{{ $item['quantity'] }} × {{ number_format($item['unit_price'], 2) }} ل.س</p>
                    </div>
                    <p class="font-bold text-primary">{{ number_format($item['subtotal'], 2) }} ل.س</p>
                </div>
            @empty
                <p class="py-2 text-center text-sm text-gray-500">لم تتم إضافة وجبات بعد</p>
            @endforelse
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <p class="font-black text-gray-900">الإجمالي مع الضريبة والتوصيل: <span id="cart-total-detail">{{ number_format($cartTotal, 2) }}</span> ل.س</p>
                @if($restaurant->canAcceptOrders())
                    <a href="{{ route('checkout', $restaurant->slug) }}" class="btn-primary flex min-h-11 items-center justify-center rounded-xl px-5 text-center font-bold">
                        طلب الوجبات
                    </a>
                @else
                    <button type="button" disabled class="flex min-h-11 cursor-not-allowed items-center justify-center rounded-xl bg-gray-300 px-5 text-center font-bold text-gray-600">
                        طلب الوجبات
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('toggle-order-summary').addEventListener('click', () => {
        const content = document.getElementById('order-summary-content');
        const isOpen = !content.hidden;
        content.hidden = isOpen;
        document.getElementById('order-summary-toggle-label').textContent = isOpen ? 'عرض الطلب' : 'إخفاء الطلب';
    });
</script>

@endsection