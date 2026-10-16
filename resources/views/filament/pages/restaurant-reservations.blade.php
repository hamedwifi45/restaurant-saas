<x-filament-panels::page>
    <div wire:poll.5s class="space-y-8" x-data="{ realtimeState: 'connecting' }" x-on:reservations-realtime-state.window="realtimeState = $event.detail">
        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-950">حالة الطاولات الآن</h2>
                    <p class="mt-1 text-sm text-gray-500">تتحدث تلقائياً عند وصول أو تعديل حجز.</p>
                </div>
                <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold"
                    :class="realtimeState === 'live' ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900'"
                >
                    <span class="size-2 rounded-full" :class="realtimeState === 'live' ? 'bg-green-500' : 'bg-amber-500'"></span>
                    <span x-text="realtimeState === 'live' ? 'تحديث فوري متصل' : 'تحديث تلقائي احتياطي'"></span>
                </span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($this->tableBoard() as $table)
                    @php
                        $stateStyles = match ($table['state']) {
                            'occupied' => 'border-red-200 bg-red-50 text-red-900',
                            'pending' => 'border-amber-200 bg-amber-50 text-amber-950',
                            'inactive' => 'border-gray-200 bg-gray-100 text-gray-600',
                            default => 'border-emerald-200 bg-emerald-50 text-emerald-950',
                        };
                        $stateLabel = match ($table['state']) {
                            'occupied' => 'مشغولة الآن',
                            'pending' => 'حجز بانتظار التأكيد',
                            'inactive' => 'غير متاحة',
                            default => 'متاحة',
                        };
                    @endphp
                    <article wire:key="restaurant-table-{{ $table['id'] }}" class="rounded-2xl border p-5 {{ $stateStyles }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black">
                                    {{ $table['name'] ?: 'طاولة '.$table['number'] }}
                                </h3>
                                <p class="mt-1 text-sm opacity-75">{{ $table['seats'] }} مقاعد</p>
                            </div>
                            <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold">{{ $stateLabel }}</span>
                        </div>

                        @if($table['customer_name'])
                            <div class="mt-4 border-t border-current/10 pt-3 text-sm">
                                <p class="font-bold">{{ $table['customer_name'] }}</p>
                                @if($table['customer_phone'])
                                    <a href="tel:{{ $table['customer_phone'] }}" class="mt-1 inline-block underline">{{ $table['customer_phone'] }}</a>
                                @endif
                                @if($table['reservation'])
                                    <p class="mt-2">
                                        {{ $table['reservation']->dine_in_arrival_at->format('H:i') }}
                                        · {{ $table['reservation_duration'] }} دقيقة
                                        · {{ $table['reservation']->reservation_status === 'confirmed' ? 'مؤكد' : 'بانتظار الموافقة' }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if($table['next_reservation'])
                            <div class="mt-4 rounded-xl bg-white/70 p-3 text-sm">
                                <p class="font-bold">الحجز التالي</p>
                                <p>{{ $table['next_reservation']->dine_in_arrival_at->format('Y-m-d H:i') }}
                                    · {{ $table['next_reservation_duration'] }} دقيقة</p>
                                <p>{{ $table['next_reservation']->customer_name }}</p>
                            </div>
                        @endif

                        @if($table['schedule']->isNotEmpty())
                            <div class="mt-4 space-y-2 border-t border-current/10 pt-3">
                                <p class="text-sm font-bold">جدول الحجوزات</p>
                                @foreach($table['schedule'] as $scheduledReservation)
                                    <div wire:key="table-{{ $table['id'] }}-schedule-{{ $scheduledReservation->id }}" class="rounded-xl bg-white/80 p-3 text-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="font-bold">{{ $scheduledReservation->dine_in_arrival_at->format('Y-m-d H:i') }}</span>
                                            <span>{{ $scheduledReservation->dine_in_duration_minutes ?: $this->restaurant()->getExperimentalOrderFeatures()['reservation_duration_minutes'] }} دقيقة</span>
                                        </div>
                                        <p class="mt-1">{{ $scheduledReservation->customer_name }} · {{ $scheduledReservation->dine_in_guest_count }} ضيوف</p>
                                        <p class="mt-1 text-xs">{{ match ($scheduledReservation->reservation_status) {
                                            'confirmed' => 'مؤكد',
                                            'rejected' => 'مرفوض',
                                            default => 'بانتظار الموافقة',
                                        } }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-500">لم تتم إضافة طاولات لهذا المطعم بعد.</p>
                @endforelse
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-950">طلبات الحجز</h2>
                    <p class="mt-1 text-sm text-gray-500">راجع تفاصيل الموعد والطاولة والعربون، ثم اعتمد الحجز أو ارفضه.</p>
                </div>
                <label class="grid gap-1 text-sm font-semibold text-gray-700">
                    تصفية حسب اليوم (اتركه فارغاً لعرض كل الحجوزات)
                    <span class="flex flex-wrap items-center gap-2">
                        <input type="date" wire:model.live="reservationDate" class="rounded-lg border-gray-300 shadow-sm">
                        <x-filament::button type="button" wire:click="clearDateFilter" color="gray" outlined>
                            عرض الكل
                        </x-filament::button>
                    </span>
                </label>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="divide-y divide-gray-100">
                    @forelse($this->reservations() as $reservation)
                        <article wire:key="reservation-{{ $reservation->id }}" class="space-y-4 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-gray-950">{{ $reservation->customer_name }} · {{ $reservation->tracking_code }}</h3>
                                    <a href="tel:{{ $reservation->customer_phone }}" class="text-sm text-primary underline">{{ $reservation->customer_phone }}</a>
                                </div>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-bold',
                                    'bg-amber-100 text-amber-900' => $reservation->reservation_status === 'pending',
                                    'bg-emerald-100 text-emerald-900' => $reservation->reservation_status === 'confirmed',
                                    'bg-red-100 text-red-900' => $reservation->reservation_status === 'rejected',
                                ])>
                                    {{ match ($reservation->reservation_status) {
                                        'confirmed' => 'مؤكد',
                                        'rejected' => 'مرفوض',
                                        default => 'بانتظار الموافقة',
                                    } }}
                                </span>
                            </div>

                            <div class="grid gap-3 text-sm text-gray-600 sm:grid-cols-2 lg:grid-cols-4">
                                <p><span class="font-semibold text-gray-900">الموعد:</span> {{ $reservation->dine_in_arrival_at->format('Y-m-d H:i') }}</p>
                                <p><span class="font-semibold text-gray-900">المدة:</span> {{ $reservation->dine_in_duration_minutes ?: 90 }} دقيقة</p>
                                <p><span class="font-semibold text-gray-900">الطاولة:</span> {{ $reservation->restaurantTable?->name ?: 'طاولة '.$reservation->restaurantTable?->number }}</p>
                                <p><span class="font-semibold text-gray-900">الضيوف:</span> {{ $reservation->dine_in_guest_count }}</p>
                                <p><span class="font-semibold text-gray-900">العربون:</span> {{ number_format((float) $reservation->deposit_amount, 2) }} {{ $this->restaurant()->currency_symbol }}</p>
                                <p><span class="font-semibold text-gray-900">حالة الدفع:</span> {{ $reservation->payment_status }}</p>
                                <p>
                                    <span class="font-semibold text-gray-900">الإيصال:</span>
                                    @if($reservation->payment_receipt)
                                        <a href="{{ route('restaurant.orders.payment-receipt', $reservation) }}" target="_blank" rel="noopener" class="text-primary underline">عرض الإيصال</a>
                                    @else
                                        غير مرفق
                                    @endif
                                </p>
                                @if($reservation->dine_in_seating_preference)
                                    <p><span class="font-semibold text-gray-900">الجلسة:</span> {{ $reservation->dine_in_seating_preference }}</p>
                                @endif
                                @if($reservation->dine_in_occasion)
                                    <p><span class="font-semibold text-gray-900">المناسبة:</span> {{ $reservation->dine_in_occasion }}</p>
                                @endif
                                @if($reservation->dine_in_high_chair_count)
                                    <p><span class="font-semibold text-gray-900">كراسي الأطفال:</span> {{ $reservation->dine_in_high_chair_count }}</p>
                                @endif
                                @if($reservation->dine_in_stroller_space)
                                    <p>مساحة لعربة أطفال مطلوبة</p>
                                @endif
                                @if($reservation->dine_in_accessibility_notes)
                                    <p class="sm:col-span-2"><span class="font-semibold text-gray-900">متطلبات الوصول:</span> {{ $reservation->dine_in_accessibility_notes }}</p>
                                @endif
                                @if($reservation->dine_in_allergy_notes)
                                    <p class="sm:col-span-2"><span class="font-semibold text-gray-900">الحساسية:</span> {{ $reservation->dine_in_allergy_notes }}</p>
                                @endif
                                @if($reservation->dine_in_dietary_preference)
                                    <p><span class="font-semibold text-gray-900">التفضيل الغذائي:</span> {{ $reservation->dine_in_dietary_preference }}</p>
                                @endif
                                @if($reservation->dine_in_confirmation_call)
                                    <p>العميل يطلب اتصالاً لتأكيد الحجز</p>
                                @endif
                                @if($reservation->dine_in_celebration_notes)
                                    <p class="sm:col-span-2"><span class="font-semibold text-gray-900">تجهيزات المناسبة:</span> {{ $reservation->dine_in_celebration_notes }}</p>
                                @endif
                                @if($reservation->items->isNotEmpty())
                                    <p class="sm:col-span-2">
                                        <span class="font-semibold text-gray-900">الطلب المسبق:</span>
                                        {{ $reservation->items->map(fn ($item): string => $item->name.' × '.$item->quantity)->join('، ') }}
                                    </p>
                                @endif
                            </div>

                            @if($reservation->reservation_status === 'pending')
                                <div class="flex flex-wrap gap-3">
                                    <x-filament::button
                                        wire:click="approveReservation({{ $reservation->id }})"
                                        wire:confirm="هل راجعت الإيصال وتؤكد استلام العربون وتوفر الطاولة؟"
                                        wire:loading.attr="disabled"
                                        color="success"
                                        icon="heroicon-o-check"
                                    >
                                        اعتماد الحجز والعربون
                                    </x-filament::button>
                                    <x-filament::button
                                        wire:click="rejectReservation({{ $reservation->id }})"
                                        wire:confirm="هل تريد رفض هذا الحجز وإبلاغ العميل؟"
                                        wire:loading.attr="disabled"
                                        color="danger"
                                        icon="heroicon-o-x-mark"
                                        outlined
                                    >
                                        رفض الحجز
                                    </x-filament::button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">لا توجد حجوزات مطابقة للتصفية.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    @script
        <script>
            if (!window.Echo && window.EchoFactory) {
                window.Echo = new window.EchoFactory({
                    broadcaster: 'reverb',
                    key: @js(config('broadcasting.connections.reverb.key')),
                    wsHost: @js(config('broadcasting.connections.reverb.options.host')) || window.location.hostname,
                    wsPort: @js((int) config('broadcasting.connections.reverb.options.port', 8080)),
                    wssPort: @js((int) config('broadcasting.connections.reverb.options.port', 443)),
                    forceTLS: @js((bool) config('broadcasting.connections.reverb.options.useTLS', true)),
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: @js(url('/broadcasting/auth')),
                    withCredentials: true,
                    auth: {
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    },
                });
            }

            const reservationsChannel = window.Echo?.private('restaurant.{{ auth()->user()->restaurant_id }}.admin');

            if (reservationsChannel) {
                reservationsChannel
                    .listen('.ReservationUpdated', () => $wire.$refresh())
                    .listen('.OrderStatusUpdated', () => $wire.$refresh())
                    .subscribed(() => window.dispatchEvent(new CustomEvent('reservations-realtime-state', { detail: 'live' })))
                    .error(() => window.dispatchEvent(new CustomEvent('reservations-realtime-state', { detail: 'fallback' })));
            } else {
                window.dispatchEvent(new CustomEvent('reservations-realtime-state', { detail: 'fallback' }));
            }
        </script>
    @endscript
</x-filament-panels::page>
