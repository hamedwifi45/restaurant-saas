@if($order->driver_id !== null)
    <section
        class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-right shadow-sm"
        data-driver-rating
        data-status-url="{{ route('order.track.location', ['slug' => $restaurant->slug, 'code' => $order->tracking_code, 'token' => $order->device_token]) }}"
        @if($order->status !== 'delivered') hidden @endif
    >
        @if($order->driver_rating !== null)
            <h2 class="font-black text-amber-950">شكراً لتقييمك للمندوب</h2>
            <p class="mt-2 text-sm text-amber-900" aria-label="تقييمك {{ $order->driver_rating }} من 5">
                {{ str_repeat('★', (int) $order->driver_rating) }}{{ str_repeat('☆', 5 - (int) $order->driver_rating) }}
            </p>
        @else
            <h2 class="font-black text-amber-950">كيف كان أداء المندوب؟</h2>
            <p class="mt-1 text-sm text-amber-900">شاركنا تقييمك بعد استلام الطلب.</p>
            <form method="POST" action="{{ route('order.driver-rating.store', ['slug' => $restaurant->slug, 'order' => $order->tracking_code]) }}" class="mt-4">
                @csrf
                <input type="hidden" name="token" value="{{ $order->device_token }}">
                <fieldset>
                    <legend class="sr-only">تقييم المندوب من نجمة إلى خمس نجوم</legend>
                    <div class="flex gap-2" dir="ltr">
                        @for($rating = 1; $rating <= 5; $rating++)
                            <label class="cursor-pointer text-3xl text-amber-400 transition-transform hover:scale-110">
                                <input class="peer sr-only" type="radio" name="rating" value="{{ $rating }}" required aria-label="{{ $rating }} من 5">
                                <span class="peer-checked:text-amber-500 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-amber-700">★</span>
                            </label>
                        @endfor
                    </div>
                </fieldset>
                @error('rating')
                    <p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-4 rounded-xl bg-emerald-600 px-5 py-2.5 font-medium text-white shadow-md transition active:scale-95 hover:bg-emerald-700">
                    إرسال التقييم
                </button>
            </form>
        @endif
    </section>
@endif
