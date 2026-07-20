@extends("themes.burger-theme.layout")

@section('content')

    <!-- Hero Section -->
    <section class="relative h-screen flex items-center justify-center overflow-hidden">
        @if($restaurant->cover_image)
            <img src="{{ asset('storage/' . $restaurant->cover_image) }}" alt="{{ $restaurant->name }}"
                class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600"></div>
        @endif

        <div class="relative z-10 text-center text-white px-4">
            <div class="mb-4">
                <span class="bg-white text-primary px-4 py-2 rounded-full text-sm font-bold">
                    متاح الآن للطلب
                </span>
            </div>

            <h1 class="text-5xl md:text-7xl font-black mb-4">{{ $restaurant->name }}</h1>
            <p class="text-2xl md:text-3xl mb-8 font-medium">{{ $restaurant->description ?? 'طعم لا يُقاوم' }}</p>

            <div class="flex gap-4 justify-center flex-wrap">
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}"
                    class="btn-primary px-8 py-4 rounded-full text-lg font-bold">
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
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-primary font-bold text-lg">اختر ما يناسبك</span>
                <h2 class="text-4xl font-black text-gray-800 mt-2">تصفح الفئات</h2>
            </div>

            @if($categories->isEmpty())
                <p class="text-center text-gray-500">لا توجد فئات متاحة حالياً</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($categories as $category)
                        <a href="{{ route('restaurant.menu', $restaurant->slug) }}#{{ $category->slug }}"
                            class="group flex flex-col items-center p-6 bg-gray-50 rounded-2xl hover:bg-primary hover:text-white transition duration-300 shadow-sm hover:shadow-xl">
                            <div
                                class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition shadow-sm">
                                🍽️ <!-- يمكنك لاحقاً إضافة أيقونة لكل تصنيف -->
                            </div>
                            <h3 class="font-bold text-lg">{{ $category->name }}</h3>
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
                    class="btn-primary px-6 py-2 rounded-full text-sm font-bold hidden sm:inline-block">
                    عرض الكل ➔
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- مثال لبطاقة منتج (يمكنك جعلها ديناميكية بجلب المنتجات المميزة) -->
                @forelse($restaurant->products()->where('is_available', true)->limit(4)->get() as $product)
                    <div
                        class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:-translate-y-2 transition duration-300">
                        <div class="relative h-48 overflow-hidden">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-4xl">🍔</div>
                            @endif
                            <span
                                class="absolute top-4 right-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">الأكثر
                                طلباً</span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $product->name }}</h3>
                            <p class="text-gray-500 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-black text-primary">{{ number_format($product->price, 2) }}
                                    ر.س</span>
                                <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit"
                                        class="bg-gray-100 hover:bg-primary hover:text-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center transition">
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
                    class="btn-primary px-6 py-2 rounded-full text-sm font-bold inline-block">
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
                <!-- Feature 1 -->
                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">🚀</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">توصيل سريع</h3>
                    <p class="text-gray-600">نوصل طلبك في أقل من {{ $restaurant->estimated_delivery_time ?? '٣٠' }} دقيقة
                        إلى باب منزلك</p>
                </div>

                <!-- Feature 2 -->
                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">🥬</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">طعام طازج</h3>
                    <p class="text-gray-600">نستخدم مكونات طازجة ١٠٠٪ يومياً لضمان الجودة</p>
                </div>

                <!-- Feature 3 -->
                <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                    <div class="text-5xl mb-4">📞</div>
                    <h3 class="text-xl font-bold mb-2 text-gray-800">دعم ٢٤/٧</h3>
                    <p class="text-gray-600">فريقنا جاهز لخدمتك على مدار الساعة طوال الأسبوع</p>
                </div>

                <!-- Feature 4 -->
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
                        <img src="{{ asset('storage/' . $restaurant->cover_image) }}" alt="{{ $restaurant->name }}"
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