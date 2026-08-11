@extends('layouts.app')

@section('title', $theme->name . ' - تفاصيل الثيم')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-8">
    <div class="container mx-auto px-4">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                            {{ $theme->name }}
                        </span>
                    </h1>
                    <p class="text-gray-400">تفاصيل ومعلومات الثيم</p>
                </div>
                <a href="{{ route('themes.index') }}" 
                   class="inline-flex items-center px-6 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all duration-300 backdrop-blur-sm">
                    <flux:icon.arrow-left class="w-5 h-5 ml-2" />
                    العودة للقائمة
                </a>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Main Info --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Preview Card --}}
                <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 overflow-hidden">
                    <div class="relative h-64 bg-gradient-to-br from-gray-800 to-gray-900">
                        @if($theme->preview_image)
                            <img src="{{ asset('storage/' . $theme->preview_image) }}" 
                                 alt="{{ $theme->name }}" 
                                 class="w-full h-full object-cover" />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <flux:icon.paint-brush class="w-24 h-24 text-gray-600" />
                            </div>
                        @endif
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            @if($theme->is_active)
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm font-medium rounded-full">
                                    نشط
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-500/20 text-gray-400 text-sm font-medium rounded-full">
                                    غير نشط
                                </span>
                            @endif
                            @if($theme->is_default)
                                <span class="px-3 py-1 bg-amber-500/20 text-amber-400 text-sm font-medium rounded-full">
                                    افتراضي
                                </span>
                            @endif
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-400 text-sm font-medium rounded-full">
                                v{{ $theme->version }}
                            </span>
                        </div>
                        
                        @if($theme->description)
                            <p class="text-gray-300 mb-4">{{ $theme->description }}</p>
                        @endif
                        
                        <div class="flex items-center text-gray-400 text-sm">
                            <flux:icon.user class="w-4 h-4 ml-2" />
                            <span>المطور: {{ $theme->author }}</span>
                        </div>
                    </div>
                </div>

                {{-- Default Settings --}}
                @if($theme->default_settings && count($theme->default_settings) > 0)
                    <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                        <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <flux:icon.cog class="w-6 h-6 ml-2 text-amber-500" />
                            الإعدادات الافتراضية
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($theme->default_settings as $key => $value)
                                <div class="bg-gray-800/50 rounded-xl p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-400 text-sm">{{ $key }}</span>
                                    </div>
                                    @if(is_string($value) && str_starts_with($value, '#'))
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 rounded-lg mr-2 border border-white/20" style="background-color: {{ $value }}"></div>
                                            <code class="text-white text-sm">{{ $value }}</code>
                                        </div>
                                    @elseif(is_bool($value))
                                        <span class="text-{{ $value ? 'green' : 'red' }}-400 text-sm">
                                            {{ $value ? '✓ نعم' : '✗ لا' }}
                                        </span>
                                    @else
                                        <code class="text-white text-sm">{{ $value }}</code>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Allowed Variables --}}
                @if($theme->allowed_variables && count($theme->allowed_variables) > 0)
                    <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                        <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <flux:icon.variable class="w-6 h-6 ml-2 text-blue-500" />
                            المتغيرات المسموحة
                        </h3>
                        <div class="space-y-4">
                            @foreach($theme->allowed_variables as $key => $config)
                                <div class="bg-gray-800/50 rounded-xl p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-white font-medium">{{ $key }}</span>
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-lg">
                                            {{ $config['type'] ?? 'unknown' }}
                                        </span>
                                    </div>
                                    @if(isset($config['label']))
                                        <p class="text-gray-400 text-sm mb-2">{{ $config['label'] }}</p>
                                    @endif
                                    @if(isset($config['description']))
                                        <p class="text-gray-500 text-xs">{{ $config['description'] }}</p>
                                    @endif
                                    @if(isset($config['options']) && is_array($config['options']))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($config['options'] as $optKey => $optValue)
                                                <span class="px-2 py-1 bg-gray-700/50 text-gray-300 text-xs rounded">
                                                    {{ $optKey }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Stats Card --}}
                <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                    <h3 class="text-xl font-bold text-white mb-4">إحصائيات</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-xl">
                            <div class="flex items-center">
                                <flux:icon.building-storefront class="w-5 h-5 text-amber-500 ml-2" />
                                <span class="text-gray-400 text-sm">المطاعم المستخدمة</span>
                            </div>
                            <span class="text-2xl font-bold text-white">{{ $theme->restaurants()->count() }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-xl">
                            <div class="flex items-center">
                                <flux:icon.cog class="w-5 h-5 text-purple-500 ml-2" />
                                <span class="text-gray-400 text-sm">عدد الإعدادات</span>
                            </div>
                            <span class="text-2xl font-bold text-white">{{ count($theme->default_settings ?? []) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-xl">
                            <div class="flex items-center">
                                <flux:icon.variable class="w-5 h-5 text-blue-500 ml-2" />
                                <span class="text-gray-400 text-sm">المتغيرات المسموحة</span>
                            </div>
                            <span class="text-2xl font-bold text-white">{{ count($theme->allowed_variables ?? []) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions Card --}}
                <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-6">
                    <h3 class="text-xl font-bold text-white mb-4">إجراءات</h3>
                    <div class="space-y-3">
                        @if(!$theme->is_default)
                            <form action="{{ route('themes.destroy', $theme) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الثيم؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full px-4 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-medium rounded-xl transition-all duration-300 flex items-center justify-center">
                                    <flux:icon.trash class="w-5 h-5 ml-2" />
                                    حذف الثيم
                                </button>
                            </form>
                        @else
                            <div class="w-full px-4 py-3 bg-amber-500/10 text-amber-400 font-medium rounded-xl text-center">
                                لا يمكن حذف الثيم الافتراضي
                            </div>
                        @endif
                        
                        <a href="{{ route('themes.index') }}" 
                           class="block w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all duration-300 text-center">
                            العودة للقائمة
                        </a>
                    </div>
                </div>

                {{-- Theme Info Card --}}
                <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/10 border border-amber-500/50 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-amber-400 mb-4">معلومات الثيم</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">اسم المجلد:</span>
                            <code class="text-white">{{ $theme->folder_name }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Slug:</span>
                            <code class="text-white">{{ $theme->slug }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">تاريخ الإنشاء:</span>
                            <span class="text-white">{{ $theme->created_at->format('Y-m-d') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">آخر تحديث:</span>
                            <span class="text-white">{{ $theme->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
