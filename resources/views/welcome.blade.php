<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة المطاعم السورية | حوّل مطعمك إلى متجر إلكتروني في دقائق</title>
    <meta name="description" content="المنصة السورية المتكاملة لإنشاء متجرك الإلكتروني للمطاعم مع لوحة تحكم احترافية، نظام طلبات ذكي، وتتبع مباشر.">

    @vite(['resources/css/app.css'])
    
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <style>
        html { scroll-behavior: smooth; }
        body { font-family: system-ui, 'Segoe UI', Arial, sans-serif; }
        
        /* خلفية Hero بنمط هندسي */
        .hero-pattern {
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(255, 107, 53, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 53, 0.06) 0%, transparent 50%);
        }
        
        .hero-dots {
            background-image: radial-gradient(circle, rgba(255, 107, 53, 0.15) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* خط يربط الخطوات */
        .steps-connector {
            position: absolute;
            top: 60px;
            right: 16.66%;
            left: 16.66%;
            height: 2px;
            background: linear-gradient(to left, #FF6B35 0%, #FFD0B3 50%, #FF6B35 100%);
            z-index: 0;
        }

        /* Gradient CTA */
        .cta-gradient {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8F5C 50%, #E85A24 100%);
            position: relative;
            overflow: hidden;
        }
        .cta-gradient::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255,255,255,0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255,255,255,0.08) 0%, transparent 40%);
        }

        /* FAQ Accordion animation */
        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
        }
        .faq-item.active .faq-content {
            max-height: 500px;
        }
        .faq-item.active .faq-icon {
            transform: rotate(45deg);
            background: #FF6B35;
            color: white;
        }
        .faq-icon {
            transition: transform 0.3s ease, background 0.3s ease, color 0.3s ease;
        }

        /* Feature card hover */
        .feature-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(255, 107, 53, 0.25);
        }

        /* Pricing popular card */
        .pricing-popular {
            background: linear-gradient(135deg, #FF6B35 0%, #E85A24 100%);
        }

        /* Mobile menu */
        .mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }
        .mobile-menu.open {
            max-height: 400px;
        }

        /* Trust logos */
        .trust-logo {
            filter: grayscale(100%);
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        .trust-logo:hover {
            filter: grayscale(0%);
            opacity: 1;
        }

        /* Testimonial card */
        .testimonial-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Mockup shadow */
        .mockup-shadow {
            filter: drop-shadow(0 30px 60px rgba(26, 26, 26, 0.25));
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #F9FAFB; }
        ::-webkit-scrollbar-thumb { background: #FF6B35; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #E85A24; }
    </style>
</head>
<body class="bg-white text-dark antialiased overflow-x-hidden">

    <!-- ============================================
         HEADER - الشريط العلوي
         ============================================ -->
    <header id="header" class="fixed top-0 right-0 left-0 bg-white/95 backdrop-blur-md z-50 border-b border-gray-100 transition-all duration-300">
        <nav class="container mx-auto px-4 lg:px-8" aria-label="التنقل الرئيسي">
            <div class="flex items-center justify-between h-20">
                
                <!-- الشعار -->
                <a href="#" class="flex items-center gap-2 group" aria-label="منصة المطاعم السورية - الصفحة الرئيسية">
                    <div class="w-11 h-11 bg-brand-500 rounded-2xl flex items-center justify-center shadow-glow group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-utensils text-white text-lg"></i>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xl font-bold text-dark">منصة المطاعم</span>
                        <span class="text-[10px] text-gray-500 -mt-1">سوريا • Restaurant Platform</span>
                    </div>
                </a>

                <!-- قائمة التنقل - ديسكتوب -->
                <ul class="hidden lg:flex items-center gap-10">
                    <li><a href="#home" class="text-gray-700 hover:text-brand-500 font-medium transition-colors relative group">
                        الرئيسية
                        <span class="absolute -bottom-1 right-0 w-0 h-0.5 bg-brand-500 group-hover:w-full transition-all"></span>
                    </a></li>
                    <li><a href="#features" class="text-gray-700 hover:text-brand-500 font-medium transition-colors relative group">
                        المميزات
                        <span class="absolute -bottom-1 right-0 w-0 h-0.5 bg-brand-500 group-hover:w-full transition-all"></span>
                    </a></li>
                    <li><a href="#pricing" class="text-gray-700 hover:text-brand-500 font-medium transition-colors relative group">
                        الأسعار
                        <span class="absolute -bottom-1 right-0 w-0 h-0.5 bg-brand-500 group-hover:w-full transition-all"></span>
                    </a></li>
                    <li><a href="#contact" class="text-gray-700 hover:text-brand-500 font-medium transition-colors relative group">
                        تواصل معنا
                        <span class="absolute -bottom-1 right-0 w-0 h-0.5 bg-brand-500 group-hover:w-full transition-all"></span>
                    </a></li>
                </ul>

                <!-- زر CTA + Hamburger -->
                <div class="flex items-center gap-3">
                    <a href="#pricing" class="hidden md:inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold px-6 py-3 rounded-2xl shadow-glow hover:shadow-2xl hover:-translate-y-0.5 transition-all">
                        <span>ابدأ مجاناً</span>
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <button id="menuToggle" class="lg:hidden w-11 h-11 flex items-center justify-center rounded-xl bg-soft-gray hover:bg-gray-200 transition-colors" aria-label="فتح القائمة" aria-expanded="false">
                        <i class="fa-solid fa-bars text-xl text-dark"></i>
                    </button>
                </div>
            </div>

            <!-- قائمة الموبايل -->
            <div id="mobileMenu" class="mobile-menu lg:hidden">
                <ul class="py-4 space-y-1 border-t border-gray-100">
                    <li><a href="#home" class="block py-3 px-4 text-gray-700 hover:bg-brand-50 hover:text-brand-500 rounded-xl font-medium transition-colors">الرئيسية</a></li>
                    <li><a href="#features" class="block py-3 px-4 text-gray-700 hover:bg-brand-50 hover:text-brand-500 rounded-xl font-medium transition-colors">المميزات</a></li>
                    <li><a href="#pricing" class="block py-3 px-4 text-gray-700 hover:bg-brand-50 hover:text-brand-500 rounded-xl font-medium transition-colors">الأسعار</a></li>
                    <li><a href="#contact" class="block py-3 px-4 text-gray-700 hover:bg-brand-50 hover:text-brand-500 rounded-xl font-medium transition-colors">تواصل معنا</a></li>
                    <li class="pt-2">
                        <a href="#pricing" class="block text-center bg-brand-500 hover:bg-brand-600 text-white font-semibold py-3 rounded-2xl transition-colors">ابدأ مجاناً</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- ============================================
         HERO SECTION - القسم الرئيسي
         ============================================ -->
    <section id="home" class="relative pt-32 pb-20 lg:pt-40 lg:pb-32 hero-pattern overflow-hidden">
        <!-- خلفية نقاط -->
        <div class="absolute inset-0 hero-dots opacity-40 pointer-events-none"></div>
        
        <div class="container mx-auto px-4 lg:px-8 relative">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                
                <!-- المحتوى -->
                <div class="order-2 lg:order-1 text-center lg:text-right">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-600 px-4 py-2 rounded-full text-sm font-semibold mb-6 border border-brand-100">
                        <span class="w-2 h-2 bg-brand-500 rounded-full animate-pulse"></span>
                        <span>المنصة السورية #1 للمطاعم</span>
                    </div>

                    <!-- العنوان الرئيسي -->
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-dark leading-tight mb-6">
                        حوّل مطعمك إلى
                        <span class="relative inline-block">
                            <span class="relative z-10 text-brand-500">متجر إلكتروني</span>
                            <svg class="absolute -bottom-2 right-0 w-full" viewBox="0 0 300 12" fill="none">
                                <path d="M2 9C50 3 150 3 298 9" stroke="#FF6B35" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <br>
                        في دقائق
                    </h1>

                    <!-- الوصف -->
                    <p class="text-lg lg:text-xl text-gray-600 leading-relaxed mb-8 max-w-xl mx-auto lg:mx-0">
                        المنصة السورية المتكاملة لإنشاء متجرك الإلكتروني مع لوحة تحكم احترافية، نظام طلبات ذكي، وتتبع مباشر لكل طلب من المطعم حتى باب العميل.
                    </p>

                    <!-- الأزرار -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-12">
                        <a href="#pricing" class="inline-flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-bold px-8 py-4 rounded-2xl shadow-glow hover:shadow-2xl hover:-translate-y-1 transition-all text-lg">
                            <span>ابدأ تجربتك المجانية</span>
                            <i class="fa-solid fa-rocket"></i>
                        </a>
                        <a href="#how-it-works" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-dark font-bold px-8 py-4 rounded-2xl border-2 border-gray-200 hover:border-brand-500 transition-all text-lg">
                            <i class="fa-solid fa-circle-play text-brand-500"></i>
                            <span>شاهد العرض التوضيحي</span>
                        </a>
                    </div>

                    <!-- الإحصائيات -->
                    <div class="grid grid-cols-3 gap-4 pt-8 border-t border-gray-200">
                        <div class="text-center lg:text-right">
                            <div class="text-3xl lg:text-4xl font-extrabold text-dark">+500</div>
                            <div class="text-sm text-gray-500 mt-1">مطعم سوري</div>
                        </div>
                        <div class="text-center lg:text-right border-r border-l border-gray-200 px-4">
                            <div class="text-3xl lg:text-4xl font-extrabold text-dark">+10K</div>
                            <div class="text-sm text-gray-500 mt-1">طلب شهرياً</div>
                        </div>
                        <div class="text-center lg:text-right">
                            <div class="flex items-center justify-center lg:justify-start gap-1 text-3xl lg:text-4xl font-extrabold text-dark">
                                4.9
                                <i class="fa-solid fa-star text-warning text-xl"></i>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">تقييم العملاء</div>
                        </div>
                    </div>
                </div>

                <!-- Mockup لوحة التحكم -->
                <div class="order-1 lg:order-2 relative">
                    <div class="relative animate-float">
                        <!-- دوائر خلفية -->
                        <div class="absolute -top-10 -right-10 w-72 h-72 bg-brand-200 rounded-full blur-3xl opacity-40"></div>
                        <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-brand-300 rounded-full blur-3xl opacity-30"></div>
                        
                        <!-- Mockup الرئيسي -->
                        <div class="relative bg-white rounded-3xl shadow-card mockup-shadow overflow-hidden border border-gray-100">
                            <!-- شريط المتصفح -->
                            <div class="bg-gray-50 border-b border-gray-100 px-4 py-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <span class="w-3 h-3 bg-red-400 rounded-full"></span>
                                    <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
                                    <span class="w-3 h-3 bg-green-400 rounded-full"></span>
                                </div>
                                <div class="flex-1 bg-white rounded-lg px-3 py-1 text-xs text-gray-400 text-center mx-8">
                                    <i class="fa-solid fa-lock text-success text-[10px] ml-1"></i>
                                    shamrestaurant.sy
                                </div>
                            </div>
                            <!-- محتوى لوحة التحكم -->
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <div class="text-xs text-gray-400">مرحباً، أبو يوسف 👋</div>
                                        <div class="text-lg font-bold text-dark">لوحة التحكم</div>
                                    </div>
                                    <div class="w-10 h-10 bg-brand-100 rounded-xl flex items-center justify-center">
                                        <i class="fa-solid fa-bell text-brand-500"></i>
                                    </div>
                                </div>
                                
                                <!-- بطاقات إحصائية -->
                                <div class="grid grid-cols-3 gap-3 mb-5">
                                    <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-2xl p-3 text-white">
                                        <i class="fa-solid fa-receipt text-lg mb-2"></i>
                                        <div class="text-xl font-bold">142</div>
                                        <div class="text-[10px] opacity-90">طلب اليوم</div>
                                    </div>
                                    <div class="bg-soft-gray rounded-2xl p-3">
                                        <i class="fa-solid fa-sack-dollar text-lg mb-2 text-success"></i>
                                        <div class="text-xl font-bold text-dark">845K</div>
                                        <div class="text-[10px] text-gray-500">ل.س إيرادات</div>
                                    </div>
                                    <div class="bg-soft-gray rounded-2xl p-3">
                                        <i class="fa-solid fa-users text-lg mb-2 text-brand-500"></i>
                                        <div class="text-xl font-bold text-dark">89</div>
                                        <div class="text-[10px] text-gray-500">عميل جديد</div>
                                    </div>
                                </div>

                                <!-- طلبات حية -->
                                <div class="bg-soft-gray rounded-2xl p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="text-sm font-bold text-dark">طلبات مباشرة</div>
                                        <span class="flex items-center gap-1 text-[10px] text-success font-semibold">
                                            <span class="w-1.5 h-1.5 bg-success rounded-full animate-pulse"></span>
                                            مباشر
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="bg-white rounded-xl p-2.5 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-brand-100 rounded-lg flex items-center justify-center">
                                                    <i class="fa-solid fa-burger text-brand-500 text-xs"></i>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-bold">#1847</div>
                                                    <div class="text-[10px] text-gray-400">شاورما عربية ×2</div>
                                                </div>
                                            </div>
                                            <span class="text-[10px] bg-warning/10 text-warning px-2 py-1 rounded-full font-bold">قيد التحضير</span>
                                        </div>
                                        <div class="bg-white rounded-xl p-2.5 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-success/10 rounded-lg flex items-center justify-center">
                                                    <i class="fa-solid fa-motorcycle text-success text-xs"></i>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-bold">#1846</div>
                                                    <div class="text-[10px] text-gray-400">كبة مشوية</div>
                                                </div>
                                            </div>
                                            <span class="text-[10px] bg-brand-500/10 text-brand-500 px-2 py-1 rounded-full font-bold">في الطريق</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- بطاقة عائمة - إشعار -->
                        <div class="absolute -top-4 -left-4 bg-white rounded-2xl shadow-card p-3 flex items-center gap-3 border border-gray-100 animate-float" style="animation-delay: 1s;">
                            <div class="w-10 h-10 bg-success/10 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-check text-success"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-dark">طلب جديد!</div>
                                <div class="text-[10px] text-gray-500">منذ ثانيتين</div>
                            </div>
                        </div>

                        <!-- بطاقة عائمة - تقييم -->
                        <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl shadow-card p-3 flex items-center gap-3 border border-gray-100 animate-float" style="animation-delay: 2s;">
                            <div class="flex -space-x-2 space-x-reverse">
                                <div class="w-8 h-8 bg-brand-200 rounded-full border-2 border-white"></div>
                                <div class="w-8 h-8 bg-brand-400 rounded-full border-2 border-white"></div>
                            </div>
                            <div>
                                <div class="flex text-warning text-xs">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <div class="text-[10px] text-gray-500">تقييم 5 نجوم</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         TRUST BAR - شريط الثقة
         ============================================ -->
    <section class="py-12 bg-soft-gray border-y border-gray-100">
        <div class="container mx-auto px-4 lg:px-8">
            <p class="text-center text-sm text-gray-500 font-medium mb-8">
                <i class="fa-solid fa-shield-halved text-brand-500 ml-1"></i>
                موثوق من قبل أفضل المطاعم السورية في دمشق وحلب وحمص واللاذقية
            </p>
            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-8 items-center justify-items-center">
                <!-- شعارات مطاعم سورية وهمية -->
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-burger text-2xl"></i>
                    <span>برجر الشام</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-pizza-slice text-2xl"></i>
                    <span>بيتزا حلب</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-drumstick-bite text-2xl"></i>
                    <span>مشاوي دمشق</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-fish text-2xl"></i>
                    <span>صيد اللاذقية</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-mug-hot text-2xl"></i>
                    <span>قهوة أمية</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-ice-cream text-2xl"></i>
                    <span>بوظة الشام</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-bowl-rice text-2xl"></i>
                    <span>مطبخ ماما هندا</span>
                </div>
                <div class="trust-logo flex items-center gap-2 text-gray-700 font-bold text-lg">
                    <i class="fa-solid fa-cookie-bite text-2xl"></i>
                    <span>حلويات الأصيل</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         FEATURES SECTION - المميزات
         ============================================ -->
    <section id="features" class="py-20 lg:py-32 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
            <!-- العنوان -->
            <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                <span class="inline-block bg-brand-50 text-brand-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <i class="fa-solid fa-sparkles ml-1"></i>
                    المميزات
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-dark mb-5">
                    كل ما تحتاجه لإدارة مطعمك
                    <span class="text-brand-500">رقمياً</span>
                </h2>
                <p class="text-lg text-gray-600 leading-relaxed">
                    أدوات قوية مصممة خصيصاً للمطاعم السورية، تمنحك التحكم الكامل في طلباتك وعملائك ومبيعاتك من مكان واحد.
                </p>
            </div>

            <!-- شبكة المميزات -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <!-- الميزة 1 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        🛒
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">متجر إلكتروني احترافي</h3>
                    <p class="text-gray-600 leading-relaxed">
                        صفحة رئيسية جذابة، قائمة منتجات منظمة، وسلة شراء ذكية تجعل تجربة عملائك لا تُنسى.
                    </p>
                </div>

                <!-- الميزة 2 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        📦
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">نظام طلبات متقدم</h3>
                    <p class="text-gray-600 leading-relaxed">
                        إدارة الطلبات من الاستلام حتى التوصيل مع تتبع مباشر لكل مرحلة وتحديثات فورية للعميل.
                    </p>
                </div>

                <!-- الميزة 3 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        🎁
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">عروض وكوبونات ذكية</h3>
                    <p class="text-gray-600 leading-relaxed">
                        عروض تلقائية وكوبونات خصم مخصصة لزيادة المبيعات وبناء ولاء العملاء بشكل مستمر.
                    </p>
                </div>

                <!-- الميزة 4 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">لوحة تحكم سهلة</h3>
                    <p class="text-gray-600 leading-relaxed">
                        لوحة تحكم بسيطة وقوية لإدارة كل شيء من مكان واحد - المبيعات، المنتجات، العملاء، والتقارير.
                    </p>
                </div>

                <!-- الميزة 5 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        ⭐
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">تقييمات موثقة</h3>
                    <p class="text-gray-600 leading-relaxed">
                        نظام تقييمات يمنع التقييمات الوهمية ويبني الثقة مع عملائك من خلال تجارب حقيقية وموثقة.
                    </p>
                </div>

                <!-- الميزة 6 -->
                <div class="feature-card reveal bg-white rounded-3xl p-8 border border-gray-100 hover:border-brand-200">
                    <div class="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center text-3xl mb-6">
                        📱
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">تطبيق جوال (PWA)</h3>
                    <p class="text-gray-600 leading-relaxed">
                        كل مطعم له تطبيقه الخاص بدون الحاجة لـ App Store - يعمل على جميع الأجهزة بكفاءة عالية.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         HOW IT WORKS - كيف يعمل
         ============================================ -->
    <section id="how-it-works" class="py-20 lg:py-32 bg-soft-gray relative">
        <div class="container mx-auto px-4 lg:px-8 relative">
            <!-- العنوان -->
            <div class="text-center max-w-3xl mx-auto mb-20 reveal">
                <span class="inline-block bg-brand-50 text-brand-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <i class="fa-solid fa-route ml-1"></i>
                    كيف يعمل
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-dark mb-5">
                    ابدأ في <span class="text-brand-500">3 خطوات</span> بسيطة
                </h2>
                <p class="text-lg text-gray-600">
                    من الفكرة إلى أول طلب في أقل من 10 دقائق - بدون أي تعقيدات تقنية.
                </p>
            </div>

            <!-- الخطوات -->
            <div class="relative grid md:grid-cols-3 gap-8 lg:gap-12">
                <!-- خط الربط -->
                <div class="steps-connector hidden md:block"></div>

                <!-- الخطوة 1 -->
                <div class="relative reveal">
                    <div class="bg-white rounded-3xl p-8 text-center relative z-10 shadow-soft hover:shadow-card transition-shadow">
                        <div class="w-24 h-24 mx-auto mb-6 relative">
                            <div class="absolute inset-0 bg-brand-100 rounded-full"></div>
                            <div class="absolute inset-2 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-extrabold text-brand-500">01</span>
                            </div>
                        </div>
                        <div class="text-5xl mb-4">✍️</div>
                        <h3 class="text-xl font-bold text-dark mb-3">أنشئ حسابك</h3>
                        <p class="text-gray-600 leading-relaxed">
                            سجل في دقيقتين بدون الحاجة لبطاقة ائتمان أو أي التزامات.
                        </p>
                    </div>
                </div>

                <!-- الخطوة 2 -->
                <div class="relative reveal">
                    <div class="bg-white rounded-3xl p-8 text-center relative z-10 shadow-soft hover:shadow-card transition-shadow">
                        <div class="w-24 h-24 mx-auto mb-6 relative">
                            <div class="absolute inset-0 bg-brand-100 rounded-full"></div>
                            <div class="absolute inset-2 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-extrabold text-brand-500">02</span>
                            </div>
                        </div>
                        <div class="text-5xl mb-4">🍔</div>
                        <h3 class="text-xl font-bold text-dark mb-3">ابنِ قائمتك</h3>
                        <p class="text-gray-600 leading-relaxed">
                            أضف منتجاتك وصورها وأسعارها بالليرة السورية بسهولة تامة.
                        </p>
                    </div>
                </div>

                <!-- الخطوة 3 -->
                <div class="relative reveal">
                    <div class="bg-white rounded-3xl p-8 text-center relative z-10 shadow-soft hover:shadow-card transition-shadow">
                        <div class="w-24 h-24 mx-auto mb-6 relative">
                            <div class="absolute inset-0 bg-brand-100 rounded-full"></div>
                            <div class="absolute inset-2 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-extrabold text-brand-500">03</span>
                            </div>
                        </div>
                        <div class="text-5xl mb-4">🚀</div>
                        <h3 class="text-xl font-bold text-dark mb-3">انطلق وابدأ</h3>
                        <p class="text-gray-600 leading-relaxed">
                            متجرك جاهز لاستقبال الطلبات فوراً - ابدأ البيع الآن!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         PRICING SECTION - الأسعار
         ============================================ -->
    <section id="pricing" class="py-20 lg:py-32 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
            <!-- العنوان -->
            <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                <span class="inline-block bg-brand-50 text-brand-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <i class="fa-solid fa-tags ml-1"></i>
                    الأسعار
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-dark mb-5">
                    خطط أسعار مرنة تناسب
                    <span class="text-brand-500">كل مطعم</span>
                </h2>
                <p class="text-lg text-gray-600">
                    ابدأ مجاناً وترقَّح حسب نمو عملك - الأسعار بالليرة السورية، بدون عقود طويلة أو رسوم خفية.
                </p>
            </div>

            <!-- البطاقات -->
            <div class="grid md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto items-stretch">
                
                <!-- الخطة الأساسية -->
                <div class="reveal bg-white rounded-3xl p-8 border-2 border-gray-100 hover:border-brand-200 transition-all flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-dark mb-2">الخطة الأساسية</h3>
                        <p class="text-gray-500 text-sm">مثالية للمطاعم الصغيرة</p>
                    </div>
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-extrabold text-dark">0</span>
                            <span class="text-xl text-gray-500">ل.س</span>
                        </div>
                        <p class="text-sm text-gray-400 mt-1">مجانية للأبد</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-grow">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700">حتى 20 منتج</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700">طلبات غير محدودة</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700">لوحة تحكم أساسية</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700">دعم عبر البريد</span>
                        </li>
                        <li class="flex items-start gap-3 opacity-40">
                            <i class="fa-solid fa-circle-xmark text-gray-300 mt-1"></i>
                            <span class="text-gray-400 line-through">نظام الكوبونات</span>
                        </li>
                    </ul>
                    <a href="#" class="block text-center bg-white hover:bg-soft-gray text-brand-500 font-bold py-4 rounded-2xl border-2 border-brand-500 hover:border-brand-600 transition-all">
                        ابدأ مجاناً
                    </a>
                </div>

                <!-- الخطة الاحترافية - الأكثر شعبية -->
                <div class="reveal bg-white rounded-3xl p-8 border-2 border-brand-500 shadow-glow relative flex flex-col md:scale-105 md:-my-4">
                    <!-- شريط الأكثر شعبية -->
                    <div class="absolute -top-4 right-1/2 translate-x-1/2 bg-brand-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg whitespace-nowrap">
                        <i class="fa-solid fa-fire ml-1"></i>
                        الأكثر شعبية
                    </div>
                    
                    <div class="mb-6 pt-2">
                        <h3 class="text-xl font-bold text-dark mb-2">الخطة الاحترافية</h3>
                        <p class="text-gray-500 text-sm">للمطاعم المتوسطة</p>
                    </div>
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-extrabold text-dark">199K</span>
                            <span class="text-xl text-gray-500">ل.س</span>
                            <span class="text-sm text-gray-400">/شهر</span>
                        </div>
                        <p class="text-sm text-brand-500 mt-1 font-semibold">وفّر 20% بالدفع السنوي</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-grow">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">منتجات غير محدودة</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">نظام عروض وكوبونات</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">تقييمات العملاء</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">تقارير متقدمة</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">دعم مباشر عبر الواتساب</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">نطاق مخصص مجاني (.sy)</span>
                        </li>
                    </ul>
                    <a href="#" class="block text-center bg-brand-500 hover:bg-brand-600 text-white font-bold py-4 rounded-2xl shadow-glow hover:shadow-2xl hover:-translate-y-0.5 transition-all">
                        ابدأ الآن
                    </a>
                </div>

                <!-- الخطة المميزة -->
                <div class="reveal bg-white rounded-3xl p-8 border-2 border-gray-100 hover:border-brand-200 transition-all flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-dark mb-2">الخطة المميزة</h3>
                        <p class="text-gray-500 text-sm">للسلاسل والمطاعم الكبيرة</p>
                    </div>
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-extrabold text-dark">399K</span>
                            <span class="text-xl text-gray-500">ل.س</span>
                            <span class="text-sm text-gray-400">/شهر</span>
                        </div>
                        <p class="text-sm text-gray-400 mt-1">حسب احتياجاتك</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-grow">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">كل مميزات الاحترافية</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">ثيمات مخصصة بالكامل</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">تطبيق جوال (PWA)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">أولوية في الدعم</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">مدير حساب خاص</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-success mt-1"></i>
                            <span class="text-gray-700 font-medium">فروع متعددة (دمشق، حلب...)</span>
                        </li>
                    </ul>
                    <a href="#contact" class="block text-center bg-white hover:bg-soft-gray text-brand-500 font-bold py-4 rounded-2xl border-2 border-brand-500 hover:border-brand-600 transition-all">
                        تواصل معنا
                    </a>
                </div>
            </div>

            <!-- ضمان -->
            <div class="text-center mt-12 reveal">
                <div class="inline-flex items-center gap-3 bg-soft-gray px-6 py-3 rounded-full">
                    <i class="fa-solid fa-shield-halved text-success text-xl"></i>
                    <span class="text-gray-700 font-medium">ضمان استرداد المبلغ خلال 30 يوماً - بدون أسئلة</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         TESTIMONIALS - آراء العملاء
         ============================================ -->
    <section class="py-20 lg:py-32 bg-soft-gray">
        <div class="container mx-auto px-4 lg:px-8">
            <!-- العنوان -->
            <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                <span class="inline-block bg-brand-50 text-brand-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <i class="fa-solid fa-heart ml-1"></i>
                    آراء العملاء
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-dark mb-5">
                    ماذا يقول <span class="text-brand-500">عملاؤنا</span>
                </h2>
                <p class="text-lg text-gray-600">
                    أكثر من 500 مطعم سوري يثقون بنا - اقرأ تجاربهم الحقيقية من دمشق وحلب وحمص واللاذقية.
                </p>
            </div>

            <!-- البطاقات -->
            <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                
                <!-- تقييم 1 - دمشق -->
                <div class="testimonial-card reveal bg-white rounded-3xl p-8">
                    <div class="flex text-warning text-lg mb-4">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-700 leading-relaxed mb-6 italic">
                        "المنصة غيرت طريقة إدارتنا للمطعم في دمشق. الطلبات زادت 40% في الشهر الأول! لوحة التحكم سهلة جداً وفريق الدعم متعاون بشكل لا يصدق."
                    </p>
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
                        <div class="w-14 h-14 bg-gradient-to-br from-brand-400 to-brand-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                            أ
                        </div>
                        <div>
                            <div class="font-bold text-dark">أحمد الحلبي</div>
                            <div class="text-sm text-gray-500">صاحب مطعم بيت الشام - دمشق</div>
                        </div>
                    </div>
                </div>

                <!-- تقييم 2 - حلب -->
                <div class="testimonial-card reveal bg-white rounded-3xl p-8">
                    <div class="flex text-warning text-lg mb-4">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-700 leading-relaxed mb-6 italic">
                        "سهلة الاستخدام جداً. فريق الدعم ممتاز ومتجاوب. استطعنا إطلاق متجرنا الإلكتروني في حلب في أقل من ساعة بدون أي خبرة تقنية سابقة."
                    </p>
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
                        <div class="w-14 h-14 bg-gradient-to-br from-brand-400 to-brand-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                            ن
                        </div>
                        <div>
                            <div class="font-bold text-dark">نورة الكواكبي</div>
                            <div class="text-sm text-gray-500">مديرة مطعم الذوق الحلبي - حلب</div>
                        </div>
                    </div>
                </div>

                <!-- تقييم 3 - حمص -->
                <div class="testimonial-card reveal bg-white rounded-3xl p-8">
                    <div class="flex text-warning text-lg mb-4">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-gray-700 leading-relaxed mb-6 italic">
                        "أفضل استثمار قمنا به لسلسلة مطاعمنا. النظام احترافي والمبيعات ارتفعت بشكل ملحوظ. نظام تتبع الطلبات المباشر وفر علينا الكثير من الوقت."
                    </p>
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
                        <div class="w-14 h-14 bg-gradient-to-br from-brand-400 to-brand-600 rounded-full flex items-center justify-center text-white font-bold text-xl">
                            خ
                        </div>
                        <div>
                            <div class="font-bold text-dark">خالد المصري</div>
                            <div class="text-sm text-gray-500">مالك سلسلة مشاوي الأصالة - حمص</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         FAQ - الأسئلة الشائعة
         ============================================ -->
    <section class="py-20 lg:py-32 bg-white">
        <div class="container mx-auto px-4 lg:px-8 max-w-4xl">
            <!-- العنوان -->
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-brand-50 text-brand-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
                    <i class="fa-solid fa-circle-question ml-1"></i>
                    الأسئلة الشائعة
                </span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-dark mb-5">
                    كل ما تريد <span class="text-brand-500">معرفته</span>
                </h2>
                <p class="text-lg text-gray-600">
                    إجابات على الأسئلة الأكثر شيوعاً - ولم تجد سؤالك؟ تواصل معنا.
                </p>
            </div>

            <!-- Accordion -->
            <div class="space-y-4">
                
                <!-- سؤال 1 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">هل يمكنني تجربة المنصة مجاناً؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            نعم، نوفر خطة مجانية دائمة مع مميزات أساسية تتيح لك تشغيل مطعمك بالكامل. يمكنك البدء فوراً بدون أي التزامات، والترفيع لاحقاً حسب احتياجاتك.
                        </p>
                    </div>
                </div>

                <!-- سؤال 2 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">هل أحتاج خبرة تقنية لاستخدام المنصة؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            لا أبداً، المنصة مصممة لتكون سهلة جداً. لا تحتاج أي خبرة تقنية أو برمجية. واجهة الاستخدام بديهية، ونوفر دروساً تعليمية ودعم مباشر عبر الواتساب لمساعدتك في كل خطوة.
                        </p>
                    </div>
                </div>

                <!-- سؤال 3 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">هل يمكنني نقل بياناتي إذا أردت إلغاء الاشتراك؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            نعم بالتأكيد، بياناتك ملكك. يمكنك تصدير كل بياناتك (المنتجات، الطلبات، العملاء) في أي وقت بصيغ متعددة. نحن لا نحتجز بياناتك أبداً.
                        </p>
                    </div>
                </div>

                <!-- سؤال 4 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">هل تدعمون الدفع الإلكتروني؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            نعم، ندعم الدفع عند الاستلام (Cash on Delivery) كخيار أساسي، بالإضافة إلى الدفع الإلكتروني عبر البطاقات والم المحافظ الإلكترونية المتاحة في السوق السوري. التكامل يتم بسهولة مع مزودي الخدمة المحليين.
                        </p>
                    </div>
                </div>

                <!-- سؤال 5 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">هل هناك عقد طويل الأمد؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            لا، لا توجد عقود طويلة الأمد. يمكنك الإلغاء في أي وقت بدون التزامات أو رسوم إلغاء. نؤمن بأنك تبقى معنا لأنك تريد ذلك، وليس لأنك مضطر.
                        </p>
                    </div>
                </div>

                <!-- سؤال 6 -->
                <div class="faq-item reveal bg-soft-gray rounded-2xl border border-gray-100 overflow-hidden">
                    <button class="faq-toggle w-full flex items-center justify-between p-6 text-right hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="font-bold text-dark text-lg">كيف يمكنني التواصل مع الدعم؟</span>
                        <span class="faq-icon w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand-500 font-bold flex-shrink-0 mr-4">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </button>
                    <div class="faq-content px-6">
                        <p class="text-gray-600 leading-relaxed pb-6">
                            نوفر دعم متعدد القنوات: البريد الإلكتروني، الواتساب (+963)، والهاتف المباشر خلال ساعات العمل (8 ص - 11 م بتوقيت دمشق). مشتركو الخطة الاحترافية والمميزة يحصلون على دعم 24/7 مع أولوية في الرد.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         CTA - دعوة للعمل
         ============================================ -->
    <section class="py-20 lg:py-32 cta-gradient relative">
        <div class="container mx-auto px-4 lg:px-8 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-semibold mb-6 border border-white/30">
                    <i class="fa-solid fa-gift"></i>
                    <span>عرض محدود - شهر مجاني إضافي</span>
                </div>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-6 leading-tight">
                    جاهز لتطوير مطعمك؟
                </h2>
                <p class="text-lg lg:text-xl text-white/90 mb-10 max-w-2xl mx-auto leading-relaxed">
                    انضم لأكثر من 500 مطعم سوري يستخدمون منصتنا يومياً من دمشق إلى اللاذقية. ابدأ تجربتك المجانية اليوم.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#pricing" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-soft-gray text-brand-500 font-bold px-8 py-4 rounded-2xl shadow-2xl hover:-translate-y-1 transition-all text-lg">
                        <span>ابدأ تجربتك المجانية</span>
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <a href="#contact" class="inline-flex items-center justify-center gap-2 bg-transparent hover:bg-white/10 text-white font-bold px-8 py-4 rounded-2xl border-2 border-white/50 hover:border-white transition-all text-lg">
                        <i class="fa-solid fa-phone"></i>
                        <span>تواصل معنا</span>
                    </a>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-6 mt-10 text-white/80 text-sm">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-white"></i>
                        بدون التزامات
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-white"></i>
                        إعداد خلال 10 دقائق
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-white"></i>
                        إلغاء في أي وقت
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         FOOTER - التذييل
         ============================================ -->
    <footer id="contact" class="bg-dark text-white pt-16 pb-8">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                
                <!-- العمود 1: عن المنصة -->
                <div class="lg:col-span-2">
                    <a href="#" class="flex items-center gap-2 mb-5">
                        <div class="w-11 h-11 bg-brand-500 rounded-2xl flex items-center justify-center">
                            <i class="fa-solid fa-utensils text-white text-lg"></i>
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-xl font-bold">منصة المطاعم</span>
                            <span class="text-[10px] text-gray-400 -mt-1">سوريا • Restaurant Platform</span>
                        </div>
                    </a>
                    <p class="text-gray-400 leading-relaxed mb-6 max-w-sm">
                        المنصة السورية المتكاملة التي تساعد أصحاب المطاعم على بناء متاجرهم الإلكترونية وإدارة عملياتهم بكفاءة واحترافية.
                    </p>
                    <!-- معلومات التواصل -->
                    <div class="space-y-2 mb-6 text-sm text-gray-400">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-brand-500"></i>
                            <span>دمشق، سوريا - شارع بغداد</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-phone text-brand-500"></i>
                            <span dir="ltr">+963 11 123 4567</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-brand-500"></i>
                            <span dir="ltr">+963 944 123 456</span>
                        </div>
                    </div>
                    <!-- وسائل التواصل -->
                    <div class="flex gap-3">
                        <a href="#" aria-label="فيسبوك" class="w-10 h-10 bg-white/5 hover:bg-brand-500 rounded-xl flex items-center justify-center transition-all">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="إنستغرام" class="w-10 h-10 bg-white/5 hover:bg-brand-500 rounded-xl flex items-center justify-center transition-all">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="واتساب" class="w-10 h-10 bg-white/5 hover:bg-brand-500 rounded-xl flex items-center justify-center transition-all">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <a href="#" aria-label="تيليغرام" class="w-10 h-10 bg-white/5 hover:bg-brand-500 rounded-xl flex items-center justify-center transition-all">
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                    </div>
                </div>

                <!-- العمود 2: المنتج -->
                <div>
                    <h4 class="font-bold text-lg mb-5">المنتج</h4>
                    <ul class="space-y-3">
                        <li><a href="#features" class="text-gray-400 hover:text-brand-500 transition-colors">المميزات</a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-brand-500 transition-colors">الأسعار</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">الأسئلة الشائعة</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">العرض التوضيحي</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">التحديثات</a></li>
                    </ul>
                </div>

                <!-- العمود 3: الشركة -->
                <div>
                    <h4 class="font-bold text-lg mb-5">الشركة</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">من نحن</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">المدونة</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">الوظائف</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">تواصل معنا</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">الشركاء</a></li>
                    </ul>
                </div>

                <!-- العمود 4: قانوني -->
                <div>
                    <h4 class="font-bold text-lg mb-5">قانوني</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">سياسة الخصوصية</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">الشروط والأحكام</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">سياسة الإلغاء</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">ملفات الارتباط</a></li>
                    </ul>
                </div>
            </div>

            <!-- الخط الفاصل -->
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-400 text-sm">
                    © 2024 منصة المطاعم السورية. جميع الحقوق محفوظة.
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-400">
                    <span>صنع بـ <i class="fa-solid fa-heart text-brand-500"></i> في سوريا 🇸🇾</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============================================
         JavaScript - التفاعلات
         ============================================ -->
    <script>
        // ========== Mobile Menu Toggle ==========
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            const isOpen = mobileMenu.classList.toggle('open');
            menuToggle.setAttribute('aria-expanded', isOpen);
            menuIcon.className = isOpen ? 'fa-solid fa-xmark text-xl text-dark' : 'fa-solid fa-bars text-xl text-dark';
        });

        // إغلاق القائمة عند النقر على رابط
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuIcon.className = 'fa-solid fa-bars text-xl text-dark';
            });
        });

        // ========== Header Shadow on Scroll ==========
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('shadow-soft');
            } else {
                header.classList.remove('shadow-soft');
            }
        });

        // ========== FAQ Accordion ==========
        document.querySelectorAll('.faq-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const item = toggle.parentElement;
                const isActive = item.classList.contains('active');
                const expanded = !isActive;

                // إغلاق جميع العناصر الأخرى
                document.querySelectorAll('.faq-item').forEach(other => {
                    other.classList.remove('active');
                    other.querySelector('.faq-toggle').setAttribute('aria-expanded', 'false');
                });

                // تبديل العنصر الحالي
                if (!isActive) {
                    item.classList.add('active');
                }
                toggle.setAttribute('aria-expanded', expanded);
            });
        });

        // ========== Scroll Reveal Animation ==========
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // ========== Smooth Scroll for Anchor Links ==========
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    const headerHeight = header.offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
