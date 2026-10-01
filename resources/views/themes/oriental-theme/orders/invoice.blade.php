@extends('themes.oriental-theme.layout')

@section('title', 'فاتورة '.$order->tracking_code.' | '.$restaurant->name)

@section('content')
    @php
        $currency = $restaurant->currency_symbol ?? 'ل.س';
        $taxRate = (float) ($restaurant->tax_percentage ?? 0);
        $vat = $order->tax_amount ?? (($order->subtotal ?? $order->total_amount) * $taxRate / 100);
    @endphp
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        <div class="mb-5 flex items-center justify-between gap-3 print:hidden">
            <a href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}" class="text-sm font-bold text-stone-600">← تتبع الطلب</a>
            <button type="button" onclick="window.print()" class="oriental-button rounded-xl px-5 py-2.5 font-bold">طباعة الفاتورة</button>
        </div>
        <article class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-9">
            <header class="flex flex-col justify-between gap-5 border-b-2 border-stone-200 pb-6 sm:flex-row">
                <div class="flex items-center gap-4">
                    @if($restaurant->logo)<img src="{{ media_url($restaurant->logo) }}" alt="" class="h-16 w-16 rounded-full object-cover">@endif
                    <div><h1 class="text-2xl font-black">{{ $restaurant->name }}</h1>@if($restaurant->address)<p class="mt-1 text-sm text-stone-500">{{ $restaurant->address }}</p>@endif</div>
                </div>
                <div class="sm:text-left"><p class="oriental-text text-2xl font-black">فاتورة</p><p class="mt-2 text-sm">رقم الطلب: {{ $order->tracking_code }}</p><p class="mt-1 text-sm">التاريخ: {{ $order->created_at?->format('Y-m-d H:i') }}</p><p class="mt-1 text-sm">رقم الفاتورة: {{ $order->invoice?->invoice_number ?? '—' }}</p></div>
            </header>
            <div class="grid gap-3 border-b border-stone-200 py-5 text-sm sm:grid-cols-2">
                <p>العميل: <strong>{{ $order->customer_name }}</strong></p><p>الهاتف: <strong>{{ $order->customer_phone }}</strong></p>
                @if($order->delivery_address)<p class="sm:col-span-2">العنوان: <strong>{{ $order->delivery_address }}</strong></p>@endif
                <p>طريقة الدفع: <strong>{{ $order->payment_method === 'cash' ? 'نقداً' : 'تحويل' }}</strong></p>
            </div>
            <div class="overflow-x-auto">
                <table class="mt-5 w-full min-w-[34rem] text-sm">
                    <thead><tr class="border-b border-stone-200 text-right"><th class="py-3">الصنف</th><th class="py-3 text-center">الكمية</th><th class="py-3 text-center">السعر</th><th class="py-3 text-left">الإجمالي</th></tr></thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b border-stone-100"><td class="py-3 font-bold">{{ $item->product_name }}</td><td class="py-3 text-center">{{ $item->quantity }}</td><td class="py-3 text-center">{{ number_format($item->price, 2) }} {{ $currency }}</td><td class="py-3 text-left">{{ number_format($item->total, 2) }} {{ $currency }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <dl class="ms-auto mt-6 max-w-sm space-y-3 text-sm">
                <div class="flex justify-between"><dt>المجموع الفرعي</dt><dd>{{ number_format($order->subtotal ?? $order->total_amount, 2) }} {{ $currency }}</dd></div>
                <div class="flex justify-between"><dt>ضريبة القيمة المضافة ({{ number_format($taxRate, 2) }}%)</dt><dd>{{ number_format($vat, 2) }} {{ $currency }}</dd></div>
                <div class="flex justify-between"><dt>رسوم التوصيل</dt><dd>{{ number_format($order->delivery_fee ?? 0, 2) }} {{ $currency }}</dd></div>
                @if(($order->discount ?? 0) > 0)<div class="flex justify-between text-emerald-700"><dt>الخصم</dt><dd>− {{ number_format($order->discount, 2) }} {{ $currency }}</dd></div>@endif
                <div class="flex justify-between border-t border-stone-200 pt-3 text-lg font-black"><dt>الإجمالي</dt><dd class="oriental-text">{{ number_format($order->total_amount, 2) }} {{ $currency }}</dd></div>
            </dl>
            <p class="mt-8 border-t border-stone-100 pt-5 text-center text-xs text-stone-500">شكراً لثقتكم بـ {{ $restaurant->name }}</p>
        </article>
    </section>
    @push('head')<style>@media print { body { background: white !important; } main { margin: 0 !important; } article { border: 0 !important; box-shadow: none !important; } }</style>@endpush
@endsection
