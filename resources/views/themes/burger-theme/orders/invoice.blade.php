@extends("themes.burger-theme.layout")

@section('content')

<!-- أزرار التحكم (تظهر فقط على الشاشة) -->
<div class="bg-gray-100 py-4 print:hidden">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <a href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}" 
           class="text-primary hover:underline font-bold">
            ← العودة لتتبع الطلب
        </a>
        <button onclick="window.print()" 
                class="btn-primary px-6 py-2 rounded-lg font-bold hover:shadow-lg transition flex items-center gap-2">
            <span>🖨️</span>
            <span>طباعة الفاتورة</span>
        </button>
    </div>
</div>

<!-- محتوى الفاتورة -->
<div class="bg-white py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <!-- رأس الفاتورة -->
        <div class="border-b-4 border-primary pb-8 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    @if($restaurant->logo)
                        <img src="{{ asset('storage/' . $restaurant->logo) }}" 
                             alt="{{ $restaurant->name }}" 
                             class="w-20 h-20 rounded-full object-cover mb-4">
                    @endif
                    <h1 class="text-3xl font-black text-gray-800">{{ $restaurant->name }}</h1>
                    @if($restaurant->address)
                        <p class="text-gray-600 mt-2">{{ $restaurant->address }}</p>
                    @endif
                    @if($restaurant->phone)
                        <p class="text-gray-600">📞 {{ $restaurant->phone }}</p>
                    @endif
                </div>
                <div class="text-left">
                    <h2 class="text-4xl font-black text-primary mb-2">فاتورة</h2>
                    <p class="text-gray-600">رقم الفاتورة: <span class="font-bold">{{ $order->invoice->invoice_number ?? 'N/A' }}</span></p>
                    <p class="text-gray-600">التاريخ: <span class="font-bold">{{ $order->created_at->format('Y-m-d H:i') }}</span></p>
                    <p class="text-gray-600">رمز التتبع: <span class="font-bold text-primary">{{ $order->tracking_code }}</span></p>
                </div>
            </div>
        </div>

        <!-- معلومات العميل -->
        <div class="bg-gray-50 p-6 rounded-xl mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4">معلومات العميل</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">الاسم</p>
                    <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">رقم الهاتف</p>
                    <p class="font-bold text-gray-800">{{ $order->customer_phone }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">عنوان التوصيل</p>
                    <p class="font-bold text-gray-800">{{ $order->delivery_address }}</p>
                </div>
            </div>
        </div>

        <!-- جدول المنتجات -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4">تفاصيل الطلب</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-right py-3 px-4 font-bold text-gray-700">المنتج</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-700">الكمية</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-700">السعر</th>
                            <th class="text-left py-3 px-4 font-bold text-gray-700">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b border-gray-100">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-800">{{ $item->product_name }}</div>
                                    @if($item->product && $item->product->description)
                                        <div class="text-sm text-gray-500 mt-1">{{ Str::limit($item->product->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="text-center py-4 px-4 font-bold text-gray-800">{{ $item->quantity }}</td>
                                <td class="text-center py-4 px-4 text-gray-600">{{ number_format($item->price, 2) }} ر.س</td>
                                <td class="text-left py-4 px-4 font-bold text-gray-800">{{ number_format($item->total, 2) }} ر.س</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ملخص المبلغ -->
        <div class="bg-gray-50 p-6 rounded-xl mb-8">
            <div class="space-y-3">
                <div class="flex justify-between text-gray-700">
                    <span>المجموع الفرعي:</span>
                    <span class="font-bold">{{ number_format($order->subtotal, 2) }} ر.س</span>
                </div>
                
                @if($order->discount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>
                            @if($order->offer)
                                🎉 خصم العرض ({{ $order->offer->title }}):
                            @else
                                🎉 خصم العرض:
                            @endif
                        </span>
                        <span class="font-bold">- {{ number_format($order->discount, 2) }} ر.س</span>
                    </div>
                @endif

                @if($order->coupon_discount > 0)
                    <div class="flex justify-between text-blue-600">
                        <span>
                            🎫 خصم الكوبون 
                            @if($order->coupon_code)
                                ({{ $order->coupon_code }})
                            @endif
                            :
                        </span>
                        <span class="font-bold">- {{ number_format($order->coupon_discount, 2) }} ر.س</span>
                    </div>
                @endif

                <div class="flex justify-between text-gray-700">
                    <span>رسوم التوصيل:</span>
                    <span class="font-bold">{{ number_format($order->delivery_fee, 2) }} ر.س</span>
                </div>

                <div class="flex justify-between text-gray-700">
                    <span>الضريبة (١٥٪):</span>
                    <span class="font-bold">{{ number_format($order->final_amount - $order->subtotal - $order->delivery_fee + $order->discount + $order->coupon_discount, 2) }} ر.س</span>
                </div>

                <div class="border-t-2 border-gray-300 pt-3 mt-3 flex justify-between text-2xl font-black text-primary">
                    <span>الإجمالي النهائي:</span>
                    <span>{{ number_format($order->final_amount, 2) }} ر.س</span>
                </div>
            </div>
        </div>

        <!-- معلومات الدفع -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 p-6 rounded-xl">
                <h3 class="text-lg font-bold text-gray-800 mb-3">طريقة الدفع</h3>
                <p class="text-gray-700">
                    @if($order->payment_method === 'cash')
                        💵 دفع عند الاستلام
                    @elseif($order->payment_method === 'transfer')
                        🏦 تحويل بنكي
                    @endif
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    حالة الدفع: 
                    <span class="font-bold {{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-orange-500' }}">
                        {{ $order->payment_status === 'paid' ? 'مدفوع' : 'قيد الانتظار' }}
                    </span>
                </p>
            </div>

            <div class="bg-gray-50 p-6 rounded-xl">
                <h3 class="text-lg font-bold text-gray-800 mb-3">حالة الطلب</h3>
                <p class="text-gray-700">
                    @switch($order->status)
                        @case('pending')
                            ⏳ قيد الانتظار
                            @break
                        @case('preparing')
                            🍳 جاري التحضير
                            @break
                        @case('on_way')
                            🛵 في الطريق
                            @break
                        @case('delivered')
                            ✅ تم التوصيل
                            @break
                        @case('cancelled')
                            ❌ ملغي
                            @break
                    @endswitch
                </p>
                @if($order->notes)
                    <p class="text-sm text-gray-500 mt-2">ملاحظات: {{ $order->notes }}</p>
                @endif
            </div>
        </div>

        <!-- تذييل الفاتورة -->
        <div class="border-t-2 border-gray-200 pt-6 text-center text-gray-500 text-sm">
            <p class="mb-2">شكراً لطلبك من {{ $restaurant->name }}!</p>
            <p>نتمنى أن تكون تجربتك مرضية ونتطلع لخدمتك مرة أخرى</p>
            @if($order->status === 'delivered' && !$order->is_reviewed)
                <p class="mt-4">
                    <a href="{{ route('review.create', [$restaurant->slug, $order->tracking_code]) }}" 
                       class="text-primary hover:underline font-bold">
                        ⭐ قيّم تجربتك الآن
                    </a>
                </p>
            @endif
        </div>

    </div>
</div>

<!-- أنماط الطباعة -->
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .container, .container * {
            visibility: visible;
        }
        .container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .print\:hidden {
            display: none !important;
        }
        header, footer {
            display: none !important;
        }
    }
</style>

@endsection