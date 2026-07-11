@extends("themes.burger-theme.layout")

@section('content')
<section class="py-20 bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-4 max-w-lg">
        
        <div class="bg-white rounded-3xl shadow-2xl p-10 text-center">
            
            <div class="w-20 h-20 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="text-4xl">🔍</span>
            </div>
            
            <h1 class="text-3xl font-black text-gray-800 mb-3">تتبع طلبك</h1>
            <p class="text-gray-600 mb-8">أدخل رمز التتبع الخاص بطلبك لمعرفة حالته</p>

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                    <span>❌</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('order.track.search', $restaurant->slug) }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="relative">
                    <input 
                        type="text" 
                        name="tracking_code" 
                        placeholder="ORD-XXXXXX"
                        required
                        class="w-full px-6 py-4 text-center text-2xl font-mono font-bold border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition uppercase"
                        value="{{ old('tracking_code') }}"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition"
                >
                    تتبع الطلب 🚚
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-500 mb-3">مثال على رمز التتبع:</p>
                <code class="bg-gray-100 px-4 py-2 rounded-lg text-primary font-mono font-bold">ORD-A3F2B9</code>
            </div>

            <div class="mt-6">
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="text-gray-500 hover:text-primary text-sm font-medium transition">
                    ← العودة للصفحة الرئيسية
                </a>
            </div>

        </div>

    </div>
</section>
@endsection