@extends("themes.burger-theme.layout")

@section('content')
<section class="py-20 bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-lg w-full mx-4">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-5xl">🎉</div>
        <h1 class="text-3xl font-black text-gray-800 mb-2">تم استلام طلبك بنجاح!</h1>
        <p class="text-gray-600 mb-8">سنبدأ في تحضير طلبك فوراً.</p>

        <div class="bg-gray-50 p-6 rounded-xl mb-8 border-2 border-dashed border-gray-300">
            <p class="text-sm text-gray-500 mb-1">رمز تتبع الطلب</p>
            <p class="text-4xl font-black text-primary tracking-wider">{{ $order->tracking_code }}</p>
            <p class="text-xs text-gray-400 mt-2">احتفظ بهذا الرمز لتتبع حالة طلبك</p>
        </div>

        <a href="{{ route('restaurant.home', $order->restaurant->slug ?? 'abboudi-mongolian') }}" class="btn-primary px-8 py-3 rounded-full font-bold inline-block">العودة للرئيسية</a>
    </div>
</section>
@endsection