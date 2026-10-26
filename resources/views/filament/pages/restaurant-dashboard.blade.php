@php
    $statusLabels = [
        'pending' => ['قيد الانتظار', 'bg-amber-50 text-amber-700'],
        'confirmed' => ['مؤكد', 'bg-blue-50 text-blue-700'],
        'preparing' => ['قيد التحضير', 'bg-orange-50 text-orange-700'],
        'ready' => ['جاهز', 'bg-emerald-50 text-emerald-700'],
        'delivered' => ['تم التسليم', 'bg-slate-100 text-slate-600'],
        'cancelled' => ['ملغى', 'bg-red-50 text-red-700'],
    ];

    $formatMoney = static fn (float|int $value): string => number_format($value, 0, ',', '.').' ل.س';
@endphp

<x-filament-panels::page>
<div dir="rtl" class="space-y-6 pb-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold text-orange-600">نظرة عامة حقيقية من النظام</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">لوحة تحكم المطعم</h1>
            <p class="mt-1 text-sm text-slate-500">تابع أداء مطعمك والطلبات التي تحتاج إلى انتباهك.</p>
        </div>
        <p class="text-xs text-slate-500">آخر تحديث: {{ $updated_at }}</p>
    </div>

    @if($restaurant)
        <section class="flex flex-col gap-4 rounded-2xl border border-orange-200 bg-orange-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-950">إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)</h2>
                <p class="mt-1 text-sm {{ $restaurant->is_accepting_orders ? 'text-emerald-700' : 'text-red-700' }}">
                    {{ $restaurant->canAcceptOrders() ? 'استقبال الطلبات متاح' : ($restaurant->is_accepting_orders ? 'المطعم مغلق خارج أوقات العمل' : 'استقبال الطلبات متوقف مؤقتاً') }}
                </p>
            </div>
            <form method="POST" action="{{ route('restaurant.orders.availability', $restaurant->slug) }}">
                @csrf
                <input type="hidden" name="is_accepting_orders" value="{{ $restaurant->is_accepting_orders ? '0' : '1' }}">
                <button type="submit" class="rounded-xl px-5 py-3 text-sm font-bold text-white transition {{ $restaurant->is_accepting_orders ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $restaurant->is_accepting_orders ? 'إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)' : 'استقبال الطلبات متاح' }}
                </button>
            </form>
        </section>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'مبيعات اليوم', 'value' => $formatMoney($summary['sales_today'] ?? 0), 'hint' => 'قيمة الطلبات المدفوعة غير الملغاة، بعد الخصم وتشمل الضريبة والتوصيل', 'color' => 'orange', 'icon' => 'ل.س'],
            ['label' => 'طلبات قيد التنفيذ', 'value' => number_format($summary['active_orders'] ?? 0), 'hint' => 'تحتاج متابعة من الفريق', 'color' => 'blue', 'icon' => '↺'],
            ['label' => 'عملاء اليوم', 'value' => number_format($summary['customers_today'] ?? 0), 'hint' => 'عملاء فريدون حسب رقم الهاتف', 'color' => 'emerald', 'icon' => '●●'],
            ['label' => 'متوسط الطلب', 'value' => $formatMoney($summary['average_order'] ?? 0), 'hint' => 'متوسط قيمة الطلبات المدفوعة غير الملغاة اليوم', 'color' => 'violet', 'icon' => '↗'],
        ] as $stat)
            <div class="rounded-2xl border border-{{ $stat['color'] }}-100 bg-{{ $stat['color'] }}-50 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-{{ $stat['color'] }}-700">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                        <p class="mt-1 text-xs text-{{ $stat['color'] }}-700">{{ $stat['hint'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-sm font-black text-{{ $stat['color'] }}-700 shadow-sm">{{ $stat['icon'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-slate-950">آخر الطلبات</h2>
                    <p class="mt-1 text-sm text-slate-500">بيانات حقيقية من طلبات مطعمك</p>
                </div>
                <a href="{{ route('filament.restaurant.resources.orders.index') }}" class="text-sm font-bold text-orange-600 transition hover:text-orange-700">عرض الكل</a>
            </div>

            @if(empty($recent_orders))
                <div class="px-5 py-12 text-center">
                    <p class="font-bold text-slate-700">لا توجد طلبات بعد</p>
                    <p class="mt-1 text-sm text-slate-500">ستظهر الطلبات هنا فور تسجيلها في النظام.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recent_orders as $order)
                        @php($status = $statusLabels[$order['status']] ?? ['غير محدد', 'bg-slate-100 text-slate-600'])
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-600">{{ $order['id'] }}</span>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $order['customer'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $order['type'] }} · {{ $order['items'] }} أصناف · {{ $order['time'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $status[1] }}">{{ $status[0] }}</span>
                                <span class="text-sm font-black text-slate-900">{{ $formatMoney($order['total']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-950">إجراءات سريعة</h2>
                <p class="mt-1 text-sm text-slate-500">الوصول إلى المهام اليومية من مكان واحد</p>
            </div>
            <div class="grid gap-3">
                <a href="{{ route('filament.restaurant.resources.orders.index') }}" class="rounded-xl border border-slate-200 p-3 text-sm font-bold text-slate-700 transition hover:border-orange-300 hover:bg-orange-50">متابعة الطلبات</a>
                <a href="{{ route('filament.restaurant.resources.products.index') }}" class="rounded-xl border border-slate-200 p-3 text-sm font-bold text-slate-700 transition hover:border-orange-300 hover:bg-orange-50">إدارة المنتجات</a>
                <a href="{{ route('filament.restaurant.resources.restaurant-tables.index') }}" class="rounded-xl border border-slate-200 p-3 text-sm font-bold text-slate-700 transition hover:border-orange-300 hover:bg-orange-50">الطاولات و QR</a>
                <a href="{{ route('filament.restaurant.pages.theme-settings') }}" class="rounded-xl border border-slate-200 p-3 text-sm font-bold text-slate-700 transition hover:border-orange-300 hover:bg-orange-50">تخصيص واجهة المطعم</a>
            </div>
        </section>
    </div>

    @if($restaurant)
        <x-ai-agent-widget :restaurant="$restaurant" mode="restaurant_admin" />
    @endif
</div>
</x-filament-panels::page>