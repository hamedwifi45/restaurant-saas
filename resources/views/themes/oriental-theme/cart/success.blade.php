@extends('themes.oriental-theme.layout')

@section('title', 'تم استلام طلبك | '.$restaurant->name)

@section('content')
    @php
        $receiptMessage = 'تم استلام طلبك رقم '.$order->tracking_code.' من '.$restaurant->name.'. شكراً لطلبك!';
        $receiptPhone = str_replace(['+', ' ', '-', '(', ')'], '', (string) ($order->customer_phone ?? ''));
    @endphp
    <section class="mx-auto flex max-w-3xl flex-col items-center px-4 py-16 text-center sm:px-6">
        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-4xl text-emerald-800">✓</span>
        <p class="mt-6 font-bold text-emerald-800">وصل طلبك بنجاح</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">شكراً لاختياركم {{ $restaurant->name }}</h1>
        <p class="mt-3 max-w-xl leading-7 text-stone-600">بدأنا تجهيز طلبك، ويمكنك متابعة حالته باستخدام رمز التتبع.</p>
        <div class="mt-7 w-full rounded-2xl border border-stone-200 bg-white p-6">
            <p class="text-sm text-stone-500">رمز التتبع</p><p class="mt-2 font-mono text-2xl font-black tracking-wider">{{ $order->tracking_code }}</p>
            <p class="mt-3 text-sm text-stone-600">الإجمالي: <strong>{{ number_format($order->total_amount, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</strong></p>
        </div>
        <div class="mt-6 flex w-full flex-col gap-3 sm:flex-row">
            <a href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}" class="oriental-button flex-1 rounded-xl px-5 py-3 font-black">تتبع الطلب</a>
            @if($receiptPhone)
                <a href="https://wa.me/{{ $receiptPhone }}?text={{ rawurlencode($receiptMessage) }}" target="_blank" rel="noopener noreferrer" class="flex-1 rounded-xl border border-stone-300 bg-white px-5 py-3 font-bold">إرسال إيصال واتساب</a>
            @endif
        </div>
    </section>
@endsection
