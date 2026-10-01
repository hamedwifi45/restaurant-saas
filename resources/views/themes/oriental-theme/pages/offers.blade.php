@extends('themes.oriental-theme.layout')

@section('title', 'العروض والخصومات | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-bl from-amber-900 to-stone-900 p-8 text-white sm:p-12">
            <p class="font-bold text-amber-200">كرمنا لكم</p><h1 class="mt-2 text-4xl font-black">عروض تستحق التجربة</h1>
            <p class="mt-3 max-w-2xl leading-7 text-stone-200">استمتعوا بخصوماتنا وباقاتنا المختارة، وتحققوا من تفاصيل كل عرض قبل الطلب.</p>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($offers as $offer)
                <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                    <div class="relative flex h-44 items-center justify-center bg-amber-50 text-6xl">
                        @if($offer->image)<img src="{{ media_url($offer->image) }}" alt="{{ $offer->title }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover">@else<span>🎁</span>@endif
                        <span class="oriental-primary absolute right-3 top-3 rounded-full px-3 py-1 text-sm font-black text-white">{{ $offer->getDiscountLabel() }}</span>
                    </div>
                    <div class="p-5">
                        <h2 class="text-xl font-black">{{ $offer->title }}</h2>
                        @if($offer->description)<p class="mt-2 text-sm leading-6 text-stone-600">{{ $offer->description }}</p>@endif
                        <div class="mt-4 space-y-2 text-sm text-stone-500">
                            @if($offer->min_order_amount > 0)<p>الحد الأدنى للطلب: {{ number_format($offer->min_order_amount, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</p>@endif
                            @if($offer->ends_at)<p>ينتهي في: {{ $offer->ends_at->format('Y-m-d') }}</p>@endif
                            @if($offer->max_uses)<p>الاستخدامات المتبقية: {{ max(0, $offer->max_uses - $offer->used_count) }}</p>@endif
                        </div>
                        <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-button mt-5 block rounded-xl px-4 py-3 text-center font-black">اطلب واستفد من العرض</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center">
                    <span class="text-5xl">🌿</span><h2 class="mt-4 text-xl font-black">لا توجد عروض نشطة حالياً</h2><p class="mt-2 text-stone-500">تابعونا، عروضنا القادمة بانتظاركم.</p>
                    <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-text mt-5 inline-block font-bold">تصفح القائمة ←</a>
                </div>
            @endforelse
        </div>
        <div class="mt-12">
            <div class="mb-5"><p class="oriental-text font-bold">مكافآت لضيوفنا</p><h2 class="mt-1 text-2xl font-black">كوبونات الخصم</h2></div>
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse($coupons as $coupon)
                    <article class="rounded-2xl border border-dashed border-amber-700/40 bg-amber-50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div><h3 class="text-lg font-black">{{ $coupon->name }}</h3>@if($coupon->description)<p class="mt-1 text-sm text-stone-600">{{ $coupon->description }}</p>@endif</div>
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-amber-900">{{ $coupon->type === 'percentage' ? 'خصم '.number_format($coupon->value, 0).'%' : 'خصم '.number_format($coupon->value, 2).' '.($restaurant->currency_symbol ?? 'ل.س') }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-amber-900/10 pt-4">
                            <span class="font-mono text-lg font-black tracking-widest">{{ $coupon->code }}</span>
                            <span class="text-xs text-stone-600">
                                @if($coupon->min_order_amount > 0)الحد الأدنى {{ number_format($coupon->min_order_amount, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}@endif
                                @if($coupon->ends_at) · ينتهي {{ $coupon->ends_at->format('Y-m-d') }}@endif
                            </span>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl bg-stone-100 p-5 text-sm text-stone-500">لا توجد كوبونات نشطة حالياً.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
