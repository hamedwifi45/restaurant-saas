@extends("themes.burger-theme.layout")

@section('content')

<!-- Hero Section -->
<section class="relative py-20 overflow-hidden">
    @if($restaurant->cover_image)
        <img src="{{ asset('storage/' . $restaurant->cover_image) }}" 
             alt="{{ $restaurant->name }}" 
             class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600"></div>
    @endif
    
    <div class="relative z-10 text-center text-white px-4">
        <h1 class="text-5xl md:text-6xl font-black mb-4">قائمة الطعام</h1>
        <p class="text-xl md:text-2xl">اكتشف أشهى الأطباق لدينا</p>
    </div>
</section>

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
                                    <div class="relative h-56 overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" 
                                                 alt="{{ $product->name }}" 
                                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        @else
                                            <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                                <span class="text-6xl">🍽️</span>
                                            </div>
                                        @endif

                                        <!-- شارات المنتج -->
                                        <div class="absolute top-4 right-4 flex flex-col gap-2">
                                            @if($product->old_price && $product->old_price > $product->price)
                                                @php
                                                    $discount = round((($product->old_price - $product->price) / $product->old_price) * 100);
                                                @endphp
                                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                    خصم {{ $discount }}%
                                                </span>
                                            @endif
                                            
                                            @if($product->is_featured)
                                                <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-bold">
                                                    مميز
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- محتوى البطاقة -->
                                    <div class="p-6">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $product->name }}</h3>
                                        
                                        @if($product->description)
                                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                                {{ $product->description }}
                                            </p>
                                        @endif

                                        <!-- السعر -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-3xl font-black text-primary">
                                                    {{ number_format($product->price, 2) }}
                                                </span>
                                                <span class="text-gray-600 text-sm">ر.س</span>
                                            </div>
                                            
                                            @if($product->old_price && $product->old_price > $product->price)
                                                <span class="text-gray-400 line-through text-lg">
                                                    {{ number_format($product->old_price, 2) }} ر.س
                                                </span>
                                            @endif
                                        </div>

                                        <!-- زر إضافة للسلة -->
                                        <form action="{{ route('cart.add') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="w-full btn-primary py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:shadow-lg transition">
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
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-black mb-4">جاهز للطلب؟</h2>
        <p class="text-xl mb-8">أضف أطباقك المفضلة للسلة وأكمل طلبك الآن</p>
        <a href="{{ route('cart.index') }}" 
           class="inline-block bg-white text-primary px-8 py-4 rounded-full text-lg font-bold hover:shadow-2xl transition">
            عرض السلة 🛒
        </a>
    </div>
</section>

@endsection