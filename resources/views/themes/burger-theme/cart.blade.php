@extends("themes.burger-theme.layout")

@section('content')

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-black text-center mb-12 text-gray-800">سلة التسوق 🛒</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(empty($products))
            <!-- حالة السلة الفارغة -->
            <div class="text-center py-20 bg-white rounded-2xl shadow-lg">
                <div class="text-8xl mb-6">🛒</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">السلة فارغة</h2>
                <p class="text-gray-600 mb-8">ابدأ بإضافة منتجات لذيذة إلى سلتك</p>
                <a href="{{ route('restaurant.menu', $restaurant->slug ?? 'abboudi-mongolian') }}" 
                   class="btn-primary px-8 py-3 rounded-full font-bold inline-block">
                    تصفح القائمة
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- قائمة المنتجات -->
                <div class="lg:col-span-2 space-y-4">
                    @foreach($products as $item)
                        <div class="bg-white p-6 rounded-2xl shadow-md flex items-center gap-6">
                            <!-- صورة المنتج -->
                            <div class="w-24 h-24 flex-shrink-0">
                                @if($item['product']->image)
                                    <img src="{{ asset('storage/' . $item['product']->image) }}" 
                                         alt="{{ $item['product']->name }}" 
                                         class="w-full h-full object-cover rounded-xl">
                                @else
                                    <div class="w-full h-full bg-gray-200 rounded-xl flex items-center justify-center text-2xl">🍽️</div>
                                @endif
                            </div>

                            <!-- تفاصيل المنتج -->
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-800">{{ $item['product']->name }}</h3>
                                <p class="text-primary font-bold mt-1">{{ number_format($item['price'], 2) }} ر.س</p>
                            </div>

                            <!-- التحكم بالكمية -->
                            <div class="flex items-center gap-3">
                                <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                    <button type="submit" name="quantity" value="{{ max(1, $item['qty'] - 1) }}" 
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">-</button>
                                    
                                    <span class="font-bold w-8 text-center">{{ $item['qty'] }}</span>
                                    
                                    <button type="submit" name="quantity" value="{{ $item['qty'] + 1 }}" 
                                            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold">+</button>
                                </form>

                                <form action="{{ route('cart.remove') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ملخص الطلب -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg sticky top-24">
                        <h3 class="text-2xl font-bold mb-6 text-gray-800">ملخص الطلب</h3>
                        
                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>المجموع الفرعي</span>
                                <span>{{ number_format($total, 2) }} ر.س</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>التوصيل</span>
                                <span class="text-green-600 font-bold">مجاني</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>الضريبة (١٥٪)</span>
                                <span>{{ number_format($total * 0.15, 2) }} ر.س</span>
                            </div>
                            <div class="border-t pt-4 flex justify-between text-xl font-black text-gray-800">
                                <span>الإجمالي</span>
                                <span class="text-primary">{{ number_format($total * 1.15, 2) }} ر.س</span>
                            </div>
                        </div>

                        <a href="{{ route('checkout') }}" 
                           class="w-full btn-primary py-4 rounded-xl font-bold text-center block hover:shadow-lg transition">
                            إتمام الطلب
                        </a>
                    </div>
                </div>

            </div>
        @endif
    </div>
</section>

@endsection