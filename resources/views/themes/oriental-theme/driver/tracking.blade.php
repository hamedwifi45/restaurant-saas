@extends('themes.oriental-theme.layout')

@section('title', 'تتبع التوصيل | '.$restaurant->name)

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6" x-data="driverTracking()" x-init="start()">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="oriental-text font-bold">{{ $restaurant->name }}</p><h1 class="mt-2 text-3xl font-black">تتبع الطلب</h1>
            <p class="mt-2 text-sm text-stone-500">رقم الطلب <strong class="font-mono">{{ $order->tracking_code }}</strong></p>
            <div class="mt-6 rounded-2xl bg-stone-50 p-5">
                <p class="text-sm text-stone-500">حالة الطلب</p><p class="mt-1 text-xl font-black" x-text="statusLabel"></p>
                <p class="mt-3 text-sm text-stone-600">{{ $order->customer_name }} · {{ $order->delivery_address ?: 'لا يوجد عنوان مسجل' }}</p>
                <p class="mt-2 text-xs text-stone-500" x-text="locationMessage"></p>
            </div>
            @if($isDriver)
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button type="button" @click="shareLocation()" :disabled="sharing" class="oriental-button min-h-12 flex-1 rounded-xl px-5 py-3 font-black"><span x-text="sharing ? 'جارٍ تحديث الموقع…' : 'مشاركة موقعي الآن'"></span></button>
                    @if($order->status === 'on_way')
                        <button type="button" @click="updateStatus()" class="rounded-xl border border-emerald-700 px-5 py-3 font-bold text-emerald-800">تم التسليم</button>
                    @endif
                </div>
            @else
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">سيظهر تحديث موقع المندوب هنا عند بدء التوصيل.</div>
            @endif
        </div>
    </section>
    @push('scripts')
        <script>
            function driverTracking() {
                return {
                    status: @js($order->status),
                    sharing: false,
                    locationMessage: 'يتم تحديث حالة الطلب تلقائياً.',
                    get statusLabel() {
                        return ({ pending: 'تم استلام الطلب', preparing: 'قيد التحضير', ready: 'جاهز للاستلام', on_way: 'في الطريق إليك', delivered: 'تم التسليم' })[this.status] || this.status;
                    },
                    start() {
                        window.setInterval(async () => {
                            try {
                                const response = await fetch(@js(route('order.track.location', [$restaurant->slug, $order->tracking_code])), { headers: { Accept: 'application/json' } });
                                if (!response.ok) throw new Error('تعذر جلب تحديث الحالة.');
                                const data = await response.json();
                                this.status = data.status;
                                if (data.driver.updated_at) this.locationMessage = 'آخر تحديث للموقع: ' + new Date(data.driver.updated_at).toLocaleTimeString('ar');
                            } catch (error) {
                                this.locationMessage = error.message;
                            }
                        }, 10000);
                    },
                    shareLocation() {
                        if (!navigator.geolocation) {
                            this.locationMessage = 'المتصفح لا يدعم تحديد الموقع.';
                            return;
                        }
                        this.sharing = true;
                        navigator.geolocation.getCurrentPosition(async position => {
                            try {
                                const response = await fetch(@js(route('driver.location.update', [$slug, $order->id])), {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                    body: JSON.stringify({ latitude: position.coords.latitude, longitude: position.coords.longitude })
                                });
                                const data = await response.json();
                                if (!response.ok) throw new Error(data.message || 'تعذر إرسال الموقع.');
                                this.status = data.status;
                                this.locationMessage = 'تم تحديث موقعك بنجاح.';
                            } catch (error) {
                                this.locationMessage = error.message;
                            } finally {
                                this.sharing = false;
                            }
                        }, error => {
                            this.locationMessage = error.message;
                            this.sharing = false;
                        }, { enableHighAccuracy: true, timeout: 10000 });
                    },
                    async updateStatus() {
                        const response = await fetch(@js(route('driver.status.update', [$slug, $order->id])), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ status: 'delivered' })
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            this.locationMessage = data.message || 'تعذر تحديث حالة الطلب.';
                            return;
                        }
                        this.status = data.status;
                    }
                };
            }
        </script>
    @endpush
@endsection
