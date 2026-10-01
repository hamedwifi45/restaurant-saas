@extends('themes.oriental-theme.layout')

@section('title', $restaurant->name.' | نكهات شرقية أصيلة')

@section('content')
    @if(theme_setting('show_hero_banner', true))
        <section class="relative isolate overflow-hidden bg-stone-950 text-white">
            @if($restaurant->cover_image)
                <img src="{{ media_url($restaurant->cover_image) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover">
                <div class="absolute inset-0 -z-10 bg-stone-950/65"></div>
            @else
                <div class="absolute inset-0 -z-10 bg-gradient-to-bl from-amber-900 via-stone-900 to-stone-950"></div>
            @endif
            <div class="mx-auto grid min-h-[28rem] max-w-7xl items-center gap-8 px-4 py-16 sm:px-6 md:min-h-[36rem] md:grid-cols-2 md:py-24">
                <div>
                    <span class="inline-flex rounded-full border border-amber-200/40 bg-white/10 px-4 py-2 text-sm font-bold text-amber-100">أصالة الطعم… كرم الضيافة</span>
                    <h1 class="mt-6 text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">{{ theme_setting('hero_title', $restaurant->name) }}</h1>
                    <p class="mt-4 max-w-xl text-lg leading-8 text-stone-200">{{ theme_setting('hero_subtitle', $restaurant->description ?? 'أشهى الأطباق تُحضّر لكم بكل حب') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-button rounded-xl px-6 py-3 font-black shadow-lg">اكتشف قائمتنا</a>
                        <a href="#categories" class="rounded-xl border border-white/50 px-6 py-3 font-bold text-white transition hover:bg-white/10">تصفح الأصناف</a>
                    </div>
                    @if($restaurant->estimated_delivery_time)
                        <p class="mt-8 text-sm text-stone-300">توصيل تقريبي خلال <strong class="text-white">{{ $restaurant->estimated_delivery_time }} دقيقة</strong></p>
                    @endif
                </div>
                <div class="hidden justify-center md:flex">
                    <div class="flex h-72 w-72 items-center justify-center rounded-full border border-amber-200/30 bg-amber-100/10 text-8xl shadow-2xl shadow-black/30 lg:h-96 lg:w-96">✦</div>
                </div>
            </div>
        </section>
    @endif

    <section id="categories" class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="oriental-text font-bold">من مطبخنا إلى مائدتكم</p><h2 class="mt-1 text-3xl font-black sm:text-4xl">اختر ما تشتهيه</h2></div>
            <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-text font-bold">كل القائمة ←</a>
        </div>
        <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-6">
            @forelse($categories as $category)
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}#category-{{ $category->id }}" class="group rounded-2xl border border-stone-200 bg-white p-4 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-lg sm:p-5">
                    <span class="oriental-text mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-2xl">❋</span>
                    <h3 class="mt-3 font-bold">{{ $category->name }}</h3>
                </a>
            @empty
                <p class="col-span-full rounded-2xl bg-white p-8 text-center text-stone-500">تُضاف الأصناف إلى قائمتنا قريباً.</p>
            @endforelse
        </div>
    </section>

    @if($restaurant->activeOffers->isNotEmpty())
        <section class="bg-amber-50 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div><p class="font-bold text-amber-800">لفترة محدودة</p><h2 class="mt-1 text-3xl font-black">عروضنا الخاصة</h2></div>
                    <a href="{{ route('offers.index', $restaurant->slug) }}" class="font-bold text-amber-900">عرض الكل ←</a>
                </div>
                <div class="flex snap-x gap-4 overflow-x-auto pb-3">
                    @foreach($restaurant->activeOffers as $offer)
                        <article class="min-w-[min(85vw,22rem)] snap-start rounded-2xl border border-amber-100 bg-white p-5 shadow-sm sm:min-w-[22rem]">
                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-black text-amber-900">{{ $offer->getDiscountLabel() }}</span>
                            <h3 class="mt-3 text-xl font-black">{{ $offer->title }}</h3>
                            @if($offer->description)<p class="mt-2 text-sm leading-6 text-stone-600">{{ $offer->description }}</p>@endif
                            <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-text mt-4 inline-flex font-bold">اطلب الآن ←</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="rounded-3xl bg-stone-900 p-7 text-white sm:p-10">
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                <div><p class="text-amber-300">وجبتك المفضلة على بُعد خطوات</p><h2 class="mt-2 text-3xl font-black">جاهزون لطلبك؟</h2><p class="mt-2 text-stone-300">تصفح القائمة واختر ما يناسبك.</p></div>
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-button shrink-0 rounded-xl px-6 py-3 font-black">ابدأ الطلب الآن</a>
            </div>
        </div>
    </section>
@endsection
