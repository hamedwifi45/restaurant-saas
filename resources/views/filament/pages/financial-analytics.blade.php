<x-filament-panels::page>
    @php
        $presets = [
            'today' => 'اليوم',
            '7_days' => 'آخر 7 أيام',
            '30_days' => 'آخر 30 يوماً',
            'this_month' => 'هذا الشهر',
            'previous_month' => 'الشهر السابق',
        ];
    @endphp

    <section class="space-y-5" dir="rtl">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary-600">أداء المطعم حسب البيانات المسجلة</p>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">التقارير والتحليلات المالية</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                    المبيعات هنا مبنية على الطلبات ذات الدفع المؤكد، وليست سجلاً للتحصيل النقدي أو الأرباح.
                </p>
            </div>
            <div wire:loading.flex class="items-center gap-2 text-sm text-gray-500" role="status" aria-live="polite">
                <span class="size-2 animate-pulse rounded-full bg-primary-500"></span>
                جارٍ تحديث التقارير…
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap gap-2" aria-label="فترات جاهزة">
                    @foreach ($presets as $preset => $label)
                        <button
                            type="button"
                            wire:click="setPeriodPreset('{{ $preset }}')"
                            wire:loading.attr="disabled"
                            class="rounded-full border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:border-primary-500 hover:text-primary-700 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:text-primary-300"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-2xl">
                    <label class="grid gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                        من تاريخ
                        <input
                            type="date"
                            wire:model.live.debounce.500ms="filters.startDate"
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-200">
                        إلى تاريخ
                        <input
                            type="date"
                            wire:model.live.debounce.500ms="filters.endDate"
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-2" aria-label="تجميع نمط الطلبات">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">نمط الطلبات:</span>
                    <button
                        type="button"
                        wire:click="setSalesPattern('hour')"
                        wire:loading.attr="disabled"
                        aria-pressed="{{ ($filters['pattern'] ?? 'hour') === 'hour' ? 'true' : 'false' }}"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ ($filters['pattern'] ?? 'hour') === 'hour' ? 'bg-primary-600 text-white' : 'border border-gray-300 text-gray-700 hover:border-primary-500 dark:border-gray-600 dark:text-gray-200' }}"
                    >
                        حسب الساعة
                    </button>
                    <button
                        type="button"
                        wire:click="setSalesPattern('weekday')"
                        wire:loading.attr="disabled"
                        aria-pressed="{{ ($filters['pattern'] ?? 'hour') === 'weekday' ? 'true' : 'false' }}"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ ($filters['pattern'] ?? 'hour') === 'weekday' ? 'bg-primary-600 text-white' : 'border border-gray-300 text-gray-700 hover:border-primary-500 dark:border-gray-600 dark:text-gray-200' }}"
                    >
                        حسب يوم الأسبوع
                    </button>
                </div>

                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                    تواريخ الطلبات محسوبة حسب وقت إنشاء الطلب؛ المصاريف حسب تاريخ الصرف. تتم مقارنة الفترة بالفترة السابقة المماثلة في عدد الأيام. نمط الطلبات يشمل الطلبات غير الملغاة بغض النظر عن حالة الدفع.
                </p>
            </div>
        </div>
    </section>
</x-filament-panels::page>
