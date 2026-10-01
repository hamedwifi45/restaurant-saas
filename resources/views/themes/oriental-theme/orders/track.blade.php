@extends('themes.oriental-theme.layout')

@section('title', 'تتبع الطلب '.$order->tracking_code.' | '.$restaurant->name)

@section('content')
    @php
        $steps = [
            'pending' => ['label' => 'تم استلام الطلب', 'icon' => '✓'],
            'preparing' => ['label' => 'قيد التحضير', 'icon' => '♨'],
            'ready' => ['label' => 'جاهز للاستلام', 'icon' => '✦'],
            'on_way' => ['label' => 'في الطريق إليك', 'icon' => '➜'],
            'delivered' => ['label' => 'تم التسليم', 'icon' => '⌂'],
        ];
    @endphp
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6" x-data="orderTracking">
        <div class="text-center"><p class="oriental-text font-bold">تحديث مباشر لحالة طلبك</p><h1 class="mt-2 text-3xl font-black">طلب <span class="font-mono">{{ $order->tracking_code }}</span></h1></div>
        <div class="mt-8 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-8">
            <ol class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($steps as $status => $step)
                    <li class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full text-xl font-black" :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(status) ? 'oriental-primary text-white' : 'bg-stone-100 text-stone-400'">{{ $step['icon'] }}</span>
                        <span class="text-sm font-bold" :class="status === '{{ $status }}' ? 'oriental-text' : 'text-stone-600'">{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-8 border-t border-stone-100 pt-6">
                <h2 class="font-black">تفاصيل التوصيل</h2>
                <p class="mt-2 text-sm text-stone-600">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                @if($order->delivery_address)<p class="mt-1 text-sm text-stone-600">{{ $order->delivery_address }}</p>@endif
                <p class="mt-4 text-sm">حالة الطلب: <strong x-text="statusLabel"></strong></p>
            </div>
        </div>
        <div class="mt-5 flex justify-center gap-3">
            <a href="{{ route('order.invoice', [$restaurant->slug, $order->tracking_code]) }}" class="rounded-xl border border-stone-300 bg-white px-5 py-3 text-sm font-bold">عرض الفاتورة</a>
            @if($order->status === 'delivered')<a href="{{ route('review.create', [$restaurant->slug, $order->tracking_code]) }}" class="oriental-button rounded-xl px-5 py-3 text-sm font-bold">قيّم تجربتك</a>@endif
        </div>
    </section>
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('orderTracking', () => ({
                    status: @js($order->status),
                    statusOrder: ['pending', 'preparing', 'ready', 'on_way', 'delivered'],
                    get statusLabel() {
                        return ({ pending: 'تم استلام الطلب', preparing: 'قيد التحضير', ready: 'جاهز للاستلام', on_way: 'في الطريق إليك', delivered: 'تم التسليم' })[this.status] || this.status;
                    },
                    init() {
                        window.setInterval(async () => {
                            try {
                                const response = await fetch(@js(route('order.track.location', [$restaurant->slug, $order->tracking_code])), { headers: { Accept: 'application/json' } });
                                if (!response.ok) throw new Error('تعذر تحديث حالة الطلب.');
                                this.status = (await response.json()).status;
                            } catch (error) {
                                console.error(error);
                            }
                        }, 15000);
                    }
                }));
            });
        </script>
    @endpush
@endsection
