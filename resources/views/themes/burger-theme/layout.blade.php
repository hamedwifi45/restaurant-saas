<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $restaurant->description ?? 'اطلب أشهى الأطباق من ' . $restaurant->name . ' مع توصيل سريع وجودة لا تضاهى.' }}">
    <meta name="keywords" content="طعام, توصيل, مطعم, {{ $restaurant->name }}, وجبات سريعة">
    <meta name="author" content="{{ $restaurant->name }}">

    <title>{{ $restaurant->name }} - {{ $pageTitle ?? 'طعم لا يُقاوم' }}</title>
    
    <!-- ==================== PWA Meta Tags ==================== -->
    <meta name="theme-color" content="{{ $restaurant->primary_color ?? '#FF6B35' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $restaurant->name }}">
    <meta name="application-name" content="{{ $restaurant->name }}">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- PWA Manifest & Icons ديناميكية -->
    <link rel="manifest" href="{{ route('pwa.manifest', $restaurant->slug) }}">
    @php
        $iconUrl = $restaurant->logo ? asset('storage/' . $restaurant->logo) : asset('/icons/default-icon.png');
    @endphp
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $iconUrl }}">
    <link rel="apple-touch-icon" href="{{ $iconUrl }}">
    
    <!-- ==================== Libraries & Fonts ==================== -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">

    <!-- ==================== Custom Styles ==================== -->
    <style>
        :root {
            --primary: {{ $restaurant->primary_color ?? '#FF6B35' }};
            --secondary: {{ $restaurant->secondary_color ?? '#FFFFFF' }};
            --background: {{ $restaurant->background_color ?? '#F9FAFB' }};
            --dark: {{ $restaurant->secondary_color ?? '#111827' }};
        }
        
        * {
            font-family: 'Tajawal', sans-serif;
            scroll-behavior: smooth;
        }
        
        body {
            background-color: var(--background);
            color: #333;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #e55a2b; }

        /* Utilities */
        .text-primary { color: var(--primary); }
        .bg-primary { background-color: var(--primary); }
        .border-primary { border-color: var(--primary); }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(0,0,0,0.3);
        }

        /* Animations */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.3s ease-in-out; }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .mobile-menu-open { animation: slideInRight 0.3s ease-out forwards; }

        /* Cart Dropdown */
        .cart-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .group:hover .cart-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Toast Container */
        #toast-container {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast-item {
            pointer-events: auto;
            animation: slideInLeft 0.4s ease-out forwards;
        }
        @keyframes slideInLeft {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Scroll to Top Button */
        #scroll-top {
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        #scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen">

    <!-- ==================== Top Bar (إعلانات سريعة) ==================== -->
    <div class="bg-primary text-white text-xs md:text-sm py-2 hidden md:block">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <span class="flex items-center gap-2">
                <span>🚚</span>
                <span>توصيل مجاني للطلبات فوق 50 ريال!</span>
            </span>
            <div class="flex items-center gap-4">
                @if($restaurant->phone)
                    <a href="tel:{{ $restaurant->phone }}" class="hover:text-gray-200 transition flex items-center gap-1">
                        <span>📞</span> {{ $restaurant->phone }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- ==================== Header & Navigation ==================== -->
    <header class="bg-white shadow-sm sticky top-0 z-40 transition-all duration-300" id="main-header">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                
                <!-- Logo & Brand -->
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="flex items-center gap-3 group">
                    @if($restaurant->logo)
                        <img src="{{ asset('storage/' . $restaurant->logo) }}" 
                             alt="{{ $restaurant->name }}" 
                             class="w-12 h-12 rounded-full object-cover border-2 border-gray-100 group-hover:border-primary transition shadow-sm">
                    @else
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-2xl shadow-sm">🍔</div>
                    @endif
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-gray-800 leading-tight group-hover:text-primary transition">
                            {{ $restaurant->name }}
                        </h1>
                        <p class="text-xs text-gray-500 font-medium">طعم لا يُقاوم</p>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-8">
                    <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">الرئيسية</a>
                    <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">القائمة</a>
                    <a href="{{ route('offers.index', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">العروض 🔥</a>
                    <a href="{{ route('order.track.form', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">تتبع الطلب</a>
                </nav>

                <!-- Cart & Actions -->
                <div class="flex items-center gap-3 md:gap-4">
                    
                    <!-- Quick Cart Preview (Dropdown) -->
                    <div class="relative group">
                        <button class="btn-primary px-4 md:px-5 py-2.5 rounded-full flex items-center gap-2 shadow-lg relative hover:shadow-xl transition">
                            <span class="text-xl">🛒</span>
                            <span class="font-bold hidden sm:inline">السلة</span>
                            <span id="cart-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                                {{ count(session('cart', [])) }}
                            </span>
                        </button>

                        <!-- Dropdown Content -->
                        <div class="cart-dropdown absolute left-0 top-full mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50">
                            <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="font-bold text-gray-800 text-sm">منتجاتك المختارة</h3>
                                <span id="mini-cart-count" class="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">{{ count(session('cart', [])) }} عناصر</span>
                            </div>
                            
                            <div id="mini-cart-items" class="max-h-64 overflow-y-auto p-2 space-y-2">
                                @if(session('cart') && count(session('cart')) > 0)
                                    @foreach(session('cart') as $id => $details)
                                        @php $product = \App\Models\Product::find($id); @endphp
                                        @if($product)
                                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                                                <img src="{{ $product->image ? asset('storage/'.$product->image) : 'https://via.placeholder.com/50?text=🍔' }}" class="w-12 h-12 rounded-md object-cover bg-gray-100">
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-sm font-bold text-gray-800 truncate">{{ $product->name }}</h4>
                                                    <p class="text-xs text-primary font-bold">{{ number_format($product->price, 2) }} ر.س × {{ $details['qty'] }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-center py-8 px-4">
                                        <div class="text-4xl mb-2">🍽️</div>
                                        <p class="text-gray-500 text-sm font-medium">السلة فارغة حالياً</p>
                                        <p class="text-gray-400 text-xs mt-1">ابدأ بإضافة منتجات لذيذة</p>
                                    </div>
                                @endif
                            </div>

                            <div class="p-4 bg-gray-50 border-t border-gray-100">
                                <div class="flex justify-between mb-3 text-sm font-bold text-gray-700">
                                    <span>المجموع:</span>
                                    <span id="mini-cart-total">
                                        @php
                                            $total = 0;
                                            if(session('cart')) {
                                                foreach(session('cart') as $id => $details) {
                                                    $p = \App\Models\Product::find($id);
                                                    if($p) $total += $p->price * $details['qty'];
                                                }
                                            }
                                        @endphp
                                        {{ number_format($total, 2) }} ر.س
                                    </span>
                                </div>
                                <a href="{{ route('cart.index', $restaurant->slug) }}" class="block w-full btn-primary text-center py-2.5 rounded-xl text-sm font-bold hover:shadow-md transition">
                                    إتمام الطلب ➔
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden text-gray-600 text-2xl focus:outline-none p-2 hover:bg-gray-100 rounded-lg transition" onclick="toggleMobileMenu()">
                        ☰
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Off-canvas) -->
        <div id="mobile-menu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" onclick="toggleMobileMenu()">
            <div class="absolute left-0 top-0 h-full w-72 bg-white shadow-2xl p-6 flex flex-col" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <h2 class="text-xl font-black text-gray-800">القائمة</h2>
                    <button onclick="toggleMobileMenu()" class="text-gray-500 hover:text-red-500 text-2xl transition">✕</button>
                </div>
                <div class="flex flex-col gap-4 flex-1">
                    <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary hover:bg-gray-50 p-3 rounded-lg transition flex items-center gap-3"><span>🏠</span> الرئيسية</a>
                    <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary hover:bg-gray-50 p-3 rounded-lg transition flex items-center gap-3"><span>📜</span> القائمة</a>
                    <a href="{{ route('offers.index', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary hover:bg-gray-50 p-3 rounded-lg transition flex items-center gap-3"><span>🔥</span> العروض</a>
                    <a href="{{ route('order.track.form', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary hover:bg-gray-50 p-3 rounded-lg transition flex items-center gap-3"><span>🚚</span> تتبع الطلب</a>
                    <a href="{{ route('order.track.form', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary hover:bg-gray-50 p-3 rounded-lg transition flex items-center gap-3"><span>📄</span> فاتورتي</a>
                </div>
                <div class="mt-auto pt-4 border-t text-center text-xs text-gray-400">
                    © {{ date('Y') }} {{ $restaurant->name }}
                </div>
            </div>
        </div>
    </header>

    <!-- ==================== Main Content Area ==================== -->
    <main class="flex-grow min-h-screen">
        @yield('content')
    </main>

    <!-- ==================== Footer Section ==================== -->
    <footer class="bg-gray-900 text-white pt-16 pb-8 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand Info -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        @if($restaurant->logo)
                            <img src="{{ asset('storage/' . $restaurant->logo) }}" class="w-12 h-12 rounded-full object-cover border border-gray-700">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center text-2xl">🍔</div>
                        @endif
                        <h3 class="text-2xl font-black">{{ $restaurant->name }}</h3>
                    </div>
                    <p class="text-gray-400 leading-relaxed mb-6 text-sm">
                        {{ $restaurant->description ?? 'نقدم أشهى الأطباق بمكونات طازجة وجودة عالية لنضمن لك تجربة طعام لا تُنسى. اطلب الآن واستمتع بالطعم الأصلي.' }}
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition transform hover:scale-110">📘</a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition transform hover:scale-110">📸</a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition transform hover:scale-110">🐦</a>
                    </div>
                </div>

                <!-- Quick Links & Categories -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">روابط سريعة</h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li><a href="{{ route('restaurant.home', $restaurant->slug) }}" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> الرئيسية</a></li>
                        <li><a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> القائمة الكاملة</a></li>
                        <li><a href="{{ route('offers.index', $restaurant->slug) }}" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> العروض والخصومات</a></li>
                        <li><a href="#about" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> من نحن</a></li>
                    </ul>
                    
                    <h4 class="text-lg font-bold mb-4 mt-8 border-r-4 border-primary pr-3">الفئات</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-primary transition">البرجر</a></li>
                        <li><a href="#" class="hover:text-primary transition">البيتزا</a></li>
                        <li><a href="#" class="hover:text-primary transition">المشروبات</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">تواصل معنا</h4>
                    <ul class="space-y-4 text-gray-400 text-sm">
                        @if($restaurant->phone)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl mt-1">📞</span>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">الهاتف</span>
                                    <a href="tel:{{ $restaurant->phone }}" class="font-medium hover:text-white transition">{{ $restaurant->phone }}</a>
                                </div>
                            </li>
                        @endif
                        @if($restaurant->email)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl mt-1">📧</span>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">البريد الإلكتروني</span>
                                    <a href="mailto:{{ $restaurant->email }}" class="font-medium hover:text-white transition">{{ $restaurant->email }}</a>
                                </div>
                            </li>
                        @endif
                        @if($restaurant->address)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl mt-1">📍</span>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">العنوان</span>
                                    <span class="font-medium">{{ $restaurant->address }}</span>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>

                <!-- Working Hours & Newsletter -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">ساعات العمل</h4>
                    <ul class="space-y-3 text-gray-400 text-sm mb-8">
                        <li class="flex justify-between border-b border-gray-800 pb-2">
                            <span>السبت - الخميس</span>
                            <span class="text-white font-bold">١٠ص - ٢ص</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-800 pb-2">
                            <span>الجمعة</span>
                            <span class="text-white font-bold">١م - ٢ص</span>
                        </li>
                    </ul>

                    <h4 class="text-lg font-bold mb-4 border-r-4 border-primary pr-3">نصائح التطبيق</h4>
                    <p class="text-xs text-gray-400 mb-3">اضغط على "مشاركة" ثم "إضافة إلى الشاشة الرئيسية" لتثبيت التطبيق.</p>
                    <button onclick="document.getElementById('install-pwa-btn').click()" class="w-full bg-gray-800 hover:bg-gray-700 text-white py-2 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                        <span>📱</span> تثبيت التطبيق
                    </button>
                </div>
            </div>

            <!-- Copyright & Legal -->
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
                <p>© {{ date('Y') }} {{ $restaurant->name }}. جميع الحقوق محفوظة. تم التطوير بشغف.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-white transition">سياسة الخصوصية</a>
                    <a href="#" class="hover:text-white transition">الشروط والأحكام</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ==================== Floating Elements ==================== -->
    
    <!-- زر تثبيت التطبيق -->
    <button id="install-pwa-btn" class="fixed bottom-24 left-5 z-40 bg-primary text-white px-5 py-3 rounded-full shadow-2xl font-bold flex items-center gap-2 transition-all duration-300 transform translate-y-20 opacity-0 hover:scale-105">
        <span>📱</span>
        <span>تثبيت التطبيق</span>
    </button>

    <!-- زر العودة للأعلى -->
    <button id="scroll-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="fixed bottom-6 right-6 z-40 bg-gray-800 text-white w-12 h-12 rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-all duration-300">
        ↑
    </button>

    <!-- حاوية الإشعارات -->
    <div id="toast-container"></div>

    <!-- ==================== JavaScript Logic ==================== -->
    <script>
        // 1. Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.querySelector('div').classList.add('mobile-menu-open');
                document.body.style.overflow = 'hidden'; // منع التمرير
            } else {
                menu.querySelector('div').classList.remove('mobile-menu-open');
                setTimeout(() => {
                    menu.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }
        }

        // 2. Scroll to Top Button Visibility
        window.addEventListener('scroll', () => {
            const scrollBtn = document.getElementById('scroll-top');
            if (window.scrollY > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });

        // 3. معالجة إضافة المنتج للسلة عبر AJAX
        document.addEventListener('DOMContentLoaded', () => {
            const cartForms = document.querySelectorAll('form[action*="cart/add"]');
            
            cartForms.forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    const originalContent = btn.innerHTML;
                    
                    btn.disabled = true;
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                    btn.innerHTML = `
                        <svg class="animate-spin h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        جاري الإضافة...
                    `;

                    try {
                        const response = await axios.post(form.action, new FormData(form), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        });
                        
                        if (response.data.success) {
                            showToast(response.data.message, 'success');
                            updateMiniCart(response.data);
                        } else {
                            showToast(response.data.message || 'حدث خطأ غير متوقع', 'error');
                        }
                    } catch (error) {
                        console.error('Cart Error:', error);
                        showToast('عذراً، حدث خطأ في الاتصال. حاول مرة أخرى.', 'error');
                        btn.classList.add('animate-shake');
                        setTimeout(() => btn.classList.remove('animate-shake'), 500);
                    } finally {
                        btn.disabled = false;
                        btn.classList.remove('opacity-75', 'cursor-not-allowed');
                        btn.innerHTML = originalContent;
                    }
                });
            });

            // 4. PWA Install Logic
            let deferredPrompt;
            const installBtn = document.getElementById('install-pwa-btn');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                // إظهار الزر بتأثير انسيابي
                installBtn.classList.remove('translate-y-20', 'opacity-0');
            });

            installBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    showToast('تم تثبيت التطبيق بنجاح! 🎉', 'success');
                    installBtn.classList.add('translate-y-20', 'opacity-0');
                }
                deferredPrompt = null;
            });

            window.addEventListener('appinstalled', () => {
                installBtn.classList.add('translate-y-20', 'opacity-0');
                deferredPrompt = null;
            });

            // 5. Service Worker Registration
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('{{ route("pwa.sw", $restaurant->slug) }}', {
                        scope: '/{{ $restaurant->slug }}/'
                    }).then(reg => console.log('SW Registered:', reg))
                      .catch(err => console.log('SW Failed:', err));
                });
            }
        });

        // 6. تحديث السلة المصغرة ديناميكياً
        function updateMiniCart(data) {
            if (!data) return;
            const badge = document.getElementById('cart-badge');
            const miniCount = document.getElementById('mini-cart-count');
            const miniCartItems = document.getElementById('mini-cart-items');
            const miniCartTotal = document.getElementById('mini-cart-total');

            if (badge) {
                badge.innerText = data.count;
                badge.classList.add('scale-125');
                setTimeout(() => badge.classList.remove('scale-125'), 200);
            }
            if (miniCount) miniCount.innerText = `${data.count} عناصر`;
            if (miniCartTotal) miniCartTotal.innerText = `${data.total} ر.س`;

            if (miniCartItems && data.cart_html) {
                miniCartItems.style.opacity = '0';
                setTimeout(() => {
                    miniCartItems.innerHTML = data.cart_html;
                    miniCartItems.style.opacity = '1';
                    miniCartItems.style.transition = 'opacity 0.3s ease-in-out';
                }, 150);
            }
        }

        // 7. نظام الإشعارات (Toast) المتطور
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            const icon = type === 'success' ? '✅' : '⚠️';

            toast.className = `toast-item flex items-center gap-3 px-5 py-3 rounded-xl shadow-2xl text-white font-medium ${bgColor}`;
            toast.innerHTML = `<span class="text-xl">${icon}</span><span class="flex-1">${message}</span>`;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }
    </script>
</body>
</html>