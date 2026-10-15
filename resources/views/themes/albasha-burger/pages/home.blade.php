@extends('themes.albasha-burger.layout')

@section('content')

    <!-- Hero Section -->
    @if(theme_setting('show_hero_banner', true))
        <section class="relative h-screen flex items-center justify-center overflow-hidden">
            @if($restaurant->cover_image)
                <img src="{{ media_url($restaurant->cover_image) }}" alt="{{ $restaurant->name }}"
                    class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-black opacity-50"></div>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600"></div>
            @endif

            <div class="relative z-10 text-center text-white px-4">
                <div class="mb-4">
                    <span class="{{ $restaurant->canAcceptOrders() ? 'bg-white text-primary' : 'bg-amber-100 text-amber-900' }} px-4 py-2 rounded-full text-sm font-bold shadow-sm">
                        {{ $restaurant->canAcceptOrders() ? 'متاح الآن للطلب' : 'الطلبات متوقفة حالياً' }}
                    </span>
                </div>

                <h1 class="text-5xl md:text-7xl font-black mb-4">
                    {{ theme_setting('hero_title', $restaurant->name) }}
                </h1>

                <p class="text-2xl md:text-3xl mb-8 font-medium">
                    {{ theme_setting('hero_subtitle', $restaurant->description ?? 'طعم لا يُقاوم') }}
                </p>

                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="{{ route('restaurant.menu', $restaurant->slug) }}"
                        class="btn-primary px-8 py-4 rounded-full text-lg font-bold shadow-lg">
                        اطلب الآن
                    </a>
                    <a href="#about"
                        class="border-2 border-white px-8 py-4 rounded-full text-lg font-bold hover:bg-white hover:text-black transition">
                        تعرف علينا
                    </a>
                </div>

                <!-- Stats -->
                <div class="flex gap-8 justify-center mt-12 flex-wrap">
                    <div class="text-center">
                        <div class="text-4xl font-black text-primary">+٥٠٠</div>
                        <div class="text-sm">عميل سعيد</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-primary">+٥٠</div>
                        <div class="text-sm">وجبة متنوعة</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-primary">{{ $restaurant->estimated_delivery_time ?? '٣٠' }} د</div>
                        <div class="text-sm">متوسط التوصيل</div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- بانر العروض -->
    @if($restaurant->activeOffers()->count() > 0)
        <section class="py-12 bg-gradient-to-r from-red-500 to-orange-500 text-white">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="text-5xl">🔥</div>
                        <div>
                            <h3 class="text-2xl font-black">عروض حصرية!</h3>
                            <p class="text-white text-opacity-90">اكتشف أحدث العروض والخصومات</p>
                        </div>
                    </div>
                    <a href="{{ route('offers.index', $restaurant->slug) }}" 
                       class="bg-white text-primary px-6 py-3 rounded-full font-bold hover:shadow-xl transition">
                        عرض العروض ←
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Browse Categories Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- العنوان الرئيسي -->
        <div class="text-center mb-14">
            <span class="inline-block px-4 py-1.5 bg-primary/10 text-primary font-bold text-sm rounded-full mb-3 tracking-wide">
                ✨ اختر ما يناسبك
            </span>
            <h2 class="text-3xl md:text-5xl mb-8 font-black text-gray-900 tracking-tight">
                تصفح الفئات
            </h2>
        </div>

        @if($categories->isEmpty())
            <div class="max-w-md mx-auto text-center py-12 bg-white rounded-3xl shadow-sm border border-gray-100">
                <span class="text-5xl mb-4 block">🍽️</span>
                <p class="text-gray-500 font-medium">لا توجد فئات متاحة حالياً</p>
            </div>
        @else
            <!-- شبكة البطاقات العصرية -->
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                @foreach($categories as $category)
                    @php
                        // تحديد مسار الصورة المناسب
                        $categoryImage = null;
                        if (!empty($category->image)) {
                            $categoryImage = asset('storage/' . $category->image);
                        } elseif (!empty($category->image_url)) {
                            $categoryImage = $category->image_url;
                        }
                    @endphp

                    <a href="{{ route('restaurant.menu', ['slug' => $restaurant->slug, 'category' => $category->slug]) }}"
                       class="group relative h-64 md:h-72 w-full rounded-3xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100 flex flex-col justify-end">
                        
                        <!-- خلفية الصورة بالكامل -->
                        @if($categoryImage)
                            <img src="{{ $categoryImage }}" 
                                 alt="{{ $category->name }}" 
                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
                        @else
                            <!-- خلفية عصرية افتراضية في حال عدم وجود صورة -->
                            <div class="absolute inset-0 bg-gradient-to-br from-amber-500 via-orange-600 to-red-600 flex items-center justify-center">
                                <span class="text-6xl group-hover:scale-125 transition-transform duration-500">🍽️</span>
                            </div>
                        @endif

                        <!-- تدرج التعتيم المحسّن الداكن جداً من الأسفل لجعل النص واضحاً تماماً -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/50 to-transparent opacity-90 group-hover:opacity-95 transition-opacity duration-300"></div>

                        <!-- المحتوى والنص عبر البطاقة -->
                        <div class="relative z-10 p-5 text-right w-full flex flex-col justify-end">
                            <!-- بطاقة زجاجية شفافة احترافية خلف النص تضمن الوضوح التام -->
                            <div class="p-3 rounded-2xl bg-black/20 backdrop-blur-sm border border-white/10 group-hover:bg-black/40 transition-colors duration-300">
                                <h3 class="font-black text-lg md:text-xl text-white group-hover:text-amber-300 transition-colors duration-300 [text-shadow:_0_2px_4px_rgba(0,0,0,0.8)]">
                                    {{ $category->name }}
                                </h3>
                                
                                @if(isset($category->products_count))
                                    <span class="text-xs text-gray-200 mt-1 block font-medium">
                                        {{ $category->products_count }} صنف
                                    </span>
                                @endif

                            </div>
                        </div>

                        <!-- شارة تفاعلية عند تحريك الماوس أعلى البطاقة -->
                        <div class="absolute top-3 left-3 z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="w-8 h-8 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center text-white border border-white/20 shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

    <!-- Popular Items Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <span class="text-primary font-bold text-lg">القائمة</span>
                    <h2 class="text-4xl font-black text-gray-800 mt-2">الأصناف الأكثر شعبية</h2>
                </div>
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}"
                    class="btn-primary px-6 py-2 text-sm font-bold hidden sm:inline-block">
                    عرض الكل ➔
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse($restaurant->products()->where('is_available', true)->limit(4)->get() as $product)
                    <div class="{{ theme_setting('card_style', 'elevated') === 'flat' ? 'bg-white rounded-2xl border border-gray-200 overflow-hidden group hover:border-primary transition duration-300' : 'bg-white rounded-2xl shadow-lg overflow-hidden group hover:-translate-y-2 transition duration-300' }}">
                        <a href="{{ route('product.show', [$restaurant->slug, $product->slug]) }}"
                            aria-label="عرض تفاصيل {{ $product->name }}" class="block">
                            <div class="relative h-48 overflow-hidden">
                                @if($product->image)
                                    <img src="{{ media_url($product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center text-4xl">🍔</div>
                                @endif
                                <span
                                    class="absolute top-4 right-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                                    الأكثر طلباً
                                </span>
                            </div>
                        </a>
                        <div class="p-6">
                            <a href="{{ route('product.show', [$restaurant->slug, $product->id]) }}"
                                class="block rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $product->name }}</h3>
                                <p class="text-gray-500 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                            </a>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-black text-primary">{{ number_format($product->price, 2) }} ل.س</span>
                                <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" @disabled(! $restaurant->canAcceptOrders())
                                        class="bg-gray-100 hover:bg-primary hover:text-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center transition font-bold text-lg disabled:cursor-not-allowed disabled:opacity-50">
                                        +
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="col-span-4 text-center text-gray-500">لا توجد منتجات مميزة حالياً</p>
                @endforelse
            </div>

            <div class="mt-8 text-center sm:hidden">
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}"
                    class="btn-primary px-6 py-2 text-sm font-bold inline-block">
                    عرض كل القائمة
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-black text-center mb-12 text-gray-800">لماذا نحن</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">🚀</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">توصيل سريع</h3>
                    <p class="text-gray-600">نوصل طلبك في أقل من {{ $restaurant->estimated_delivery_time ?? '٣٠' }} دقيقة إلى باب منزلك</p>
                </div>

                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">🥬</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">طعام طازج</h3>
                    <p class="text-gray-600">نستخدم مكونات طازجة ١٠٠٪ يومياً لضمان الجودة</p>
                </div>

                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">📞</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">دعم ٢٤/٧</h3>
                    <p class="text-gray-600">فريقنا جاهز لخدمتك على مدار الساعة طوال الأسبوع</p>
                </div>

                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">💳</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">دفع سهل</h3>
                    <p class="text-gray-600">طرق دفع متعددة وآمنة تناسب جميع العملاء</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-primary font-bold text-lg">قصتنا</span>
                    <h2 class="text-4xl font-black mb-6 text-gray-800">شغفنا هو صنع أفضل طعام</h2>

                    <p class="text-lg text-gray-700 mb-4">
                        {{ $restaurant->description ?? 'منذ أكثر من عشر سنوات، بدأنا رحلتنا في عالم الطعام بشغف كبير وهدف واحد: تقديم أشهى الأطباق بمكونات طازجة وجودة لا تُضاهى.' }}
                    </p>

                    <p class="text-lg text-gray-700 mb-6">
                        نختار كل مكون بعناية فائقة، لنضمن لك تجربة طعام لا تُنسى في كل قضمة.
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <span class="text-primary text-2xl">✓</span>
                            <span class="font-medium">مكونات طازجة</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-primary text-2xl">✓</span>
                            <span class="font-medium">طهاة محترفون</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-primary text-2xl">✓</span>
                            <span class="font-medium">جودة عالية</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-primary text-2xl">✓</span>
                            <span class="font-medium">خدمة ممتازة</span>
                        </div>
                    </div>
                </div>

                @if($restaurant->cover_image)
                    <div>
                        <img src="{{ media_url($restaurant->cover_image) }}" alt="{{ $restaurant->name }}"
                            class="rounded-2xl shadow-2xl">
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-black text-center mb-12 text-gray-800">تواصل معنا</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                @if($restaurant->phone)
                    <div class="text-center p-6 bg-gray-50 rounded-xl">
                        <div class="text-5xl mb-4">📞</div>
                        <h3 class="font-bold mb-2 text-gray-800">اتصل بنا</h3>
                        <p class="text-gray-600">{{ $restaurant->phone }}</p>
                    </div>
                @endif

                @if($restaurant->email)
                    <div class="text-center p-6 bg-gray-50 rounded-xl">
                        <div class="text-5xl mb-4">📧</div>
                        <h3 class="font-bold mb-2 text-gray-800">البريد الإلكتروني</h3>
                        <p class="text-gray-600">{{ $restaurant->email }}</p>
                    </div>
                @endif

                @if($restaurant->address)
                    <div class="text-center p-6 bg-gray-50 rounded-xl">
                        <div class="text-5xl mb-4">📍</div>
                        <h3 class="font-bold mb-2 text-gray-800">العنوان</h3>
                        <p class="text-gray-600">{{ $restaurant->address }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

@endsection