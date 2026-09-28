<x-filament-panels::page>
    @php
        $restaurant = auth()->user()?->restaurant;
        $theme = $restaurant?->theme;
    @endphp

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">تعديل القيم المسموح بها من ملف theme.json لمعاينة وحفظ مظهر الموقع.</p>
        </div>

        @if($theme)
            <span class="inline-flex items-center rounded-full bg-orange-100 dark:bg-orange-950/50 px-3 py-1 text-sm font-bold text-orange-600 dark:text-orange-400">
                الثيم النشط: {{ $theme->name }}
            </span>
        @endif
    </div>

    @if(! $restaurant || ! $theme)
        <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-8 text-center">
            <p class="text-lg font-bold text-gray-700 dark:text-gray-300">لم يتم العثور على مطعم أو ثيم مفعّل.</p>
            <p class="mt-2 text-sm text-gray-500">قم بتعيين ثيم للمطعم أولاً من لوحة الإدارة ثم عد إلى هذه الصفحة.</p>
        </div>
    @else
        <!-- تم إزالة الطول الثابت في الجوال ليعمل التمرير الطبيعي، وإضافته فقط لشاشات xl -->
        <div class="grid gap-6 xl:grid-cols-12 xl:h-[calc(100vh-180px)]">
            
            <!-- القسم الأيسر: النموذج -->
            <!-- في الجوال: ارتفاع تلقائي، في الشاشات الكبيرة: ارتفاع كامل مع تمرير داخلي -->
            <div class="xl:col-span-5 h-auto xl:h-full xl:overflow-y-auto p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <form wire:submit.prevent="save" class="space-y-6 flex-grow flex flex-col justify-between">
                    <div class="flex-grow">
                        {{ $this->form }}
                    </div>

                    <div class="pt-4 mt-6 border-t border-gray-100 dark:border-gray-800">
                        <x-filament::button type="submit" size="lg" class="w-full">
                            حفظ التغييرات
                        </x-filament::button>
                    </div>
                </form>
            </div>

            <!-- القسم الأيمن: المعاينة -->
            <!-- في الجوال: ارتفاع ثابت 600px ليكون قابلاً للاستخدام، في الشاشات الكبيرة: ارتفاع كامل -->
            <div style="height: 58rem;" class="xl:col-span-7 h-[600px] xl:h-full bg-gray-100 dark:bg-gray-950 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <div class="bg-gray-200 dark:bg-gray-900 px-4 py-3 border-b border-gray-300 dark:border-gray-800 flex items-center justify-between text-xs font-bold text-gray-600 dark:text-gray-400 shrink-0">
                    <span class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        معاينة متجر المطعم المباشرة
                    </span>
                    <span class="text-gray-400 dark:text-gray-500 font-normal hidden sm:block">تحديث لحظي عند تغيير الألوان</span>
                </div>
                
                <iframe 
                    id="theme-preview-iframe"
                    src="{{ route('restaurant.home', $restaurant->slug ?? 'demo') }}?preview=1" 
                    class="w-full h-full md:h-[5rem] flex-grow border-none">
                </iframe>
            </div>

        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('theme-variable-updated', (event) => {
                const iframe = document.getElementById('theme-preview-iframe');
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.postMessage({
                        type: 'THEME_VARIABLE_CHANGE',
                        key: event.key,
                        value: event.value,
                        cssVar: event.cssVar
                    }, '*');
                }
            });
        });
    </script>
</x-filament-panels::page>