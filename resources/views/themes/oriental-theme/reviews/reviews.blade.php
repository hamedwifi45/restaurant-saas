@extends('themes.oriental-theme.layout')

@section('title', 'تقييمات الضيوف | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <header class="rounded-3xl bg-stone-950 p-7 text-center text-white sm:p-10">
            <p class="text-amber-300">من تجارب ضيوفنا</p><h1 class="mt-2 text-3xl font-black">آراء وتقييمات</h1>
            <div class="mt-4 flex items-center justify-center gap-3"><span class="text-2xl text-amber-300">★</span><strong class="text-2xl">{{ number_format($restaurant->average_rating ?? 0, 1) }}</strong><span class="text-sm text-stone-300">({{ $restaurant->reviews_count ?? $reviews->total() }} تقييم)</span></div>
        </header>
        <div class="mt-6 space-y-4">
            @forelse($reviews as $review)
                <article class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3"><span class="oriental-primary flex h-11 w-11 items-center justify-center rounded-full font-black text-white">{{ mb_substr($review->customer_name, 0, 1) }}</span><div><h2 class="font-black">{{ $review->customer_name }}</h2><p class="mt-1 text-xs text-stone-500">{{ $review->created_at?->diffForHumans() }}</p></div></div>
                        <span class="whitespace-nowrap text-amber-600" aria-label="التقييم {{ $review->rating }} من 5">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
                    </div>
                    @if($review->comment)<p class="mt-4 leading-7 text-stone-700">{{ $review->comment }}</p>@endif
                    @if($review->images->isNotEmpty())
                        <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">@foreach($review->images as $image)<img src="{{ $image->url }}" alt="{{ $image->alt ?? 'صورة من تقييم العميل' }}" loading="lazy" class="aspect-square w-full rounded-xl object-cover">@endforeach</div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center"><span class="text-4xl">✧</span><h2 class="mt-3 font-black">لا توجد تقييمات بعد</h2><p class="mt-1 text-sm text-stone-500">نتطلع لسماع رأيكم بعد تجربتكم.</p></div>
            @endforelse
        </div>
        @if($reviews->hasPages())<div class="mt-6">{{ $reviews->links() }}</div>@endif
    </section>
@endsection
