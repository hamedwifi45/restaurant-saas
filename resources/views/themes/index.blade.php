@extends('layouts.app')

@section('title', 'إدارة الثيمات')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-8">
    <div class="container mx-auto px-4">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                            إدارة الثيمات
                        </span>
                    </h1>
                    <p class="text-gray-400">تحكم في ثيمات منصتك وأضف تصاميم جديدة</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.themes.upload') }}" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-medium rounded-xl transition-all duration-300 shadow-lg shadow-amber-500/25">
                        <flux:icon.cloud-arrow-up class="w-5 h-5 ml-2" />
                        رفع ثيم جديد
                    </a>
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all duration-300 backdrop-blur-sm">
                        <flux:icon.arrow-left class="w-5 h-5 ml-2" />
                        العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>

        {{-- Themes Grid --}}
        @if($themes->count() > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($themes as $theme)
                    <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 overflow-hidden hover:border-amber-500/50 transition-all duration-300 group">
                        {{-- Preview Image --}}
                        <div class="relative h-48 bg-gradient-to-br from-gray-800 to-gray-900 overflow-hidden">
                            @if($theme->preview_image)
                                <img src="{{ asset('storage/' . $theme->preview_image) }}" 
                                     alt="{{ $theme->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon.paint-brush class="w-16 h-16 text-gray-600" />
                                </div>
                            @endif
                            
                            {{-- Status Badge --}}
                            <div class="absolute top-3 right-3">
                                @if($theme->is_active)
                                    <span class="px-3 py-1 bg-green-500/90 text-white text-xs font-medium rounded-full">
                                        نشط
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-500/90 text-white text-xs font-medium rounded-full">
                                        غير نشط
                                    </span>
                                @endif
                            </div>
                            
                            @if($theme->is_default)
                                <div class="absolute bottom-3 right-3">
                                    <span class="px-3 py-1 bg-amber-500/90 text-white text-xs font-medium rounded-full">
                                        افتراضي
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Content --}}
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white mb-1">{{ $theme->name }}</h3>
                                    <p class="text-gray-400 text-sm">الإصدار: {{ $theme->version }}</p>
                                </div>
                            </div>
                            
                            @if($theme->description)
                                <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $theme->description }}</p>
                            @endif
                            
                            <div class="flex items-center text-gray-500 text-sm mb-4">
                                <flux:icon.user class="w-4 h-4 ml-1" />
                                <span>{{ $theme->author }}</span>
                            </div>
                            
                            {{-- Stats --}}
                            <div class="flex items-center justify-between py-3 border-t border-white/10">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-amber-500">{{ $theme->restaurants()->count() }}</p>
                                    <p class="text-xs text-gray-500">مطعم يستخدمه</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-purple-500">{{ count($theme->default_settings ?? []) }}</p>
                                    <p class="text-xs text-gray-500">إعدادات</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-blue-500">{{ count($theme->allowed_variables ?? []) }}</p>
                                    <p class="text-xs text-gray-500">متغيرات</p>
                                </div>
                            </div>
                            
                            {{-- Actions --}}
                            <div class="flex gap-2 mt-4">
                                <a href="{{ route('themes.show', $theme) }}" 
                                   class="flex-1 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-lg transition-all duration-300 text-center">
                                    عرض التفاصيل
                                </a>
                                @if(!$theme->is_default)
                                    <form action="{{ route('themes.destroy', $theme) }}" method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد من حذف هذا الثيم؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm font-medium rounded-lg transition-all duration-300">
                                            حذف
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
            <div class="mt-8">
                {{ $themes->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-12 text-center">
                <flux:icon.paint-brush class="w-20 h-20 text-gray-600 mx-auto mb-4" />
                <h3 class="text-2xl font-bold text-white mb-2">لا توجد ثيمات حالياً</h3>
                <p class="text-gray-400 mb-6">ابدأ بإضافة أول ثيم لمنصتك</p>
                <a href="{{ route('admin.themes.upload') }}" 
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-medium rounded-xl transition-all duration-300">
                    <flux:icon.cloud-arrow-up class="w-5 h-5 ml-2" />
                    رفع ثيم جديد
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
