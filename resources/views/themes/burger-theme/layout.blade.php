<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurant->name }} - {{ $pageTitle ?? 'طعم لا يُقاوم' }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Axios for AJAX Requests -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        :root {
            --primary: {{ $restaurant->primary_color ?? '#FF6B35' }};
            --secondary: {{ $restaurant->secondary_color ?? '#FFFFFF' }};
            --background: {{ $restaurant->background_color ?? '#1A1A1A' }};
        }
        
        * {
            font-family: 'Tajawal', sans-serif;
            scroll-behavior: smooth;
        }
        
        body {
            background-color: var(--background);
            color: #333;
        }
        
        /* Custom Utilities */
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

        /* Cart Dropdown Animation */
        .cart-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        
        .group:hover .cart-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="antialiased">

    <!-- Header & Navigation -->
    <header class="bg-white shadow-sm sticky top-0 z-50 transition-all duration-300" id="main-header">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                
                <!-- Logo & Brand -->
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="flex items-center gap-3 group">
                    @if($restaurant->logo)
                        <img src="{{ asset('storage/' . $restaurant->logo) }}" 
                             alt="{{ $restaurant->name }}" 
                             class="w-12 h-12 rounded-full object-cover border-2 border-gray-100 group-hover:border-primary transition">
                    @else
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-xl">🍔</div>
                    @endif
                    <div>
                        <h1 class="text-2xl font-black text-gray-800 leading-tight group-hover:text-primary transition">{{ $restaurant->name }}</h1>
                        <p class="text-xs text-gray-500 font-medium">طعم لا يُقاوم</p>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-8">
                    <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">الرئيسية</a>
                    <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">القائمة</a>
                    <a href="#about" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">من نحن</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary font-bold transition relative after:content-[''] after:absolute after:-bottom-1 after:right-0 after:w-0 after:h-0.5 after:bg-primary after:transition-all hover:after:w-full">اتصل بنا</a>
                </nav>

                <!-- Cart & Actions -->
                <div class="flex items-center gap-4">
                    
                    <!-- Quick Cart Preview (Dropdown) -->
                    <div class="relative group">
                        <button class="btn-primary px-5 py-2.5 rounded-full flex items-center gap-2 shadow-lg relative">
                            <span class="text-xl">🛒</span>
                            <span class="font-bold hidden sm:inline">السلة</span>
                            <span id="cart-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">
                                {{ count(session('cart', [])) }}
                            </span>
                        </button>

                        <!-- Dropdown Content -->
                        <div class="cart-dropdown absolute left-0 top-full mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50">
                            <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="font-bold text-gray-800">منتجاتك المختارة</h3>
                                <span id="mini-cart-count" class="text-xs text-gray-500">{{ count(session('cart', [])) }} عناصر</span>
                            </div>
                            
                            <div id="mini-cart-items" class="max-h-64 overflow-y-auto p-2 space-y-2">
                                @if(session('cart') && count(session('cart')) > 0)
                                    @foreach(session('cart') as $id => $details)
                                        @php $product = \App\Models\Product::find($id); @endphp
                                        @if($product)
                                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                                                <img src="{{ $product->image ? asset('storage/'.$product->image) : 'https://via.placeholder.com/50' }}" class="w-12 h-12 rounded-md object-cover">
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-bold text-gray-800 truncate">{{ $product->name }}</h4>
                                                    <p class="text-xs text-primary font-bold">{{ $product->price }} ر.س × {{ $details['qty'] }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-center py-8 text-gray-400 text-sm">
                                        السلة فارغة حالياً 🍽️
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
                                <a href="{{ route('cart.index') }}" class="block w-full btn-primary text-center py-2 rounded-lg text-sm font-bold hover:shadow-md transition">
                                    إتمام الطلب ➔
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden text-gray-600 text-2xl focus:outline-none" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                        ☰
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-100 p-4 absolute w-full shadow-lg">
            <div class="flex flex-col gap-4">
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary">الرئيسية</a>
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="text-gray-700 font-bold hover:text-primary">القائمة</a>
                <a href="#about" class="text-gray-700 font-bold hover:text-primary">من نحن</a>
                <a href="#contact" class="text-gray-700 font-bold hover:text-primary">اتصل بنا</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer Section -->
    <footer class="bg-gray-900 text-white pt-16 pb-8 mt-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand Info -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        @if($restaurant->logo)
                            <img src="{{ asset('storage/' . $restaurant->logo) }}" class="w-10 h-10 rounded-full object-cover">
                        @endif
                        <h3 class="text-2xl font-black">{{ $restaurant->name }}</h3>
                    </div>
                    <p class="text-gray-400 leading-relaxed mb-6">
                        {{ $restaurant->description ?? 'نقدم أشهى الأطباق بمكونات طازجة وجودة عالية لنضمن لك تجربة طعام لا تُنسى.' }}
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition">📘</a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition">📸</a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary transition">🐦</a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">روابط سريعة</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="{{ route('restaurant.home', $restaurant->slug) }}" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> الرئيسية</a></li>
                        <li><a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> القائمة</a></li>
                        <li><a href="#about" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> من نحن</a></li>
                        <li><a href="#contact" class="hover:text-primary transition flex items-center gap-2"><span class="text-xs">➤</span> اتصل بنا</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">تواصل معنا</h4>
                    <ul class="space-y-4 text-gray-400">
                        @if($restaurant->phone)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl">📞</span>
                                <div>
                                    <span class="block text-sm text-gray-500">الهاتف</span>
                                    <span class="font-medium">{{ $restaurant->phone }}</span>
                                </div>
                            </li>
                        @endif
                        @if($restaurant->email)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl">📧</span>
                                <div>
                                    <span class="block text-sm text-gray-500">البريد الإلكتروني</span>
                                    <span class="font-medium">{{ $restaurant->email }}</span>
                                </div>
                            </li>
                        @endif
                        @if($restaurant->address)
                            <li class="flex items-start gap-3">
                                <span class="text-primary text-xl">📍</span>
                                <div>
                                    <span class="block text-sm text-gray-500">العنوان</span>
                                    <span class="font-medium">{{ $restaurant->address }}</span>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>

                <!-- Working Hours -->
                <div>
                    <h4 class="text-lg font-bold mb-6 border-r-4 border-primary pr-3">ساعات العمل</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex justify-between border-b border-gray-800 pb-2">
                            <span>السبت - الخميس</span>
                            <span class="text-white font-bold">١٠ص - ٢ص</span>
                        </li>
                        <li class="flex justify-between border-b border-gray-800 pb-2">
                            <span>الجمعة</span>
                            <span class="text-white font-bold">١م - ٢ص</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
                <p>© {{ date('Y') }} {{ $restaurant->name }}. جميع الحقوق محفوظة. تم التطوير بشغف.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript Logic -->
    <script>
        // 1. Handle Add to Cart via AJAX
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form[action*="cart/add"]');
            
            forms.forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = form.querySelector('button');
                    const originalContent = btn.innerHTML;
                    
                    // Loading State
                    btn.disabled = true;
                    btn.innerHTML = '<span class="animate-spin inline-block">⏳</span> جاري الإضافة...';

                    try {
                        const response = await axios.post(form.action, new FormData(form));
                        
                        // Success Feedback
                        showToast(response.data.message || 'تمت الإضافة بنجاح!');
                        updateMiniCart();
                        
                    } catch (error) {
                        showToast('عذراً، حدث خطأ أثناء الإضافة', 'error');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                });
            });
        });

        // 2. Update Mini Cart & Badge without reload
        async function updateMiniCart() {
            // In a real app, you might fetch this from an API endpoint
            // For now, we'll just increment the badge visually for better UX
            const badge = document.getElementById('cart-badge');
            const miniCount = document.getElementById('mini-cart-count');
            
            let current = parseInt(badge.innerText);
            badge.innerText = current + 1;
            if(miniCount) miniCount.innerText = (current + 1) + ' عناصر';
            
            // Optional: Fetch full cart HTML via AJAX if needed
        }

        // 3. Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type === 'error' ? 'bg-red-600' : 'bg-green-600'}`;
            toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span><span>${message}</span>`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease-in reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>