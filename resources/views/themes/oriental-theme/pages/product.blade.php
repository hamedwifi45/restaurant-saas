@extends('themes.oriental-theme.layout')

@section('title', $product->name.' | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6" x-data="{ quantity: 1, selectedSize: 'عادي' }">
        <div class="grid gap-8 rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:grid-cols-2 md:p-8">
            <div>
                @if($product->image)
                    <img src="{{ media_url($product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="aspect-square w-full rounded-2xl object-cover">
                @else
                    <div class="flex aspect-square items-center justify-center rounded-2xl bg-amber-50 text-8xl">🍲</div>
                @endif
            </div>
            <div class="flex flex-col justify-center">
                <p class="oriental-text font-bold">{{ $product->category->name ?? 'من مطبخنا' }}</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">{{ $product->name }}</h1>
                @if($product->description)<p class="mt-4 leading-7 text-stone-600">{{ $product->description }}</p>@endif
                <div class="mt-5 flex items-center gap-3">
                    <span class="oriental-text text-3xl font-black">{{ number_format($product->price, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                    @if($product->old_price && $product->old_price > $product->price)<del class="text-stone-400">{{ number_format($product->old_price, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</del>@endif
                </div>
                <div class="mt-7">
                    <label for="product-size" class="mb-2 block font-bold">الحجم</label>
                    <select id="product-size" x-model="selectedSize" class="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 focus:border-amber-800 focus:outline-none">
                        <option value="عادي">عادي</option><option value="كبير">كبير</option><option value="عائلي">عائلي</option>
                    </select>
                </div>
                <fieldset class="mt-6">
                    <legend class="font-bold">إضافات اختيارية</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3"><input type="checkbox" name="addons[]" value="خبز إضافي" class="accent-amber-800"><span class="text-sm">خبز إضافي</span></label>
                        <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3"><input type="checkbox" name="addons[]" value="صلصة خاصة" class="accent-amber-800"><span class="text-sm">صلصة خاصة</span></label>
                    </div>
                </fieldset>
                <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST" class="mt-7 flex flex-wrap gap-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" :value="quantity">
                    <div class="flex items-center rounded-xl border border-stone-300">
                        <button type="button" @click="quantity = Math.max(1, quantity - 1)" class="h-12 w-12 text-xl font-bold" aria-label="تقليل الكمية">−</button>
                        <output class="w-10 text-center font-black" x-text="quantity"></output>
                        <button type="button" @click="quantity++" class="h-12 w-12 text-xl font-bold" aria-label="زيادة الكمية">+</button>
                    </div>
                    <button type="submit" class="oriental-button min-h-12 flex-1 rounded-xl px-5 font-black">أضف إلى السلة</button>
                </form>
                <p class="mt-3 text-xs text-stone-500">تُتاح خيارات الحجم والإضافات للعرض؛ يعتمد السعر والطلب على خيارات المنتج المعتمدة في المطعم.</p>
            </div>
        </div>
        <section class="mt-12">
            <h2 class="text-2xl font-black">آراء ضيوفنا</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @forelse($reviews as $review)
                    <article class="rounded-2xl border border-stone-200 bg-white p-5">
                        <div class="flex items-center justify-between gap-3"><strong>{{ $review->customer_name }}</strong><span class="text-amber-600" aria-label="التقييم {{ $review->rating }} من 5">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span></div>
                        @if($review->comment)<p class="mt-3 text-sm leading-6 text-stone-600">{{ $review->comment }}</p>@endif
                    </article>
                @empty
                    <p class="rounded-2xl bg-white p-5 text-stone-500">لا توجد تقييمات لهذا الطبق بعد.</p>
                @endforelse
            </div>
            @if($reviews->hasPages())<div class="mt-5">{{ $reviews->links() }}</div>@endif
        </section>
    </section>
@endsection
