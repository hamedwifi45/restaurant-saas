@extends('themes.oriental-theme.layout')

@section('title', 'شاشة المطبخ | '.$restaurant->name)

@section('content')
    <section class="min-h-screen bg-stone-100 px-3 py-5 sm:px-6" x-data="orientalKds()" x-init="start()">
        <div class="mx-auto max-w-[1600px]">
            <header class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-stone-950 p-5 text-white">
                <div><p class="text-sm text-amber-300">{{ $restaurant->name }}</p><h1 class="text-2xl font-black">شاشة المطبخ</h1></div>
                <div class="flex items-center gap-3"><span class="rounded-full bg-white/10 px-3 py-2 text-sm"><span x-text="orders.length"></span> طلب نشط</span><span class="flex items-center gap-2 text-sm"><span class="h-2.5 w-2.5 rounded-full" :class="connected ? 'bg-emerald-400' : 'bg-stone-500'"></span><span x-text="connected ? 'متصل مباشرة' : 'بانتظار الاتصال'"></span></span></div>
            </header>
            <p x-cloak x-show="message" x-text="message" class="mb-4 rounded-xl bg-red-50 p-3 text-sm font-bold text-red-800" role="status"></p>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="order in orders" :key="order.id">
                    <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" :class="order.status === 'preparing' ? 'border-t-4 border-t-amber-500' : 'border-t-4 border-t-emerald-600'">
                        <div class="flex items-start justify-between gap-3 p-4">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-stone-500">رقم الطلب</p><h2 class="text-xl font-black" x-text="order.tracking_code"></h2></div>
                            <span class="rounded-full px-3 py-1 text-xs font-black" :class="order.status === 'preparing' ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900'" x-text="order.status === 'preparing' ? 'قيد التحضير' : 'طلب جديد'"></span>
                        </div>
                        <div class="border-y border-stone-100 px-4 py-3 text-sm text-stone-600"><span x-text="order.customer_name || 'طلب جديد'"></span><span x-show="order.table" class="mr-3" x-text="'طاولة ' + order.table"></span></div>
                        <ul class="space-y-2 p-4">
                            <template x-for="(item, index) in (order.items || [])" :key="index">
                                <li class="flex justify-between gap-3 text-sm"><span x-text="item.product_name || item.name || 'صنف'"></span><strong x-text="'× ' + (item.quantity || 1)"></strong></li>
                            </template>
                        </ul>
                        <button type="button" @click="advance(order)" class="oriental-primary m-4 mt-0 min-h-12 w-[calc(100%-2rem)] rounded-xl px-4 font-black text-white" x-text="order.status === 'preparing' ? 'إنهاء التحضير' : 'بدء التحضير'"></button>
                    </article>
                </template>
                <div x-cloak x-show="orders.length === 0" class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center text-stone-500">لا توجد طلبات قيد التحضير حالياً.</div>
            </div>
        </div>
    </section>
    @push('scripts')
        <script>
            function orientalKds() {
                return {
                    orders: @json($orderCollection->map(fn ($order) => ['id' => $order->tracking_code, 'tracking_code' => $order->tracking_code, 'status' => $order->status, 'customer_name' => $order->customer_name, 'table' => $order->restaurantTable?->number, 'items' => $order->items->map(fn ($item) => ['product_name' => $item->product_name, 'quantity' => $item->quantity])->values()])->values()),
                    connected: false,
                    message: '',
                    start() {
                        if (!window.Echo) return;
                        this.connected = true;
                        window.Echo.private('restaurant.{{ $restaurant->id }}.kds')
                            .listen('.order.created', event => {
                                const incomingOrder = {
                                    ...event.order,
                                    tracking_code: event.order.tracking_code || event.order.id,
                                    status: event.order.status === 'new' ? 'pending' : event.order.status,
                                    table: event.order.table || event.order.table_number,
                                    items: (event.order.items || []).map(item => ({ ...item, quantity: item.quantity || item.qty }))
                                };
                                if (!this.orders.some(order => order.tracking_code === incomingOrder.tracking_code)) this.orders.unshift(incomingOrder);
                            });
                    },
                    async advance(order) {
                        const previousStatus = order.status;
                        const previousOrders = this.orders.map(item => ({ ...item }));
                        order.status = previousStatus === 'preparing' ? 'ready' : 'preparing';
                        this.orders = this.orders.filter(item => item.status !== 'ready');
                        try {
                            const response = await fetch(@js(route('kitchen.orders.updateStatus', ['orderId' => '__ORDER__'])).replace('__ORDER__', encodeURIComponent(order.tracking_code || order.id)), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ status: previousStatus === 'preparing' ? 'ready' : 'preparing' })
                            });
                            if (!response.ok) throw new Error('تعذر تحديث حالة الطلب.');
                        } catch (error) {
                            this.message = error.message;
                            this.orders = previousOrders;
                        }
                    }
                };
            }
        </script>
    @endpush
@endsection
