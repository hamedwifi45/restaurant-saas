@props(['order'])

@if($order->dine_in_arrival_at || $order->requested_pickup_at)
    <section class="mb-6 rounded-2xl border border-orange-200 bg-orange-50 p-5 text-orange-950">
        <h3 class="mb-3 flex items-center gap-2 text-lg font-bold">
            <i class="fa-solid {{ $order->requested_pickup_at ? 'fa-store' : 'fa-utensils' }}" aria-hidden="true"></i>
            {{ $order->requested_pickup_at ? 'تفاصيل الاستلام من المطعم' : 'تفاصيل حجز الطاولة' }}
        </h3>
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            @if($order->dine_in_arrival_at)
                <div>
                    <dt class="text-orange-800">موعد الوصول</dt>
                    <dd class="font-bold">{{ $order->dine_in_arrival_at->format('Y-m-d H:i') }}</dd>
                </div>
            @endif
            @if($order->requested_pickup_at)
                <div>
                    <dt class="text-orange-800">موعد الاستلام</dt>
                    <dd class="font-bold">{{ $order->requested_pickup_at->format('Y-m-d H:i') }}</dd>
                </div>
            @endif
            @if($order->restaurantTable)
                <div>
                    <dt class="text-orange-800">الطاولة</dt>
                    <dd class="font-bold">{{ $order->restaurantTable->number }}</dd>
                </div>
            @endif
            @if($order->dine_in_guest_count)
                <div>
                    <dt class="text-orange-800">عدد الضيوف</dt>
                    <dd class="font-bold">{{ $order->dine_in_guest_count }}</dd>
                </div>
            @endif
            @if($order->deposit_amount > 0)
                <div>
                    <dt class="text-orange-800">العربون المحوّل</dt>
                    <dd class="font-bold">{{ number_format((float) $order->deposit_amount, 2) }} ل.س</dd>
                </div>
                <div>
                    <dt class="text-orange-800">حالة الإيصال</dt>
                    <dd class="font-bold">
                        {{ match ($order->payment_status) {
                            'deposit_verified' => 'تم تأكيد استلام العربون',
                            'verified' => 'مدفوع بالكامل',
                            'rejected' => 'الإيصال مرفوض، يرجى التواصل مع المطعم',
                            default => 'بانتظار مراجعة المطعم',
                        } }}
                    </dd>
                </div>
            @endif
            @if($order->dine_in_seating_preference && $order->dine_in_seating_preference !== 'any')
                <div>
                    <dt class="text-orange-800">تفضيل الجلسة</dt>
                    <dd class="font-bold">{{ match ($order->dine_in_seating_preference) {
                        'indoor' => 'داخلية',
                        'outdoor' => 'خارجية',
                        'window' => 'بجانب النافذة',
                        'quiet' => 'مكان هادئ',
                        'family' => 'جلسة عائلية',
                        default => 'بدون تفضيل',
                    } }}</dd>
                </div>
            @endif
            @if($order->dine_in_occasion && $order->dine_in_occasion !== 'none')
                <div>
                    <dt class="text-orange-800">المناسبة</dt>
                    <dd class="font-bold">{{ match ($order->dine_in_occasion) {
                        'birthday' => 'عيد ميلاد',
                        'anniversary' => 'ذكرى سنوية',
                        'family' => 'لقاء عائلي',
                        'business' => 'اجتماع عمل',
                        default => 'أخرى',
                    } }}</dd>
                </div>
            @endif
            @if($order->dine_in_high_chair_count > 0)
                <div>
                    <dt class="text-orange-800">كراسي الأطفال</dt>
                    <dd class="font-bold">{{ $order->dine_in_high_chair_count }}</dd>
                </div>
            @endif
            @if($order->dine_in_stroller_space)
                <div>
                    <dt class="text-orange-800">مساحة عربة أطفال</dt>
                    <dd class="font-bold">مطلوبة</dd>
                </div>
            @endif
            @if($order->dine_in_dietary_preference && $order->dine_in_dietary_preference !== 'none')
                <div>
                    <dt class="text-orange-800">التفضيل الغذائي</dt>
                    <dd class="font-bold">{{ match ($order->dine_in_dietary_preference) {
                        'vegetarian' => 'نباتي',
                        'vegan' => 'نباتي صرف',
                        'gluten_free' => 'خالٍ من الغلوتين',
                        default => 'أخرى',
                    } }}</dd>
                </div>
            @endif
            @if($order->dine_in_confirmation_call)
                <div>
                    <dt class="text-orange-800">تأكيد الحجز</dt>
                    <dd class="font-bold">يرجى الاتصال بالعميل</dd>
                </div>
            @endif
            @if($order->dine_in_allergy_notes)
                <div class="sm:col-span-2">
                    <dt class="text-orange-800">الحساسية والمكونات الواجب تجنبها</dt>
                    <dd class="font-bold">{{ $order->dine_in_allergy_notes }}</dd>
                </div>
            @endif
            @if($order->dine_in_accessibility_notes)
                <div class="sm:col-span-2">
                    <dt class="text-orange-800">متطلبات الوصول أو الحركة</dt>
                    <dd class="font-bold">{{ $order->dine_in_accessibility_notes }}</dd>
                </div>
            @endif
            @if($order->dine_in_celebration_notes)
                <div class="sm:col-span-2">
                    <dt class="text-orange-800">تجهيزات المناسبة</dt>
                    <dd class="font-bold">{{ $order->dine_in_celebration_notes }}</dd>
                </div>
            @endif
        </dl>
    </section>
@endif
