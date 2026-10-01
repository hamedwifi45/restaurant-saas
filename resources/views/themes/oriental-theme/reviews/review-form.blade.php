@extends('themes.oriental-theme.layout')

@section('title', 'شاركنا رأيك | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <header class="text-center"><span class="text-4xl text-amber-500">★</span><h1 class="mt-2 text-3xl font-black">كيف كانت تجربتك؟</h1><p class="mt-2 text-stone-500">تقييمك يساعدنا على تقديم الأفضل.</p></header>
            @if(session('error'))<p class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</p>@endif
            @if($errors->any())<div class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form action="{{ route('review.store', [$restaurant->slug, $order->tracking_code]) }}" method="POST" class="mt-7 space-y-6">
                @csrf
                <fieldset>
                    <legend class="text-center font-bold">اختر تقييمك من 1 إلى 5 نجوم</legend>
                    <div class="mt-3 flex justify-center gap-2" x-data="{ rating: @js((int) old('rating', 0)) }">
                        @for($rating = 1; $rating <= 5; $rating++)
                            <label class="cursor-pointer"><input type="radio" name="rating" value="{{ $rating }}" required x-model="rating" class="sr-only"><span class="text-4xl" :class="rating >= {{ $rating }} ? 'text-amber-500' : 'text-stone-300'">★</span></label>
                        @endfor
                    </div>
                    @error('rating')<p class="mt-2 text-center text-sm text-red-700">{{ $message }}</p>@enderror
                </fieldset>
                <div><label for="review-comment" class="mb-2 block font-bold">تعليقك (اختياري)</label><textarea id="review-comment" name="comment" rows="4" maxlength="2000" class="w-full rounded-xl border border-stone-300 px-4 py-3 focus:border-amber-800 focus:outline-none" placeholder="أخبرنا عن تجربتك...">{{ old('comment') }}</textarea></div>
                <div class="flex flex-col gap-3 sm:flex-row"><button type="submit" class="oriental-button min-h-12 flex-1 rounded-xl px-5 py-3 font-black">إرسال التقييم</button><a href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}" class="rounded-xl border border-stone-300 px-5 py-3 text-center font-bold">عودة إلى الطلب</a></div>
            </form>
        </div>
    </section>
@endsection
