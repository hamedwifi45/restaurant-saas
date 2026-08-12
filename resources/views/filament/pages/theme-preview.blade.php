<x-filament-panels::page>
    <div class="space-y-6">
        <!-- رأس الصفحة مع معلومات الثيم -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
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
                    @if(!$theme->is_active)
                    <form action="{{ route('themes.activate', $theme) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium shadow-sm">
                            <x-heroicon-o-check-circle class="w-5 h-5 inline-block mr-1" />
                            تفعيل الثيم
                        </button>
                    </form>
                    @else
                    <span class="px-4 py-2.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg font-medium flex items-center gap-1">
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        مفعل حالياً
                    </span>
                    @endif
                    
                    <a href="{{ \Filament\Facades\Filament::getUrl() }}/themes" 
                       class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                        ← العودة للقائمة
                    </a>
                </div>
            </div>

            <!-- معلومات الثيم -->
            <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800/50">
                <div class="p-3 bg-white dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-code-bracket class="w-4 h-4 text-blue-500" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">الإصدار</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $theme->version ?? '1.0.0' }}</span>
                </div>
                <div class="p-3 bg-white dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-user class="w-4 h-4 text-purple-500" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">المطور</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $theme->author ?? 'غير معروف' }}</span>
                </div>
                <div class="p-3 bg-white dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-arrow-path class="w-4 h-4 text-{{ $theme->is_active ? 'green' : 'gray' }}-500" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">الحالة</span>
                    </div>
                    <span class="font-semibold {{ $theme->is_active ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $theme->is_active ? 'مفعل' : 'غير مفعل' }}
                    </span>
                </div>
                <div class="p-3 bg-white dark:bg-gray-700/50 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-building-storefront class="w-4 h-4 text-orange-500" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">المطاعم المستخدمة</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $theme->restaurants()->count() }}</span>
                </div>
            </div>
        </div>

        <!-- إطار المعاينة الحية -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 border-b border-gray-200 dark:border-gray-600 flex items-center gap-3">
                <div class="flex gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500 shadow-sm"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500 shadow-sm"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500 shadow-sm"></div>
                </div>
                <div class="flex-1 text-center">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">🔍 معاينة حية - {{ $theme->name }}</span>
                </div>
                <div class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                    {{ $theme->folder_name }}
                </div>
            </div>

            <!-- محتوى المعاينة -->
            <div class="relative bg-gray-100 dark:bg-gray-900">
                <iframe 
                    src="{{ url('/' . request()->user()->restaurant->subdomain . '?preview_theme=' . $theme->folder_name) }}"
                    class="w-full h-[800px] border-0"
                    title="Theme Preview"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    id="theme-preview-iframe"
                ></iframe>
                
                <!-- طبقة تحميل -->
                <div id="loading-overlay" class="absolute inset-0 bg-white/90 dark:bg-gray-900/90 flex items-center justify-center z-10">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent mx-auto mb-4"></div>
                        <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">جاري تحميل المعاينة...</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">يرجى الانتظار ريثما يتم تحميل الثيم</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إعدادات الثيم الافتراضية -->
        @if($settings && count($settings) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-blue-600" />
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        الإعدادات الافتراضية للثيم
                    </h3>
                </div>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($settings as $key => $value)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 font-medium">
                            {{ str_replace('_', ' ', $key) }}
                        </dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                            @if(is_bool($value))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $value ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                <x-heroicon-o-{{ $value ? 'check-circle' : 'x-circle' }} class="w-4 h-4" />
                                {{ $value ? 'نعم' : 'لا' }}
                            </span>
                            @elseif(is_array($value))
                            <span class="text-gray-500 dark:text-gray-400">مصفوفة ({{ count($value) }} عناصر)</span>
                            @else
                            @if(str_contains($value, '#'))
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 shadow-sm" style="background-color: {{ $value }}"></div>
                                <code class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">{{ $value }}</code>
                            </div>
                            @else
                            {{ is_string($value) && strlen($value) > 50 ? substr($value, 0, 50).'...' : $value }}
                            @endif
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-variable class="w-6 h-6 text-purple-600" />
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        المتغيرات المسموحة للتخصيص
                    </h3>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">المتغير</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">النوع</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">القيمة الافتراضية</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">الوصف</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($theme->allowed_variables as $key => $config)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ $key }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if(is_array($config))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ $config['type'] ?? 'mixed' }}
                                    </span>
                                    @else
                                    <span class="text-gray-500 dark:text-gray-400">mixed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if(is_array($config))
                                        @if(isset($config['default']) && str_contains($config['default'], '#'))
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded border border-gray-300 dark:border-gray-600" style="background-color: {{ $config['default'] }}"></div>
                                            <code class="text-xs">{{ $config['default'] }}</code>
                                        </div>
                                        @else
                                        {{ $config['default'] ?? '-' }}
                                        @endif
                                    @else
                                    {{ $config ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ is_array($config) ? ($config['label'] ?? '-') : '-' }}
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
</x-filament-panels::page>

@push('scripts')
<script>
    // إخفاء طبقة التحميل عند تحميل الإطار
    document.getElementById('theme-preview-iframe').addEventListener('load', function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }
        
        // محاولة تحديث ارتفاع الإطار تلقائياً
        try {
            const newHeight = Math.max(800, this.contentWindow.document.documentElement.scrollHeight);
            this.style.height = newHeight + 'px';
        } catch (e) {
            // Cross-origin restriction - لا يمكن الوصول للمحتوى
            console.log('تعذر ضبط الارتفاع التلقائي بسبب قيود الأمان');
        }
    });

    // معالجة أخطاء التحميل
    document.getElementById('theme-preview-iframe').addEventListener('error', function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="text-red-500 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-red-600">فشل تحميل المعاينة</p>
                    <p class="text-sm text-gray-500 mt-2">يرجى التأكد من وجود صفحات الثيم</p>
                </div>
            `;
        }
    });
</script>
@endpush
