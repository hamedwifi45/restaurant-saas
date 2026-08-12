@extends('layouts.app')

@section('title', 'معاينة الثيم - '.$theme->name)

@section('content')
<div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- رأس الصفحة مع أزرار التحكم -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        معاينة الثيم: {{ $theme->name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $theme->description ?? 'لا يوجد وصف' }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('themes.index') }}" 
                       class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        ← العودة للقائمة
                    </a>
                    
                    @if(!$theme->is_active)
                    <form action="{{ route('themes.activate', $theme) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            تفعيل الثيم
                        </button>
                    </form>
                    @else
                    <span class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg font-medium">
                        ✓ مفعل حالياً
                    </span>
                    @endif
                </div>
            </div>

            <!-- معلومات الثيم -->
            <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800/50">
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">الإصدار</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $theme->version ?? '1.0.0' }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">المطور</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $theme->author ?? 'غير معروف' }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">حالة الثيم</span>
                    <span class="font-medium {{ $theme->is_active ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $theme->is_active ? 'مفعل' : 'غير مفعل' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">عدد المطاعم المستخدمة</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $theme->restaurants()->count() }}</span>
                </div>
            </div>
        </div>

        <!-- إطار المعاينة الحية -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 flex items-center gap-2">
                <div class="flex gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <div class="flex-1 text-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">معاينة حية - {{ $theme->name }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $theme->folder_name }}
                </div>
            </div>

            <!-- محتوى المعاينة -->
            <div class="relative">
                <iframe 
                    src="{{ route('restaurant.public.home', ['preview_theme' => $theme->folder_name]) }}"
                    class="w-full h-[800px] border-0"
                    title="Theme Preview"
                    sandbox="allow-scripts allow-same-origin allow-forms"
                ></iframe>
                
                <!-- طبقة تحميل -->
                <div id="loading-overlay" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                        <p class="text-gray-600 dark:text-gray-400">جاري تحميل المعاينة...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إعدادات الثيم الافتراضية -->
        @if($settings && count($settings) > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    الإعدادات الافتراضية للثيم
                </h3>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($settings as $key => $value)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                            {{ str_replace('_', ' ', $key) }}
                        </dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            @if(is_bool($value))
                                <span class="{{ $value ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $value ? 'نعم' : 'لا' }}
                                </span>
                            @elseif(is_array($value))
                                <span class="text-gray-500">مصفوفة ({{ count($value) }} عناصر)</span>
                            @else
                                {{ is_string($value) && strlen($value) > 50 ? substr($value, 0, 50).'...' : $value }}
                            @endif
                        </dd>
                    </div>
                    @endforeach
                </dl>
            </div>
        </div>
        @endif

        <!-- المتغيرات المسموحة -->
        @if($theme->allowed_variables && count($theme->allowed_variables) > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    المتغيرات المسموحة للتخصيص
                </h3>
            </div>
            <div class="px-6 py-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">المتغير</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">النوع</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">القيمة الافتراضية</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الوصف</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($theme->allowed_variables as $key => $config)
                            <tr>
                                <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">
                                    {{ $key }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ is_array($config) ? ($config['type'] ?? 'mixed') : 'mixed' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ is_array($config) ? ($config['default'] ?? '-') : ($config ?? '-') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ is_array($config) ? ($config['description'] ?? '-') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // إخفاء طبقة التحميل عند تحميل الإطار
    document.querySelector('iframe').addEventListener('load', function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }
    });

    // تحديث ارتفاع الإطار تلقائياً
    document.querySelector('iframe').addEventListener('load', function() {
        try {
            this.style.height = Math.max(800, this.contentWindow.document.documentElement.scrollHeight) + 'px';
        } catch (e) {
            // Cross-origin restriction
        }
    });
</script>
@endpush
@endsection
