@extends('themes.oriental-theme.layout')

@section('title', 'تتبع طلب | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 sm:px-6">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-9">
            <div class="text-center"><span class="oriental-text text-5xl">⌖</span><h1 class="mt-3 text-3xl font-black">تتبع طلبك</h1><p class="mt-2 text-stone-500">أدخل رمز التتبع لمعرفة آخر تحديث لحالة طلبك.</p></div>
            <form action="{{ route('order.track.search', $restaurant->slug) }}" method="POST" class="mt-7 space-y-4">
                @csrf
                <label for="tracking-code" class="block text-sm font-bold">رمز التتبع</label>
                <input id="tracking-code" name="tracking_code" value="{{ old('tracking_code') }}" required autocomplete="off" placeholder="ORD-XXXXXX" class="w-full rounded-xl border border-stone-300 px-4 py-4 text-center font-mono text-xl uppercase tracking-widest focus:border-amber-800 focus:outline-none">
                @error('tracking_code')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                <button type="submit" class="oriental-button w-full rounded-xl px-5 py-3 font-black">اعرض حالة الطلب</button>
            </form>
            <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="mt-6 block text-center text-sm font-bold text-stone-500 hover:text-stone-900">العودة للرئيسية</a>
        </div>
    </section>
@endsection
