@if(filled($order->device_token))
<div
    class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 text-right shadow-sm dark:border-slate-700 dark:bg-slate-900"
    data-order-push-subscription
    data-subscription-url="{{ route('order.push-subscriptions.store', ['slug' => $restaurant->slug, 'order' => $order->tracking_code]) }}"
    data-service-worker-url="{{ route('pwa.sw', $restaurant->slug) }}"
    data-service-worker-scope="/{{ $restaurant->slug }}/"
    data-vapid-public-key="{{ config('webpush.vapid.public_key') }}"
    data-device-token="{{ $order->device_token }}"
    data-csrf-token="{{ csrf_token() }}"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-900 dark:text-white">تنبيهات حالة الطلب</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">استقبل تحديثات الطلب ووصول المندوب حتى عند مغادرة الصفحة.</p>
        </div>
        <button
            type="button"
            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
            data-enable-order-push
        >تفعيل التنبيهات</button>
    </div>
    <p class="mt-3 inline-flex items-center gap-2 rounded-full border border-emerald-200/50 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-200/50 dark:bg-emerald-950/40 dark:text-emerald-300" data-order-push-status role="status" aria-live="polite"></p>
</div>
@endif
