@extends("themes.burger-theme.layout")

@section('content')

<!-- Hero Section -->
<section class="relative h-screen flex items-center justify-center overflow-hidden">
    @if($restaurant->cover_image)
        <img src="{{ asset('storage/' . $restaurant->cover_image) }}" 
             alt="{{ $restaurant->name }}" 
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

<!-- Features Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-black text-center mb-12 text-gray-800">لماذا نحن</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Feature 1 -->
            <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition">
                <div class="text-5xl mb-4">🚀</div>
                <h3 class="text-xl font-bold mb-2 text-gray-800">توصيل سريع</h3>
                <p class="text-gray-600">نوصل طلبك في أقل من {{ $restaurant->estimated_delivery_time ?? '٣٠' }} دقيقة إلى باب منزلك</p>
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
                    <img src="{{ asset('storage/' . $restaurant->cover_image) }}" 
                         alt="{{ $restaurant->name }}" 
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