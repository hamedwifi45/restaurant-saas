<header data-theme-slot="receipt-header" class="border-b border-dashed border-zinc-400 pb-4 text-center">
    @if($restaurant->logo)
        <img src="{{ media_url($restaurant->logo) }}" alt="" class="mx-auto mb-3 size-16 object-contain">
    @endif
    <h1 class="text-xl font-extrabold">{{ $restaurant->name }}</h1>
    @if($restaurant->phone)
        <p class="mt-1 text-sm">{{ $restaurant->phone }}</p>
    @endif
    <p class="mt-4 text-sm font-bold">إيصال طلب</p>
    <p class="font-mono text-lg font-bold">{{ $order->tracking_code }}</p>
    <p class="text-xs text-zinc-500">{{ $order->created_at?->format('Y-m-d H:i') }}</p>
</header>
