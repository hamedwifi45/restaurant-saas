@extends("themes.burger-theme.layout")

@section('content')
    <section class="py-16 bg-gray-50 min-h-screen">
        <div class="container mx-auto px-4 max-w-4xl">

            <!-- رأس الصفحة -->
            <div class="text-center mb-12">
                <span class="bg-primary text-white px-4 py-1 rounded-full text-sm font-bold mb-4 inline-block">حالة
                    الطلب</span>
                <h1 class="text-4xl font-black text-gray-800 mb-2">تتبع طلبك المباشر 🚚</h1>
                <p class="text-gray-600">رقم التتبع: <span
                        class="font-mono font-bold text-primary text-lg">{{ $order->tracking_code }}</span></p>
            </div>

            <!-- بطاقة الحالة الرئيسية -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8 border border-gray-100">

                <!-- شريط التقدم المرئي -->
                <div class="p-8 bg-gray-50 border-b border-gray-100">
                    @php
                        $steps = ['pending' => 1, 'preparing' => 2, 'on_way' => 3, 'delivered' => 4];
                        $currentStep = $steps[$order->status] ?? 1;
                        $labels = [
                            1 => ['title' => 'تم الاستلام', 'icon' => '✓'],
                            2 => ['title' => 'جاري التحضير', 'icon' => '🍳'],
                            3 => ['title' => 'في الطريق إليك', 'icon' => '🛵'],
                            4 => ['title' => 'تم التوصيل', 'icon' => '🏠']
                        ];
                    @endphp

                    <div class="relative flex justify-between items-center">
                        <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 transform -translate-y-1/2"></div>

                        @foreach($labels as $stepNum => $label)
                            @php $isActive = $currentStep >= $stepNum; @endphp
                            <div class="flex flex-col items-center gap-3 relative z-10">
                                <div
                                    class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-500 
                                        {{ $isActive ? 'bg-primary text-white shadow-lg scale-110' : 'bg-white text-gray-300 border-2 border-gray-200' }}">
                                    {{ $label['icon'] }}
                                </div>
                                <span
                                    class="text-sm font-bold {{ $isActive ? 'text-gray-800' : 'text-gray-400' }}">{{ $label['title'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- تفاصيل الطلب والتوصيل -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                    <!-- معلومات العميل -->
                    <div class="p-8 border-b md:border-b-0 md:border-l border-gray-100">
                        <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-2">
                            <span class="text-primary">📍</span> معلومات التوصيل
                        </h3>
                        <ul class="space-y-4 text-gray-600">
                            <li class="flex items-start gap-3">
                                <span class="mt-1 text-primary">👤</span>
                                <div>
                                    <span class="block text-xs text-gray-400">الاسم</span>
                                    <span class="font-bold text-gray-800">{{ $order->customer_name }}</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 text-primary">📞</span>
                                <div>
                                    <span class="block text-xs text-gray-400">رقم الهاتف</span>
                                    <span class="font-bold text-gray-800">{{ $order->customer_phone }}</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 text-primary">🏠</span>
                                <div>
                                    <span class="block text-xs text-gray-400">العنوان</span>
                                    <span class="font-bold text-gray-800">{{ $order->delivery_address }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- ملخص الدفع -->
                    <div class="p-8 bg-gray-50">
                        <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-2">
                            <span class="text-primary">💳</span> ملخص الفاتورة
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>المجموع الفرعي:</span>
                                <span>{{ number_format($order->total_amount, 2) }} ر.س</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>الضريبة (١٥٪):</span>
                                <span>{{ number_format($order->final_amount - $order->total_amount, 2) }} ر.س</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>طريقة الدفع:</span>
                                <span
                                    class="font-bold">{{ $order->payment_method === 'cash' ? 'نقدي عند الاستلام' : 'تحويل بنكي' }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between items-center">
                                <span class="font-black text-gray-800">الإجمالي النهائي:</span>
                                <span class="text-2xl font-black text-primary">{{ number_format($order->final_amount, 2) }}
                                    ر.س</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة المنتجات المطلوبة -->
<div class="bg-white rounded-3xl shadow-lg p-8 mb-8">
    <h3 class="font-black text-2xl mb-8 text-gray-800 border-b pb-4">محتويات الطلب</h3>
    <div class="space-y-6">
        @foreach($order->items as $item)
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b last:border-0 last:pb-0">
                <div class="flex items-center gap-4">
                    @if($item->product && $item->product->image)
                        <img src="{{ asset('storage/' . $item->product->image) }}" 
                             alt="{{ $item->product->name }}"
                             class="w-16 h-16 rounded-2xl object-cover border border-orange-100">
                    @else
                        <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center text-3xl border border-orange-100">
                            🍔
                        </div>
                    @endif
                    
                    <div>
                        @if($item->product)
                            <h4 class="font-bold text-lg text-gray-800">{{ $item->product->name }}</h4>
                            <p class="text-sm text-gray-500">الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ر.س</p>
                            
                            <!-- زر عرض المنتج -->
                            <a href="{{ route('product.show', [$restaurant->slug, $item->product->id]) }}" 
                               class="inline-block mt-2 text-sm text-primary hover:underline font-bold">
                                عرض المنتج ←
                            </a>
                        @else
                            <h4 class="font-bold text-lg text-gray-800">منتج محذوف</h4>
                            <p class="text-sm text-gray-500">الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ر.س</p>
                            <p class="text-xs text-red-500 mt-1">هذا المنتج لم يعد متاحاً</p>
                        @endif
                    </div>
                </div>
                <span class="font-black text-xl text-gray-800">{{ number_format($item->subtotal, 2) }} ر.س</span>
            </div>
        @endforeach
    </div>
</div>

            <!-- QR Code للتتبع السريع -->
            <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 text-center">
                <h3 class="font-bold text-xl mb-4 text-gray-800 flex items-center justify-center gap-2">
                    <span>📱</span>
                    <span>شارك رمز التتبع</span>
                </h3>
                <p class="text-gray-500 text-sm mb-6">امسح هذا الرمز بكاميرا الهاتف للوصول السريع لصفحة التتبع</p>

                <div id="qrcode-track" class="flex justify-center mb-4"></div>

                <div class="flex gap-3 justify-center flex-wrap">
                    <button onclick="downloadTrackQR()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2">
                        <span>💾</span>
                        <span>تحميل الصورة</span>
                    </button>

                    <button onclick="shareTrackQR()"
                        class="btn-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                        <span>📤</span>
                        <span>مشاركة الرابط</span>
                    </button>
                </div>
            </div>
            <!-- زر التقييم (يظهر فقط إذا كان الطلب مكتمل ولم يتم تقييمه) -->
            @if($order->status === 'delivered' && !$order->is_reviewed && $order->delivered_at && $order->delivered_at->diffInMinutes(now()) >= 10)
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-3xl shadow-lg p-8 mb-8 text-center border-2 border-yellow-200">
                    <div class="text-5xl mb-4">⭐</div>
                    <h3 class="text-2xl font-black text-gray-800 mb-2">كيف كانت تجربتك؟</h3>
                    <p class="text-gray-600 mb-6">شاركنا رأيك لنساعدنا على التحسين</p>
                    <a 
                        href="{{ route('review.create', [$restaurant->slug, $order->tracking_code]) }}"
                        class="btn-primary px-8 py-3 rounded-xl font-bold inline-block hover:shadow-xl transition"
                    >
                        قيّم تجربتك الآن
                    </a>
                </div>
            @endif
            <!-- خيارات المساعدة -->
            <div class="text-center">
                <p class="text-gray-500 mb-4">هل لديك استفسار حول هذا الطلب؟</p>
                <div class="flex justify-center gap-4 flex-wrap">
                    <a href="tel:{{ $restaurant->phone }}"
                        class="bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 transition flex items-center gap-2">
                        📞 اتصل بالمطعم
                    </a>
                    <a href="{{ route('restaurant.home', $restaurant->slug) }}"
                        class="btn-primary px-6 py-3 rounded-xl font-bold hover:shadow-lg transition">
                        العودة للرئيسية
                    </a>
                </div>
            </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trackUrl = "{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}";

            new QRCode(document.getElementById("qrcode-track"), {
                text: trackUrl,
                width: 180,
                height: 180,
                colorDark: "{{ $restaurant->primary_color ?? '#FF6B35' }}",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });

        function downloadTrackQR() {
            const canvas = document.querySelector('#qrcode-track canvas');
            if (!canvas) {
                alert('انتظر قليلاً');
                return;
            }
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `tracking-{{ $order->tracking_code }}.png`;
            link.href = url;
            link.click();
        }

        async function shareTrackQR() {
            const trackUrl = "{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}";
            const shareData = {
                title: 'تتبع طلبي من {{ $restaurant->name }}',
                text: `تتبع طلبك برمز: {{ $order->tracking_code }}`,
                url: trackUrl
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else {
                    navigator.clipboard.writeText(trackUrl);
                    alert('تم نسخ رابط التتبع!');
                }
            } catch (err) {
                console.log('Error:', err);
            }
        }
    </script>
@endsection