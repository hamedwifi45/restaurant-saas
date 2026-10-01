@extends('themes.oriental-theme.layout')

@section('title', 'قائمة الطعام | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6" x-data="{ search: '', category: 'all' }">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div><p class="oriental-text font-bold">نكهات مُعدّة بحب</p><h1 class="mt-1 text-3xl font-black sm:text-4xl">قائمة الطعام</h1></div>
            <label class="relative block w-full sm:max-w-sm">
                <span class="sr-only">ابحث في القائمة</span>
                <input x-model.debounce.150ms="search" type="search" placeholder="ابحث عن طبق..." class="w-full rounded-xl border border-stone-300 bg-white px-4 py-3 pe-11 outline-none focus:border-amber-800 focus:ring-2 focus:ring-amber-800/20">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-stone-400">⌕</span>
            </label>
        </div>
        @if($table)
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-900">طلبك على طاولة رقم {{ $table->number }}</p>
        @endif

        <div class="mt-6 flex snap-x gap-2 overflow-x-auto pb-2">
            <button @click="category = 'all'" :class="category === 'all' ? 'oriental-primary text-white' : 'bg-white text-stone-700'" class="shrink-0 rounded-full border border-stone-200 px-4 py-2 text-sm font-bold">الكل</button>
            @foreach($categories as $category)
                <button @click="category = '{{ $category->id }}'" :class="category === '{{ $category->id }}' ? 'oriental-primary text-white' : 'bg-white text-stone-700'" class="shrink-0 rounded-full border border-stone-200 px-4 py-2 text-sm font-bold">{{ $category->name }}</button>
            @endforeach
        </div>

        <div class="mt-8 space-y-10">
            @forelse($categories as $category)
                <section id="category-{{ $category->id }}" x-show="category === 'all' || category === '{{ $category->id }}'">
                    <div class="mb-4 border-b border-stone-200 pb-3">
                        <h2 class="text-2xl font-black">{{ $category->name }}</h2>
                        @if($category->description)<p class="mt-1 text-sm text-stone-500">{{ $category->description }}</p>@endif
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse($category->products as $product)
                            <article data-name="{{ mb_strtolower($product->name) }}" x-show="!search || $el.dataset.name.includes(search.trim().toLowerCase())" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md">
                                <a href="{{ route('product.show', [$restaurant->slug, $product->id]) }}" class="flex gap-4 p-4">
                                    @if($product->image)
                                        <img src="{{ media_url($product->image) }}" alt="{{ $product->name }}" loading="lazy" class="h-24 w-24 shrink-0 rounded-xl object-cover sm:h-28 sm:w-28">
                                    @else
                                        <span class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-3xl sm:h-28 sm:w-28">🍽️</span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-black">{{ $product->name }}</span>
                                        @if($product->description)<span class="mt-1 line-clamp-2 block text-sm leading-5 text-stone-500">{{ $product->description }}</span>@endif
                                        <span class="oriental-text mt-3 block font-black">{{ number_format($product->price, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                                    </span>
                                </a>
                                <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST" class="border-t border-stone-100 px-4 py-3">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="oriental-button w-full rounded-lg px-4 py-2.5 text-sm font-black">أضف إلى السلة</button>
                                </form>
                            </article>
                        @empty
                            <p class="col-span-full rounded-xl bg-white p-5 text-sm text-stone-500">لا توجد أطباق في هذا التصنيف حالياً.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-2xl bg-white p-10 text-center text-stone-500">القائمة قيد التجهيز، تفضل بزيارتنا قريباً.</div>
            @endforelse
        </div>
        @if(isset($cartCount) && $cartCount > 0)
            <a href="{{ route('cart.index', $restaurant->slug) }}" class="oriental-primary fixed inset-x-4 bottom-4 z-30 mx-auto flex max-w-xl items-center justify-between rounded-2xl px-5 py-4 font-black text-white shadow-xl sm:inset-x-auto sm:left-6 sm:right-6">
                <span>عرض السلة · {{ $cartCount }} أصناف</span><span>{{ number_format($cartTotal, 2) }} {{ $restaurant->currency_symbol ?? 'ل.س' }} ←</span>
            </a>
        @endif
    </section>
@endsection
