@extends(\App\Helpers\ThemeHelper::layoutView($restaurant))

@section('title', $pageTitle)

@push('head')
    <style>
        @media print {
            @page { size: 80mm auto; margin: 4mm; }
            body { background: #fff !important; }
        }
    </style>
@endpush

@section('content')
    <main class="mx-auto max-w-xl p-4 sm:p-8">
        <div class="mb-4 flex justify-between gap-3 print:hidden">
            <a href="{{ $newOrderUrl }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">طلب جديد</a>
            <button type="button" onclick="window.print()" class="rounded-xl px-4 py-2 text-sm font-bold text-white hover:opacity-90" style="background-color: {{ theme_setting('primary_color', '#FF6B35') }}">طباعة الإيصال</button>
        </div>

        <article class="rounded-2xl border border-zinc-200 bg-white p-6 text-zinc-900 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 print:rounded-none print:border-0 print:p-0 print:shadow-none">
            @include(
                \App\Helpers\ThemeHelper::viewOrFallback($restaurant, 'orders.pos-receipt', 'pos.partials.receipt-header'),
                ['restaurant' => $restaurant, 'order' => $order]
            )

            <section class="space-y-1 border-b border-dashed border-zinc-400 py-4 text-sm">
                <p><strong>النوع:</strong> {{ match ($order->delivery_type) { 'dine_in' => 'صالة', 'takeaway' => 'استلام', default => 'توصيل' } }}</p>
                @if($order->restaurantTable)
                    <p><strong>الطاولة:</strong> {{ $order->restaurantTable->name ?: $order->restaurantTable->number }}</p>
                @endif
                <p><strong>العميل:</strong> {{ $order->customer_name }}</p>
                <p><strong>الهاتف:</strong> {{ $order->customer_phone }}</p>
                @if($order->delivery_type === 'delivery')
                    <p><strong>العنوان:</strong> {{ $order->delivery_address }}</p>
                @endif
            </section>

            <table class="w-full border-b border-dashed border-zinc-400 py-3 text-sm">
                <thead>
                    <tr class="text-start text-xs text-zinc-500">
                        <th class="py-2 text-start">الصنف</th>
                        <th class="py-2 text-center">الكمية</th>
                        <th class="py-2 text-end">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="py-2">
                                <span class="font-semibold">{{ $item->product_name }}</span>
                                @if($item->variant_name)
                                    <span class="block text-xs text-zinc-500">{{ $item->variant_name }}</span>
                                @endif
                                @if($item->notes)
                                    <span class="block text-xs text-zinc-500">{{ $item->notes }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                            <td class="whitespace-nowrap py-2 text-end">{{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="space-y-2 border-b border-dashed border-zinc-400 py-4 text-sm">
                <div class="flex justify-between"><dt>المجموع الفرعي</dt><dd>{{ number_format((float) $order->subtotal, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                @if((float) $order->discount > 0)
                    <div class="flex justify-between"><dt>الخصم</dt><dd>− {{ number_format((float) $order->discount, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>الضريبة</dt><dd>{{ number_format((float) $order->tax_amount, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                @if((float) $order->delivery_fee > 0)
                    <div class="flex justify-between"><dt>التوصيل</dt><dd>{{ number_format((float) $order->delivery_fee, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-lg font-extrabold dark:border-zinc-700"><dt>الإجمالي</dt><dd>{{ number_format((float) $order->final_amount, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                <div class="flex justify-between"><dt>حالة الدفع</dt><dd>{{ $order->payment_status === 'verified' ? 'مدفوع نقداً' : 'مستحق نقداً' }}</dd></div>
                <div class="flex justify-between"><dt>المبلغ المستحق</dt><dd>{{ number_format($order->payment_status === 'verified' ? 0 : (float) $order->final_amount, 2) }} {{ $restaurant->currency_symbol }}</dd></div>
            </dl>

            @if($order->notes)
                <p class="border-b border-dashed border-zinc-400 py-3 text-sm"><strong>ملاحظات:</strong> {{ $order->notes }}</p>
            @endif

            <footer class="pt-4 text-center text-xs text-zinc-500">
                @if($order->invoice)
                    رقم الفاتورة: {{ $order->invoice->invoice_number }}
                @elseif(config('local_operations.enabled'))
                    رقم الفاتورة الرسمي يصدر بعد مزامنة هذا الطلب مع السحابة.
                @else
                    رقم الفاتورة: —
                @endif
            </footer>
        </article>
    </main>
@endsection
