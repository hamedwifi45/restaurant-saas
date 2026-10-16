@extends('themes.albasha-burger.layout')

@section('content')

<!-- Hero Section -->
<section class="relative py-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary to-orange-600"></div>
    
    <div class="relative z-10 text-center text-white px-4">
        <h1 class="text-5xl md:text-6xl font-black mb-4">العروض الحالية</h1>
        <p class="text-xl md:text-2xl">اكتشف أفضل العروض والخصومات</p>
    </div>
</section>

<!-- العروض -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-6xl">
        
        @if($offers->isEmpty())
            <!-- حالة عدم وجود عروض -->
            <div class="text-center py-20 bg-white rounded-2xl shadow-lg">
                <i class="fa-solid fa-tags mb-6 text-8xl text-primary" aria-hidden="true"></i>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">لا توجد عروض حالياً</h2>
                <p class="text-gray-600 mb-8">تابعنا لمعرفة أحدث العروض والخصومات</p>
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}" 
                   class="btn-primary px-8 py-3 rounded-full font-bold inline-block">
                    تصفح القائمة
                </a>
            </div>
        @else
            <!-- شبكة العروض -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($offers as $offer)
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                        
                        <!-- صورة العرض -->
                        <div class="relative h-48 overflow-hidden">
                            @if($offer->image)
                                <img src="{{ media_url($offer->image) }}"
                                     alt="{{ $offer->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center">
                                    <i class="fa-solid fa-fire text-6xl text-white" aria-hidden="true"></i>
                                </div>
                            @endif

                            <!-- شارة الخصم -->
                            <div class="absolute top-4 right-4">
                                <span class="bg-red-500 text-white px-4 py-2 rounded-full text-lg font-black shadow-lg">
                                    {{ $offer->getDiscountLabel() }}
                                </span>
                            </div>

                            <!-- شريط الفترة -->
                            @if($offer->ends_at)
                                <div class="absolute bottom-0 left-0 right-0 bg-black opacity-70 text-white text-xs py-2 px-4">
                                    ⏰ ينتهي: {{ $offer->ends_at->format('Y-m-d') }}
                                </div>
                            @endif
                        </div>

                        <!-- محتوى البطاقة -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $offer->title }}</h3>
                            
                            @if($offer->description)
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    {{ $offer->description }}
                                </p>
                            @endif

                            <!-- الشروط -->
                            <div class="space-y-2 mb-4">
                                @if($offer->min_order_amount > 0)
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <i class="fa-solid fa-coins text-primary" aria-hidden="true"></i>
                                        <span>الحد الأدنى للطلب: {{ number_format($offer->min_order_amount, 2) }} ل.س</span>
                                    </div>
                                @endif

                                @if($offer->max_uses)
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <i class="fa-solid fa-bullseye text-primary" aria-hidden="true"></i>
                                        <span>متبقي: {{ $offer->max_uses - $offer->used_count }} استخدام</span>
                                    </div>
                                @endif

                                @if(!$offer->apply_to_all)
                                    <div class="flex items-center gap-2 text-sm text-primary font-bold">
                                        <i class="fa-solid fa-star text-primary" aria-hidden="true"></i>
                                        <span>منتجات محددة فقط</span>
                                    </div>
                                @endif
                            </div>

                            <!-- زر اطلب الآن -->
                            <a href="{{ route('restaurant.menu', $restaurant->slug) }}" 
                               class="block w-full btn-primary py-3 rounded-xl font-bold text-center hover:shadow-lg transition">
                                اطلب الآن <i class="fa-solid fa-bag-shopping mr-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>

<!-- Call to Action -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-4xl font-black mb-4">لا تفوت العروض القادمة!</h2>
        <p class="text-xl mb-8">تابعنا لمعرفة أحدث العروض والخصومات الحصرية</p>
        <a href="{{ route('restaurant.home', $restaurant->slug) }}" 
           class="inline-block bg-white text-primary px-8 py-4 rounded-full text-lg font-bold hover:shadow-2xl transition">
            العودة للرئيسية
        </a>
    </div>
</section>

@endsection