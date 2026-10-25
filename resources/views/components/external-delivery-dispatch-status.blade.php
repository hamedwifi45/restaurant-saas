@php
    $dispatch = $order->deliveryDispatch;
    $statusLabels = [
        'queued' => 'تم تسجيل طلب الإحالة للمراجعة اليدوية',
        'accepted' => 'أكدت الشركة قبول الطلب',
        'picked_up' => 'استلمت الشركة الطلب',
        'delivered' => 'أكدت الشركة إتمام التوصيل',
        'cancelled' => 'تم إلغاء طلب الإحالة',
    ];
@endphp

@if($order->delivery_type === 'delivery' && $dispatch && ! $order->driver_id)
    <div class="mb-6 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-gray-700">
        <p class="font-bold text-gray-900">حالة شركة التوصيل</p>
        <p id="external-delivery-dispatch-status" class="mt-1">
            {{ $statusLabels[$dispatch->status] ?? 'حالة الإحالة غير معروفة' }} · {{ $dispatch->provider?->name }}
        </p>
        @if(filled($dispatch->provider?->phone))
            <a class="mt-2 inline-block font-bold text-primary" href="tel:{{ $dispatch->provider->phone }}">
                {{ $dispatch->provider->phone }}
            </a>
        @endif
        @if(filled($dispatch->provider?->email))
            <a class="mt-2 block font-bold text-primary" href="mailto:{{ $dispatch->provider->email }}">
                {{ $dispatch->provider->email }}
            </a>
        @endif
    </div>

    <script>
        const dispatchStatusElement = document.getElementById('external-delivery-dispatch-status');
        const dispatchStatusLabels = @js($statusLabels);
        const dispatchStatusUrl = @js(route('order.track.location', [
            'slug' => $restaurant->slug,
            'code' => $order->tracking_code,
            'token' => $order->device_token,
        ]));

        async function refreshExternalDeliveryDispatchStatus() {
            try {
                const response = await fetch(dispatchStatusUrl);

                if (!response.ok) {
                    throw new Error(`Dispatch status request failed with ${response.status}.`);
                }

                const data = await response.json();

                if (data.delivery_dispatch) {
                    const statusLabel = dispatchStatusLabels[data.delivery_dispatch.status] ?? 'حالة الإحالة غير معروفة';
                    dispatchStatusElement.textContent = `${statusLabel} · ${data.delivery_dispatch.provider_name ?? ''}`;
                }
            } catch (error) {
                dispatchStatusElement.textContent = 'تعذر تحديث حالة شركة التوصيل حالياً.';
                console.error(error);
            }
        }

        refreshExternalDeliveryDispatchStatus();
        setInterval(refreshExternalDeliveryDispatchStatus, 15000);
    </script>
@endif
