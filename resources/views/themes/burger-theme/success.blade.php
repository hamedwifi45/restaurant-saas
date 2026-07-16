@extends("themes.burger-theme.layout")

@section('content')
<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <!-- بطاقة النجاح -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- الرأس -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-8 text-center text-white">
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                    <span class="text-6xl">🎉</span>
                </div>
                <h1 class="text-3xl font-black mb-2">تم استلام طلبك بنجاح!</h1>
                <p class="text-green-50">سنبدأ في تحضير طلبك فوراً</p>
            </div>

            <!-- محتوى البطاقة -->
            <div class="p-8">
                
                <!-- رمز التتبع -->
                <div class="bg-gray-50 p-6 rounded-2xl mb-6 border-2 border-dashed border-gray-200 text-center">
                    <p class="text-sm text-gray-500 mb-2">رمز تتبع الطلب</p>
                    <p class="text-4xl font-mono font-black text-primary tracking-wider">{{ $order->tracking_code }}</p>
                    <button 
                        onclick="copyTrackingCode('{{ $order->tracking_code }}')"
                        class="mt-3 text-sm text-gray-500 hover:text-primary transition flex items-center gap-1 mx-auto"
                    >
                        <span id="copy-icon">📋</span>
                        <span id="copy-text">نسخ الرمز</span>
                    </button>
                </div>

                <!-- QR Code -->
                <div class="bg-white border-2 border-gray-100 rounded-2xl p-6 mb-6 text-center">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center justify-center gap-2">
                        <span>📱</span>
                        <span>امسح الرمز للتتبع المباشر</span>
                    </h3>
                    
                    <!-- حاوية QR Code -->
                    <div id="qrcode" class="flex justify-center mb-4"></div>
                    
                    <p class="text-xs text-gray-400 mb-4">
                        استخدم كاميرا هاتفك لمسح هذا الرمز وفتح صفحة التتبع مباشرة
                    </p>

                    <!-- أزرار الإجراءات -->
                    <div class="flex gap-3 justify-center flex-wrap">
                        <button 
                            onclick="downloadQRCode()"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2"
                        >
                            <span>💾</span>
                            <span>تحميل الصورة</span>
                        </button>
                        
                        <button 
                            onclick="shareQRCode()"
                            class="btn-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2"
                        >
                            <span>📤</span>
                            <span>مشاركة</span>
                        </button>
                    </div>
                </div>
                <!-- معلومات الخصم (إذا تم تطبيقه) -->
                @if($order->discount_amount > 0)
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                        <div class="flex items-center gap-2 text-green-700">
                            <span class="text-2xl">🎉</span>
                            <div>
                                <p class="font-bold">تم تطبيق خصم خاص!</p>
                                <p class="text-sm">وفرت {{ number_format($order->discount_amount, 2) }} ر.س</p>
                            </div>
                        </div>
                    </div>
                @endif
                <!-- معلومات سريعة -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-xl text-center">
                        <div class="text-2xl mb-1">💰</div>
                        <p class="text-xs text-gray-500">المبلغ</p>
                        <p class="font-bold text-gray-800">{{ number_format($order->final_amount, 2) }} ر.س</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl text-center">
                        <div class="text-2xl mb-1">⏱️</div>
                        <p class="text-xs text-gray-500">وقت التوصيل</p>
                        <p class="font-bold text-gray-800">{{ $restaurant->estimated_delivery_time ?? 30 }} دقيقة</p>
                    </div>
                </div>

                <!-- أزرار التنقل -->
                <div class="flex gap-3">
                    <a 
                        href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}"
                        class="flex-1 btn-primary py-3 rounded-xl font-bold text-center hover:shadow-lg transition"
                    >
                        تتبع الطلب 🚚
                    </a>
                    <a 
                        href="{{ route('restaurant.home', $restaurant->slug) }}"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold text-center transition"
                    >
                        الرئيسية
                    </a>
                </div>

            </div>
        </div>

    </div>
</section>

<!-- JavaScript لتوليد QR Code -->
<script>
    // 1. توليد QR Code عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        const trackUrl = "{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}";
        
        new QRCode(document.getElementById("qrcode"), {
            text: trackUrl,
            width: 200,
            height: 200,
            colorDark: "{{ $restaurant->primary_color ?? '#FF6B35' }}",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    });

    // 2. نسخ رمز التتبع
    function copyTrackingCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            document.getElementById('copy-icon').innerText = '✅';
            document.getElementById('copy-text').innerText = 'تم النسخ!';
            setTimeout(() => {
                document.getElementById('copy-icon').innerText = '📋';
                document.getElementById('copy-text').innerText = 'نسخ الرمز';
            }, 2000);
        });
    }

    // 3. تحميل صورة QR Code
    function downloadQRCode() {
        const canvas = document.querySelector('#qrcode canvas');
        if (!canvas) {
            alert('انتظر قليلاً حتى يكتمل توليد الرمز');
            return;
        }
        
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = `tracking-{{ $order->tracking_code }}.png`;
        link.href = url;
        link.click();
    }

    // 4. مشاركة QR Code (عبر Web Share API)
    async function shareQRCode() {
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
                // إذا كان المتصفح لا يدعم المشاركة، انسخ الرابط
                navigator.clipboard.writeText(trackUrl);
                alert('تم نسخ رابط التتبع! يمكنك لصقه ومشاركته');
            }
        } catch (err) {
            console.log('Error sharing:', err);
        }
    }
</script>
@endsection