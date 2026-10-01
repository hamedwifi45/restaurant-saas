@extends('themes.oriental-theme.layout')

@section('title', 'إتمام الطلب | '.$restaurant->name)

@section('content')
    @php($currency = $restaurant->currency_symbol ?? 'ل.س')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6" x-data="{ deliveryType: @js($table ? 'dine_in' : 'delivery'), processing: false }">
        <h1 class="text-3xl font-black">إتمام الطلب</h1>
        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-bold">يرجى مراجعة البيانات التالية:</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('checkout.store', $restaurant->slug) }}" method="POST" class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]" @submit="processing = true">
            @csrf
            <div class="space-y-5 rounded-2xl border border-stone-200 bg-white p-5 sm:p-7">
                <fieldset>
                    <legend class="font-black">طريقة الاستلام</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 p-4"><input type="radio" name="delivery_type" value="delivery" x-model="deliveryType" :disabled="{{ $table ? 'true' : 'false' }}" class="accent-amber-800"><span><strong class="block">توصيل</strong><small class="text-stone-500">إلى عنوانك</small></span></label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-stone-200 p-4"><input type="radio" name="delivery_type" value="dine_in" x-model="deliveryType" :disabled="{{ $table ? 'false' : 'true' }}" class="accent-amber-800"><span><strong class="block">تناول في المطعم</strong><small class="text-stone-500">{{ $table ? 'الطاولة '.$table->number : 'امسح رمز الطاولة لطلب الطعام داخل المطعم' }}</small></span></label>
                    </div>
                </fieldset>
                @if($table)<input type="hidden" name="table_id" value="{{ $table->id }}">@endif
                <div><label for="customer-name" class="mb-2 block text-sm font-bold">الاسم الكامل</label><input id="customer-name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required class="w-full rounded-xl border border-stone-300 px-4 py-3 focus:border-amber-800 focus:outline-none"></div>
                <div>
                    <label for="customer-phone" class="mb-2 block text-sm font-bold">رقم الهاتف السوري</label>
                    <input id="customer-phone" type="tel" name="phone" value="{{ old('phone') }}" pattern="(09|\+?9639)\d{8}" title="أدخل رقماً سورياً يبدأ بـ 09 أو +9639" inputmode="tel" autocomplete="tel" required class="w-full rounded-xl border border-stone-300 px-4 py-3 focus:border-amber-800 focus:outline-none">
                    @error('phone')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-stone-500">الصيغة المقبولة: 09XXXXXXXX أو +9639XXXXXXXX</p>
                </div>
                <div x-show="deliveryType === 'delivery'">
                    <label for="customer-address" class="mb-2 block text-sm font-bold">عنوان التوصيل</label>
                    <textarea id="customer-address" name="address" rows="3" @if(!$table) required @endif class="w-full rounded-xl border border-stone-300 px-4 py-3 focus:border-amber-800 focus:outline-none">{{ old('address') }}</textarea>
                    @if(!$table)
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-sm font-bold">خط العرض<input name="latitude" type="number" step="any" value="{{ old('latitude') }}" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2"></label>
                            <label class="text-sm font-bold">خط الطول<input name="longitude" type="number" step="any" value="{{ old('longitude') }}" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2"></label>
                        </div>
                    @endif
                </div>
                <fieldset>
                    <legend class="font-bold">طريقة الدفع</legend>
                    <div class="mt-3 flex flex-wrap gap-4"><label class="flex items-center gap-2"><input type="radio" name="payment_method" value="cash" checked class="accent-amber-800"> نقداً</label><label class="flex items-center gap-2"><input type="radio" name="payment_method" value="transfer" class="accent-amber-800"> تحويل بنكي</label></div>
                </fieldset>
                <div><label for="order-notes" class="mb-2 block text-sm font-bold">ملاحظات إضافية (اختياري)</label><textarea id="order-notes" name="notes" rows="2" class="w-full rounded-xl border border-stone-300 px-4 py-3">{{ old('notes') }}</textarea></div>
            </div>
            <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-5 lg:sticky lg:top-24">
                <h2 class="text-xl font-black">ملخص المبلغ</h2>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between"><span>المجموع الفرعي</span><span>{{ number_format($baseSubtotal, 2) }} {{ $currency }}</span></div>
                    <div class="flex justify-between"><span>الضريبة ({{ $restaurant->tax_percentage ?? 0 }}%)</span><span>{{ number_format($baseTax, 2) }} {{ $currency }}</span></div>
                    @if($deliveryFee > 0)<div class="flex justify-between"><span>رسوم التوصيل</span><span>{{ number_format($deliveryFee, 2) }} {{ $currency }}</span></div>@endif
                    @if($activeOffer)<div class="flex justify-between text-emerald-700"><span>خصم {{ $activeOffer->title }}</span><span>− {{ number_format($autoDiscount, 2) }} {{ $currency }}</span></div>@endif
                </div>
                <div class="mt-4 flex justify-between border-t border-stone-200 pt-4 font-black"><span>الإجمالي</span><span class="oriental-text">{{ number_format($initialFinalTotal, 2) }} {{ $currency }}</span></div>
                <input type="hidden" name="offer_id" value="{{ $activeOffer?->id }}">
                <button type="submit" class="oriental-button mt-6 min-h-12 w-full rounded-xl px-4 py-3 font-black" :disabled="processing"><span x-show="!processing">تأكيد الطلب</span><span x-cloak x-show="processing">جارٍ تجهيز طلبك…</span></button>
                <div x-cloak x-show="processing" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/70 p-4" role="status" aria-live="polite">
                    <div class="rounded-2xl bg-white p-7 text-center shadow-2xl"><span class="oriental-text text-4xl">◌</span><p class="mt-3 text-lg font-black">جارٍ معالجة طلبك</p><p class="mt-1 text-sm text-stone-500">يرجى الانتظار وعدم إغلاق الصفحة.</p></div>
                </div>
            </aside>
        </form>
    </section>
@endsection
