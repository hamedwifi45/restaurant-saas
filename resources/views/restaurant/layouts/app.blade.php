<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'لوحة تحكم المطعم' }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="flex min-h-screen">
        <!-- القائمة الجانبية -->
        <aside class="w-64 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 fixed h-full overflow-y-auto">
            <div class="p-6">
                <!-- شعار المطعم -->
                @if($restaurant?->logo)
                    <img src="{{ Storage::url($restaurant->logo) }}" alt="{{ $restaurant->name }}" class="h-12 w-auto mb-4">
                @else
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ $restaurant->name ?? 'مطعمي' }}</h1>
                @endif
                
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">لوحة التحكم</p>
            </div>

            <nav class="mt-6 px-3 space-y-1">
                <!-- لوحة التحكم الرئيسية -->
                <a href="{{ route('restaurant.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.dashboard') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    الرئيسية
                </a>

                <!-- الطلبات -->
                <a href="{{ route('restaurant.orders') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.orders') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    الطلبات
                    @php
                        $pendingCount = \App\Models\Order::where('restaurant_id', $restaurant->id)->where('status', 'pending')->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="mr-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </a>

                <!-- المنتجات -->
                <a href="{{ route('restaurant.products') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.products') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    المنتجات
                </a>

                <!-- التصنيفات -->
                <a href="{{ route('restaurant.categories') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.categories') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    التصنيفات
                </a>

                <!-- العروض -->
                <a href="{{ route('restaurant.offers') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.offers') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                    </svg>
                    العروض
                </a>

                <!-- أكواد الخصم -->
                <a href="{{ route('restaurant.coupons') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.coupons') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    أكواد الخصم
                </a>

                <!-- التقييمات -->
                <a href="{{ route('restaurant.reviews') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.reviews') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    التقييمات
                </a>

                <!-- الإعدادات -->
                <a href="{{ route('restaurant.settings') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.settings') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    الإعدادات
                </a>

                <!-- تخصيص الثيم -->
                <a href="{{ route('restaurant.theme-customizer') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('restaurant.theme-customizer') 
                              ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400' 
                              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    تخصيص الثيم
                </a>
            </nav>

            <!-- الفاصل -->
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

            <!-- العودة للموقع -->
            <div class="px-3">
                <a href="{{ url('/') }}" target="_blank" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    العودة للموقع
                </a>
            </div>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="mr-64 flex-1 p-8">
            <!-- الشريط العلوي -->
            <header class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title ?? 'لوحة التحكم' }}</h1>
                        @if(isset($subtitle))
                            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- زر الوضع الليلي -->
                        <flux:button variant="ghost" wire:click="$toggleDarkMode">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </flux:button>

                        <!-- قائمة المستخدم -->
                        <flux:dropdown>
                            <flux:button variant="ghost" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                            </flux:button>
                            
                            <flux:menu>
                                <flux:menu.radio.group>
                                    <flux:menu.item href="{{ route('profile.edit') }}" icon="user">
                                        الملف الشخصي
                                    </flux:menu.item>
                                </flux:menu.radio.group>
                                
                                <flux:menu.separator />
                                
                                <flux:menu.item method="post" href="{{ route('logout') }}" icon="arrow-right-start-on-rectangle">
                                    تسجيل الخروج
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </header>

            <!-- محتوى الصفحة -->
            <div>
                {{ $slot }}
            </div>
        </main>
    </div>

    @fluxScripts
</body>
</html>
