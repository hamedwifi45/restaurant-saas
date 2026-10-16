@extends('themes.albasha-burger.layout')

@section('content')

    <!-- Hero Section -->
    <section class="relative py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

                <!-- صورة المنتج -->
                <div x-data="{ activeImage: @js($productImages->first()['url'] ?? null) }">
                    @if ($productImages->isNotEmpty())
                        <img :src="activeImage" src="{{ $productImages->first()['url'] }}"
                            alt="{{ $productImages->first()['alt'] }}" fetchpriority="high"
                            class="h-96 w-full rounded-3xl object-cover shadow-xl">
                        @if ($productImages->count() > 1)
                            <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                                @foreach ($productImages as $image)
                                    <button type="button" @click="activeImage = @js($image['url'])"
                                        :aria-pressed="activeImage === @js($image['url'])"
                                        aria-label="عرض صورة {{ $loop->iteration }} من {{ $product->name }}"
                                        class="shrink-0 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}"
                                            class="h-20 w-20 rounded-xl object-cover ring-1 ring-gray-200">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="flex h-96 w-full items-center justify-center rounded-3xl bg-gray-200 text-6xl">
                            <i class="fa-solid fa-utensils text-gray-400" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>

                <!-- تفاصيل المنتج -->
                <div class="flex flex-col justify-center">
                    <div class="mb-4">
                        <span class="text-primary font-bold text-sm">{{ $product->category->name ?? 'منتج' }}</span>
                    </div>

                    <h1 class="text-4xl font-black text-gray-800 mb-4">{{ $product->name }}</h1>

                    @if ($product->description)
                        <p class="text-lg text-gray-600 mb-6 leading-relaxed">{{ $product->description }}</p>
                    @endif

                    <!-- قسم السعر والتحديد التفاعلي -->
                    <!-- قسم السعر والتحديد التفاعلي الديناميكي -->
                    <div x-data="{
                        priceType: @js($product->price_type ?? 'fixed'),
                        basePrice: @js((float) $product->price),
                        minPrice: @js((float) ($product->min_price ?? 1000)),
                        maxPrice: @js((float) ($product->max_price ?? 50000)),
                        customAmount: @js((float) ($product->min_price ?? ($product->price ?? 1000))),
                        selectedVariantIndex: 0,
                        variants: @js($product->variants ?? []),
                    
                        // توليد 4 أزرار سريعة متناسبة تماماً بين الحد الأدنى والقصى مع التقريب التلقائي
                        get pricePresets() {
                            let min = this.minPrice;
                            let max = this.maxPrice;
                            if (max <= min) return [min];
                    
                            let step = (max - min) / 4;
                            let presets = [];
                    
                            for (let i = 0; i <= 4; i++) {
                                let val = min + (step * i);
                                // التقريب التلقائي حسب قيمة الرقم لتجنب الكسور العشوائية
                                if (max - min <= 2000) {
                                    val = Math.round(val / 50) * 50; // تقريب لأقرب 50 إذا كان النطاق صغير
                                } else if (max - min <= 10000) {
                                    val = Math.round(val / 200) * 200; // تقريب لأقرب 200
                                } else {
                                    val = Math.round(val / 500) * 500; // تقريب لأقرب 500 للمبالغ الكبيرة
                                }
                                presets.push(val);
                            }
                            // إزالة التكرار وضمان الالتزام بالحدين
                            return [...new Set(presets)].filter(v => v >= min && v <= max);
                        },
                    
                        get currentPrice() {
                            if (this.priceType === 'open_price') {
                                return Number(this.customAmount) || 0;
                            }
                            if (this.priceType === 'variant' && this.variants.length > 0) {
                                return Number(this.variants[this.selectedVariantIndex]?.price || 0);
                            }
                            return this.basePrice;
                        },
                    
                        init() {
                            // التأكد من أن القيمة الافتتاحية ضمن المدى المسموح
                            if (this.priceType === 'open_price') {
                                if (this.customAmount < this.minPrice) this.customAmount = this.minPrice;
                                if (this.customAmount > this.maxPrice) this.customAmount = this.maxPrice;
                            }
                        }
                    }" class="mb-6">

                        <!-- السعر التفاعلي المباشر -->
                        <div class="mb-6 flex flex-wrap items-baseline gap-3">
                            <span class="text-4xl font-black text-primary"
                                x-text="currentPrice.toLocaleString('ar-SY') + ' ل.س'"></span>
                            @if ($product->old_price && $product->old_price > $product->price)
                                <span class="text-xl text-gray-400 line-through"
                                    x-show="priceType === 'fixed'">{{ number_format($product->old_price, 2) }} ل.س</span>
                            @endif
                        </div>

                        <!-- 1. واجهة الأوزان المحددة -->
                        <template x-if="priceType === 'variant' && variants.length > 0">
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-gray-700 mb-3">اختر الوزن / الحجم المطلوب
                                    <i class="fa-solid fa-scale-balanced ml-1" aria-hidden="true"></i></label>
                                <div class="grid grid-cols-3 gap-3">
                                    <template x-for="(variant, index) in variants" :key="index">
                                        <button type="button" @click="selectedVariantIndex = index"
                                            :class="selectedVariantIndex === index ?
                                                'border-primary bg-primary/10 text-primary font-black ring-2 ring-primary' :
                                                'border-gray-200 text-gray-700 hover:border-gray-300'"
                                            class="p-3 border-2 rounded-2xl text-center transition flex flex-col items-center justify-center gap-1">
                                            <span class="text-base" x-text="variant.name"></span>
                                            <span class="text-xs font-bold opacity-80"
                                                x-text="Number(variant.price).toLocaleString('ar-SY') + ' ل.س'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- 2. واجهة المبلغ المفتوح (محددة تلقائياً بالحد الأدنى والأقصى) -->
                        <template x-if="priceType === 'open_price'">
                            <div class="mb-6 bg-gray-100/70 p-4 rounded-2xl border border-gray-200">
                                <label class="block text-sm font-bold text-gray-800 mb-2">حدد قيمة الطلب (المبلغ المطلوب)
                                    <i class="fa-solid fa-coins ml-1" aria-hidden="true"></i></label>

                                <!-- أزرار سريعة متناسبة ديناميكياً بين الحد الأدنى والأقصى -->
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <template x-for="preset in pricePresets" :key="preset">
                                        <button type="button" @click="customAmount = preset"
                                            :class="customAmount == preset ? 'bg-primary text-white font-bold' :
                                                'bg-white text-gray-700 border hover:bg-gray-50'"
                                            class="px-3 py-1.5 rounded-xl text-xs transition">
                                            <span x-text="preset.toLocaleString('ar-SY')"></span> ل.س
                                        </button>
                                    </template>
                                </div>

                                <!-- إدخال يدوي مخصص مقيد بالحدود -->
                                <div class="relative">
                                    <input type="number" x-model.number="customAmount" :min="minPrice"
                                        :max="maxPrice" step="100"
                                        class="w-00 w-full px-4 py-3 border-2 border-gray-200 rounded-xl font-black text-lg text-primary focus:border-primary focus:ring-0">
                                    <span
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-bold">ل.س</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    الحد الأدنى: <span x-text="minPrice.toLocaleString('ar-SY')"></span> ل.س | الحد الأقصى:
                                    <span x-text="maxPrice.toLocaleString('ar-SY')"></span> ل.س
                                </p>
                            </div>
                        </template>

                        <!-- نموذج أضف للسلة -->
                        <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST"
                            class="flex flex-col gap-3 sm:flex-row">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="variant_index" :value="selectedVariantIndex"
                                x-if="priceType === 'variant'">
                            <input type="hidden" name="custom_amount" :value="customAmount"
                                x-if="priceType === 'open_price'">

                            <div x-data="{ quantity: 1 }"
                                class="flex items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2">
                                <button type="button" @click="quantity = Math.max(1, quantity - 1)"
                                    aria-label="تقليل الكمية"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 font-bold text-gray-800 transition hover:bg-gray-200">−</button>
                                <input type="number" name="quantity" x-model.number="quantity" min="1"
                                    max="99" required aria-label="الكمية"
                                    class="w-12 border-0 bg-transparent p-0 text-center font-bold text-gray-900 focus:ring-0">
                                <button type="button" @click="quantity = Math.min(99, quantity + 1)"
                                    aria-label="زيادة الكمية"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 font-bold text-gray-800 transition hover:bg-gray-200">+</button>
                            </div>

                            <button type="submit" @disabled(!$restaurant->canAcceptOrders())
                                class="flex-1 btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <span>🛒</span>
                                <span>أضف للسلة</span>
                            </button>
                        </form>
                    </div>
                    <!-- معلومات إضافية -->
                    <div class="grid grid-cols-2 gap-4 mt-8 pt-8 border-t">
                        <div class="flex items-center gap-2 text-gray-600">
                            <span class="text-2xl">⏱️</span>
                            <span>{{ $restaurant->estimated_delivery_time ?? 30 }} دقيقة</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <span class="text-2xl">$ :</span>
                            <span>{{ number_format($restaurant->delivery_fee, 2) }}
                                {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- قسم التقييمات -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-4xl">

            <div class="text-center mb-12">
                <h2 class="text-4xl font-black text-gray-800 mb-3">تقييمات هذا المنتج</h2>
                @if ($reviewCount > 0)
                    <div class="flex items-center justify-center gap-2">
                        <div class="flex text-yellow-400 text-2xl">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="{{ $i <= round($reviewAverage) ? 'fa-solid' : 'fa-regular' }} fa-star" aria-hidden="true"></i>
                            @endfor
                        </div>
                        <span class="text-2xl font-bold text-gray-800">{{ number_format($reviewAverage, 1) }}</span>
                        <span class="text-gray-500">({{ $reviewCount }} تقييم)</span>
                    </div>
                @endif
            </div>

            <!-- زر إضافة تقييم -->
            <div class="text-center mb-8">
                <button onclick="openReviewModal()"
                    class="btn-primary px-8 py-3 rounded-xl font-bold hover:shadow-xl transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <span>أضف تقييمك</span>
                </button>
            </div>

            <!-- قائمة التقييمات -->
            <div class="space-y-6" id="reviews-list">
                @forelse($reviews as $review)
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 bg-primary opacity-10 rounded-full flex items-center justify-center text-xl font-bold text-primary">
                                    {{ mb_substr($review->customer_name, 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">{{ $review->customer_name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex text-yellow-400">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="{{ $i <= $review->rating ? 'fa-solid' : 'fa-regular' }} fa-star" aria-hidden="true"></i>
                                @endfor
                            </div>
                        </div>

                        @if ($review->comment)
                            <p class="text-gray-700 mb-4">{{ $review->comment }}</p>
                        @endif

                        @if ($review->images->isNotEmpty())
                            <div class="grid grid-cols-3 gap-3 mt-4">
                                @foreach ($review->images as $image)
                                    <img src="{{ $image->url }}" alt="{{ $image->alt }}"
                                        class="aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80 transition"
                                        onclick="openImageModal('{{ $image->url }}')">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12 bg-gray-50 rounded-2xl">
                        <i class="fa-solid fa-comments mb-4 text-6xl text-gray-300" aria-hidden="true"></i>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد تقييمات لهذا المنتج بعد</h3>
                        <p class="text-gray-500">كن أول من يقيّم هذا المنتج!</p>
                    </div>
                @endforelse
            </div>

            @if ($reviews->hasPages())
                <div class="mt-8">
                    {{ $reviews->links() }}
                </div>
            @endif

        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="bg-gray-50 py-16">
            <div class="container mx-auto max-w-6xl px-4">
                <div class="mb-8 text-center">
                    <p class="mb-2 font-bold text-primary">{{ $product->category->name ?? 'منتجاتنا' }}</p>
                    <h2 class="text-3xl font-black text-gray-800">منتجات مشابهة قد تعجبك</h2>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $relatedProduct)
                        <article
                            class="overflow-hidden rounded-2xl bg-white shadow-md transition hover:-translate-y-1 hover:shadow-xl">
                            <a href="{{ route('product.show', [$restaurant->slug, $relatedProduct->slug]) }}"
                                aria-label="عرض تفاصيل {{ $relatedProduct->name }}" class="block">
                                @if ($relatedProduct->image)
                                    <img src="{{ media_url($relatedProduct->image) }}" alt="{{ $relatedProduct->name }}"
                                        loading="lazy" decoding="async" class="h-40 w-full object-cover">
                                @else
                                    <div class="flex h-40 items-center justify-center bg-gray-100 text-4xl text-gray-400"><i class="fa-solid fa-utensils" aria-hidden="true"></i></div>
                                @endif
                            </a>
                            <div class="p-4">
                                <a href="{{ route('product.show', [$restaurant->slug, $relatedProduct->id]) }}"
                                    class="font-bold text-gray-800 hover:text-primary">
                                    {{ $relatedProduct->name }}
                                </a>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <span class="font-black text-primary">{{ number_format($relatedProduct->price, 2) }}
                                        {{ $restaurant->currency_symbol ?? 'ل.س' }}</span>
                                    @if(($relatedProduct->price_type ?? 'fixed') === 'fixed')
                                        <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $relatedProduct->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" @disabled(!$restaurant->canAcceptOrders())
                                                aria-label="أضف {{ $relatedProduct->name }} للسلة"
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-primary font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">+</button>
                                        </form>
                                    @else
                                        <a href="{{ route('product.show', [$restaurant->slug, $relatedProduct->slug]) }}"
                                            class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">
                                            اختر السعر
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Modal التقييم -->
    <div id="review-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true">
        <div class="absolute inset-0 z-40 bg-black/50" aria-hidden="true" onclick="closeReviewModal()"></div>
        <div role="dialog" aria-modal="true" aria-labelledby="review-modal-title"
            class="relative z-50 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">

            <!-- الرأس -->
            <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center rounded-t-3xl">
                <h3 id="review-modal-title" class="text-2xl font-black text-gray-800">قيّم هذا المنتج</h3>
                <button type="button" aria-label="إغلاق نافذة التقييم" onclick="closeReviewModal()" class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i></button>
            </div>

            <!-- المحتوى -->
            <div class="p-6">

                <!-- الخطوة 1: التحقق من الرمز -->
                <div id="step-1" class="space-y-6">
                    <div class="text-center mb-6">
                        <div
                            class="w-16 h-16 bg-primary opacity-10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-magnifying-glass text-3xl text-primary" aria-hidden="true"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-800 mb-2">أدخل رمز طلبك</h4>
                        <p class="text-gray-500 text-sm">للتحقق من أنك اشتريت هذا المنتج فعلاً</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">رمز التتبع</label>
                        <input type="text" id="tracking-code-input" placeholder="ORD-XXXXXX"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition text-center text-xl font-mono font-bold uppercase">
                        <p id="tracking-error" class="text-red-500 text-sm mt-2 hidden"></p>
                    </div>

                    <button onclick="verifyTrackingCode()" id="verify-btn"
                        class="w-full btn-primary py-3 rounded-xl font-bold hover:shadow-lg transition">
                        تحقق من الرمز
                    </button>
                </div>

                <!-- الخطوة 2: نموذج التقييم -->
                <div id="step-2" class="space-y-6 hidden">

                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-2 text-green-700">
                            <i class="fa-solid fa-circle-check text-2xl" aria-hidden="true"></i>
                            <span class="font-bold">تم التحقق بنجاح! يمكنك الآن تقييم المنتج</span>
                        </div>
                    </div>

                    <form id="review-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="order_id" id="verified-order-id">
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="restaurant_id" value="{{ $restaurant->id }}">

                        <!-- التقييم بالنجوم -->
                        <div id="rating-stars" x-data="{ rating: 0, hoveredRating: 0 }">
                            <label class="block text-lg font-bold text-gray-800 mb-3">كيف كان هذا المنتج؟</label>
                            <input type="hidden" name="rating" :value="rating">
                            <div class="flex justify-center gap-2 text-5xl" @mouseleave="hoveredRating = 0">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" @mouseenter="hoveredRating = {{ $i }}"
                                        @focus="hoveredRating = {{ $i }}" @blur="hoveredRating = 0"
                                        @click="rating = {{ $i }}"
                                        :aria-pressed="rating === {{ $i }}"
                                        aria-label="{{ $i }} نجوم"
                                        class="cursor-pointer leading-none transition"
                                        :class="(hoveredRating || rating) >= {{ $i }} ? 'text-yellow-400' :
                                            'text-gray-300'"><i class="fa-solid fa-star" aria-hidden="true"></i></button>
                                @endfor
                            </div>
                        </div>

                        <!-- التعليق -->
                        <div>
                            <label class="block text-lg font-bold text-gray-800 mb-3">أخبرنا عن تجربتك (اختياري)</label>
                            <textarea name="comment" rows="4"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition"
                                placeholder="ما الذي أعجبك في هذا المنتج؟"></textarea>
                        </div>

                        <!-- رفع الصور -->
                        <div>
                            <label class="block text-lg font-bold text-gray-800 mb-3">أضف صور (اختياري - حتى 5 صور)</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary transition cursor-pointer"
                                id="upload-area">
                                <input type="file" name="images[]" multiple accept="image/*" class="hidden"
                                    id="image-input">
                                <i class="fa-solid fa-camera mb-2 text-4xl text-gray-400" aria-hidden="true"></i>
                                <p class="text-gray-600 font-bold">اضغط لاختيار الصور</p>
                                <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF حتى 2MB لكل صورة</p>
                            </div>
                            <div id="image-preview" class="grid grid-cols-3 gap-3 mt-4"></div>
                        </div>

                        <!-- أزرار الإرسال -->
                        <div class="flex gap-3 mt-6">
                            <button type="submit" id="submit-review-btn"
                                class="flex-1 btn-primary py-3 rounded-xl font-bold hover:shadow-lg transition">
                                إرسال التقييم <i class="fa-solid fa-check mr-1" aria-hidden="true"></i>
                            </button>
                            <button type="button" onclick="backToStep1()"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition">
                                رجوع
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal لعرض الصور -->
    <div id="image-modal" class="fixed inset-0 bg-black opacity-90 z-50 hidden flex items-center justify-center p-4"
        onclick="closeImageModal()">
        <img id="modal-image" src="" class="max-w-full max-h-full rounded-lg">
    </div>

    <script>
        let previousBodyOverflow = '';

        function openReviewModal() {
            const modal = document.getElementById('review-modal');

            if (modal.classList.contains('hidden')) {
                previousBodyOverflow = document.body.style.overflow;
            }

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeReviewModal() {
            const modal = document.getElementById('review-modal');
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = previousBodyOverflow;
            resetReviewForm();
        }

        function resetReviewForm() {
            document.getElementById('step-1').classList.remove('hidden');
            document.getElementById('step-2').classList.add('hidden');
            document.getElementById('tracking-code-input').value = '';
            document.getElementById('tracking-error').classList.add('hidden');
            document.getElementById('review-form').reset();
            document.getElementById('image-preview').innerHTML = '';

            const ratingControl = document.getElementById('rating-stars');

            if (window.Alpine && ratingControl) {
                const ratingState = window.Alpine.$data(ratingControl);
                ratingState.rating = 0;
                ratingState.hoveredRating = 0;
            }
        }

        async function verifyTrackingCode() {
            const code = document.getElementById('tracking-code-input').value.trim().toUpperCase();
            const errorDiv = document.getElementById('tracking-error');
            const verifyBtn = document.getElementById('verify-btn');

            if (!code) {
                errorDiv.textContent = 'الرجاء إدخال رمز التتبع';
                errorDiv.classList.remove('hidden');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> جاري التحقق...';
            errorDiv.classList.add('hidden');

            try {
                const response = await axios.post('{{ route('review.verify') }}', {
                    tracking_code: code,
                    product_id: {{ $product->id }},
                    restaurant_id: {{ $restaurant->id }}
                });

                if (response.data.success) {
                    document.getElementById('verified-order-id').value = response.data.order_id;
                    document.getElementById('step-1').classList.add('hidden');
                    document.getElementById('step-2').classList.remove('hidden');
                } else {
                    errorDiv.textContent = response.data.message;
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى';
                errorDiv.classList.remove('hidden');
            } finally {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = 'تحقق من الرمز';
            }
        }

        function backToStep1() {
            document.getElementById('step-1').classList.remove('hidden');
            document.getElementById('step-2').classList.add('hidden');
        }

        const uploadArea = document.getElementById('upload-area');
        const imageInput = document.getElementById('image-input');
        const previewContainer = document.getElementById('image-preview');

        uploadArea.addEventListener('click', () => imageInput.click());

        imageInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = Array.from(e.target.files).slice(0, 5);

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative aspect-square rounded-lg overflow-hidden';
                    div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <div class="absolute top-2 right-2 bg-black opacity-50 text-white text-xs px-2 py-1 rounded">
                        صورة ${index + 1}
                    </div>
                `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });

        document.getElementById('review-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (Number(this.querySelector('[name="rating"]').value) === 0) {
                alert('الرجاء اختيار عدد النجوم');
                return;
            }

            const submitBtn = document.getElementById('submit-review-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> جاري الإرسال...';

            const formData = new FormData(this);

            try {
                const response = await axios.post('{{ route('review.store.ajax') }}', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (response.data.success) {
                    alert('شكراً لتقييمك! تم إرساله بنجاح');
                    closeReviewModal();
                    location.reload();
                }
            } catch (error) {
                alert(error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'إرسال التقييم <i class="fa-solid fa-check mr-1" aria-hidden="true"></i>';
            }
        });

        function openImageModal(url) {
            document.getElementById('modal-image').src = url;
            document.getElementById('image-modal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('image-modal').classList.add('hidden');
        }
    </script>
@endsection
