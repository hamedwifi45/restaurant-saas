@extends('themes.oriental-theme.layout')

@section('title', 'سلة التسوق | '.$restaurant->name)

@section('content')
    @php($currency = $restaurant->currency_symbol ?? 'ل.س')
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6" x-data="cartPage()">
        <h1 class="text-3xl font-black">سلة التسوق</h1>
        <p x-cloak x-show="message" x-text="message" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="status"></p>
        @if(empty($products))
            <div class="mt-6 rounded-3xl border border-stone-200 bg-white px-5 py-16 text-center">
                <span class="text-6xl">🛍️</span><h2 class="mt-4 text-2xl font-black">سلتك فارغة</h2><p class="mt-2 text-stone-500">تصفح قائمتنا واختر أطباقك المفضلة.</p>
                <a href="{{ route('restaurant.menu', $restaurant->slug) }}" class="oriental-button mt-6 inline-flex rounded-xl px-6 py-3 font-black">تصفح القائمة</a>
            </div>
        @else
            <div class="mt-7 grid gap-6 lg:grid-cols-[1fr_20rem]">
                <div class="space-y-3">
                    @foreach($products as $item)
                        <article x-show="items[{{ $item['product']->id }}] && items[{{ $item['product']->id }}].quantity > 0" class="flex flex-wrap items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 sm:flex-nowrap">
                            @if($item['product']->image)
                                <img src="{{ media_url($item['product']->image) }}" alt="{{ $item['product']->name }}" class="h-20 w-20 rounded-xl object-cover">
                            @else
                                <span class="flex h-20 w-20 items-center justify-center rounded-xl bg-amber-50 text-3xl">🍽️</span>
                            @endif
                            <div class="min-w-0 flex-1"><h2 class="font-black">{{ $item['product']->name }}</h2><p class="oriental-text mt-1 font-bold">{{ number_format($item['price'], 2) }} {{ $currency }}</p></div>
                            <form action="{{ route('cart.update', $restaurant->slug) }}" method="POST" class="flex items-center gap-2" @submit.prevent="updateQuantity($event)">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                <button type="submit" name="quantity" :value="Math.max(1, items[{{ $item['product']->id }}].quantity - 1)" :disabled="busyId === {{ $item['product']->id }}" aria-label="تقليل الكمية" class="h-10 w-10 rounded-lg border border-stone-200 font-bold disabled:opacity-50">−</button>
                                <output class="w-8 text-center font-black" x-text="items[{{ $item['product']->id }}].quantity">{{ $item['qty'] }}</output>
                                <button type="submit" name="quantity" :value="items[{{ $item['product']->id }}].quantity + 1" :disabled="busyId === {{ $item['product']->id }}" aria-label="زيادة الكمية" class="h-10 w-10 rounded-lg border border-stone-200 font-bold disabled:opacity-50">+</button>
                            </form>
                            <form action="{{ route('cart.remove', $restaurant->slug) }}" method="POST" @submit.prevent="removeItem($event)">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                <button type="submit" :disabled="busyId === {{ $item['product']->id }}" aria-label="حذف {{ $item['product']->name }}" class="rounded-lg px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50 disabled:opacity-50">حذف</button>
                            </form>
                        </article>
                    @endforeach
                    <p x-cloak x-show="Object.keys(items).length === 0" class="rounded-2xl border border-stone-200 bg-white p-8 text-center text-stone-500">أصبحت سلتك فارغة.</p>
                </div>
                <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-5 lg:sticky lg:top-24">
                    <h2 class="text-xl font-black">ملخص الطلب</h2>
                    <div class="mt-5 flex justify-between text-sm"><span class="text-stone-600">المجموع الفرعي</span><span><span x-text="money(subtotal())">{{ number_format($total, 2) }}</span> {{ $currency }}</span></div>
                    <div class="mt-3 flex justify-between text-sm"><span class="text-stone-600">الضريبة ({{ $restaurant->tax_percentage ?? 0 }}%)</span><span><span x-text="money(subtotal() * taxRate / 100)">{{ number_format($total * (($restaurant->tax_percentage ?? 0) / 100), 2) }}</span> {{ $currency }}</span></div>
                    <div class="mt-4 flex justify-between border-t border-stone-200 pt-4 text-lg font-black"><span>الإجمالي</span><span class="oriental-text"><span x-text="money(subtotal() * (1 + taxRate / 100))">{{ number_format($total * (1 + (($restaurant->tax_percentage ?? 0) / 100)), 2) }}</span> {{ $currency }}</span></div>
                    <a href="{{ route('checkout', $restaurant->slug) }}" class="oriental-button mt-5 block rounded-xl px-4 py-3 text-center font-black" :class="Object.keys(items).length === 0 ? 'pointer-events-none opacity-50' : ''">متابعة إلى الدفع</a>
                    <p x-cloak x-show="busyId !== null" class="mt-3 text-center text-xs text-stone-500" role="status">جارٍ تحديث السلة…</p>
                </aside>
            </div>
        @endif
    </section>
    @push('scripts')
        <script>
            function cartPage() {
                return {
                    items: @js(collect($products)->mapWithKeys(fn ($item) => [$item['product']->id => ['quantity' => (int) $item['qty'], 'price' => (float) $item['price']]])->all()),
                    taxRate: @js((float) ($restaurant->tax_percentage ?? 0)),
                    busyId: null,
                    message: '',
                    subtotal() {
                        return Object.values(this.items).reduce((sum, item) => sum + item.price * item.quantity, 0);
                    },
                    money(amount) {
                        return Number(amount).toFixed(2);
                    },
                    async send(form, submitter) {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: submitter ? new FormData(form, submitter) : new FormData(form)
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'تعذر تحديث السلة.');
                        return result;
                    },
                    async updateQuantity(event) {
                        const form = event.currentTarget;
                        const productId = form.elements.product_id.value;
                        this.busyId = Number(productId);
                        this.message = '';
                        try {
                            const result = await this.send(form, event.submitter);
                            this.items[result.product_id].quantity = result.quantity;
                        } catch (error) {
                            this.message = error.message;
                        } finally {
                            this.busyId = null;
                        }
                    },
                    async removeItem(event) {
                        const form = event.currentTarget;
                        this.busyId = Number(form.elements.product_id.value);
                        this.message = '';
                        try {
                            const result = await this.send(form);
                            delete this.items[result.product_id];
                        } catch (error) {
                            this.message = error.message;
                        } finally {
                            this.busyId = null;
                        }
                    }
                };
            }
        </script>
    @endpush
@endsection
