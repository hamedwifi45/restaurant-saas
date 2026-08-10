<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800" dir="rtl">
    <div class="container mx-auto px-4 py-8">
        {{-- رأس الصفحة --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white mb-2">
                🎨 تخصيص الثيم
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                قم بتخصيص مظهر مطعمك بشكل فريد مع معاينة مباشرة للتغييرات
            </p>
        </div>

        {{-- رسالة النجاح --}}
        @if($saveMessage)
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-init="setTimeout(() => { 
                     @this.dispatch('clear-message'); 
                     show = false; 
                 }, 3000)"
                 class="mb-6 p-4 bg-green-100 dark:bg-green-900 border-r-4 border-green-500 text-green-700 dark:text-green-300 rounded-lg shadow-lg animate-pulse">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="font-semibold">{{ $saveMessage }}</span>
                    </div>
                    <button @click="show = false" class="text-green-500 hover:text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- لوحة التحكم الجانبية --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- اختيار الثيم --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        اختر الثيم
                    </h2>
                    
                    <div class="space-y-3">
                        @foreach($availableThemes as $themeData)
                            <div wire:click="$set('selectedThemeId', {{ $themeData['id'] }}); selectTheme({{ $themeData['id'] }})"
                                 class="cursor-pointer p-4 rounded-xl border-2 transition-all duration-300 transform hover:scale-105
                                        {{ $selectedThemeId == $themeData['id'] 
                                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-lg' 
                                            : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700' }}">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                                        {{ substr($themeData['name'], 0, 1) }}
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $themeData['name'] }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $themeData['slug'] }}</p>
                                    </div>
                                    @if($selectedThemeId == $themeData['id'])
                                        <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($theme)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button wire:click="applyTheme"
                                    class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-300">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    تطبيق الثيم
                                </span>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- إعدادات الألوان --}}
                @if(!empty($allowedVariables))
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                            </svg>
                            تخصيص الألوان والإعدادات
                        </h2>

                        <div class="space-y-4">
                            @foreach($allowedVariables as $key => $config)
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $config['label'] ?? $key }}
                                    </label>

                                    @if($config['type'] === 'color')
                                        <div class="relative">
                                            <input type="color" 
                                                   wire:model.live="settings.{{ $key }}"
                                                   wire:change="updateSetting('{{ $key }}', $event.target.value)"
                                                   value="{{ $settings[$key] ?? $config['default'] }}"
                                                   class="w-full h-12 rounded-lg cursor-pointer border-2 border-gray-300 dark:border-gray-600 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                                                <span class="text-xs font-mono text-gray-600 dark:text-gray-400">
                                                    {{ $settings[$key] ?? $config['default'] }}
                                                </span>
                                            </div>
                                        </div>

                                    @elseif($config['type'] === 'select')
                                        <select wire:model.live="settings.{{ $key }}"
                                                wire:change="updateSetting('{{ $key }}', $event.target.value)"
                                                class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                            @foreach($config['options'] as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>

                                    @else
                                        <input type="text" 
                                               wire:model.live="settings.{{ $key }}"
                                               wire:change="updateSetting('{{ $key }}', $event.target.value)"
                                               value="{{ $settings[$key] ?? $config['default'] }}"
                                               class="w-full px-4 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- أزرار التحكم --}}
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                            <button wire:click="resetToDefaults"
                                    class="flex-1 py-3 px-4 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold rounded-xl transition-all duration-300">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    إعادة للافتراضي
                                </span>
                            </button>
                            <button wire:click="saveSettings"
                                    class="flex-1 py-3 px-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-300">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    حفظ التغييرات
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- منطقة المعاينة --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 sticky top-4">
                    {{-- شريط المعاينة --}}
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            معاينة مباشرة
                        </h2>
                        <a href="{{ $previewUrl }}" target="_blank" 
                           class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            فتح في صفحة جديدة
                        </a>
                    </div>

                    {{-- إطار المعاينة --}}
                    <div class="relative" style="height: 700px;">
                        <iframe src="{{ $previewUrl }}" 
                                id="preview-frame"
                                class="w-full h-full border-0"
                                onload="injectCustomStyles()">
                        </iframe>

                        {{-- مؤشر التحميل --}}
                        <div wire:loading.class.remove="hidden" 
                             class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center z-10 hidden">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600 mx-auto mb-4"></div>
                                <p class="text-gray-600 dark:text-gray-400 font-semibold">جاري تحميل المعاينة...</p>
                            </div>
                        </div>
                    </div>

                    {{-- تعليمات --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p class="font-semibold mb-1">💡 نصيحة:</p>
                                <p>قم بتغيير الإعدادات في اللوحة الجانبية وسترى التغييرات تنعكس فوراً في المعاينة. لا تنسَ حفظ تغييراتك قبل المغادرة!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function injectCustomStyles() {
        const frame = document.getElementById('preview-frame');
        if (!frame) return;

        const iframeDoc = frame.contentDocument || frame.contentWindow.document;
        
        // الاستماع لتحديثات الإعدادات من Livewire
        @this.on('setting-updated', (event) => {
            applyStyles(iframeDoc, event.key, event.value);
        });

        @this.on('settings-reset', () => {
            location.reload();
        });

        @this.on('theme-changed', () => {
            setTimeout(() => {
                location.reload();
            }, 500);
        });

        @this.on('clear-message', () => {
            $wire.saveMessage = '';
        });
    }

    function applyStyles(doc, key, value) {
        if (!doc) return;

        let styleElement = doc.getElementById('custom-theme-styles');
        if (!styleElement) {
            styleElement = doc.createElement('style');
            styleElement.id = 'custom-theme-styles';
            doc.head.appendChild(styleElement);
        }

        // تحديث المتغيرات CSS بناءً على نوع الإعداد
        let cssText = '';
        
        if (key.includes('color')) {
            cssText = `
                :root {
                    --${key}: ${value} !important;
                }
                
                /* تطبيق الألوان على العناصر الشائعة */
                ${key === 'primary_color' ? `
                    .btn-primary, .bg-primary, a, .text-primary {
                        background-color: ${value} !important;
                        color: ${value} !important;
                    }
                ` : ''}
                
                ${key === 'background_color' ? `
                    body, .bg-background {
                        background-color: ${value} !important;
                    }
                ` : ''}
            `;
        }

        if (key === 'font_family') {
            cssText += `
                * {
                    font-family: '${value}', sans-serif !important;
                }
            `;
        }

        if (key === 'border_radius') {
            cssText += `
                button, .card, .input, .btn {
                    border-radius: ${value} !important;
                }
            `;
        }

        styleElement.textContent = cssText;
    }

    // تطبيق الأنماط الأولية عند التحميل
    document.addEventListener('DOMContentLoaded', () => {
        injectCustomStyles();
    });
</script>
