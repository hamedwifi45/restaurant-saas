@extends("themes.burger-theme.layout")

@section('content')

<!-- Hero Section -->
<section class="relative py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- صورة المنتج -->
            <div>
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" 
                         alt="{{ $product->name }}"
                         class="w-full h-96 object-cover rounded-3xl shadow-xl">
                @else
                    <div class="w-full h-96 bg-gray-200 rounded-3xl flex items-center justify-center text-6xl">
                        🍽️
                    </div>
                @endif
            </div>

            <!-- تفاصيل المنتج -->
            <div class="flex flex-col justify-center">
                <div class="mb-4">
                    <span class="text-primary font-bold text-sm">{{ $product->category->name ?? 'منتج' }}</span>
                </div>
                
                <h1 class="text-4xl font-black text-gray-800 mb-4">{{ $product->name }}</h1>
                
                @if($product->description)
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">{{ $product->description }}</p>
                @endif

                <!-- السعر -->
                <div class="flex items-baseline gap-3 mb-6">
                    <span class="text-4xl font-black text-primary">{{ number_format($product->price, 2) }} ر.س</span>
                    @if($product->old_price && $product->old_price > $product->price)
                        <span class="text-xl text-gray-400 line-through">{{ number_format($product->old_price, 2) }} ر.س</span>
                        @php
                            $discount = round((($product->old_price - $product->price) / $product->old_price) * 100);
                        @endphp
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">خصم {{ $discount }}%</span>
                    @endif
                </div>

                <!-- زر إضافة للسلة -->
                <form action="{{ route('cart.add', $restaurant->slug) }}" method="POST" class="flex gap-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="flex-1 btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition flex items-center justify-center gap-2">
                        <span>🛒</span>
                        <span>أضف للسلة</span>
                    </button>
                </form>

                <!-- معلومات إضافية -->
                <div class="grid grid-cols-2 gap-4 mt-8 pt-8 border-t">
                    <div class="flex items-center gap-2 text-gray-600">
                        <span class="text-2xl">⏱️</span>
                        <span>{{ $restaurant->estimated_delivery_time ?? 30 }} دقيقة</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <span class="text-2xl">🚚</span>
                        <span>{{ number_format($restaurant->delivery_fee, 2) }} ر.س</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم التقييمات -->
<!-- قسم التقييمات -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <div class="text-center mb-12">
            <h2 class="text-4xl font-black text-gray-800 mb-3">تقييمات هذا المنتج</h2>
            @if($reviews->count() > 0)
                <div class="flex items-center justify-center gap-2">
                    <div class="flex text-yellow-400 text-2xl">
                        @php $avgRating = $reviews->avg('rating'); @endphp
                        @for($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= round($avgRating) ? '⭐' : '☆' }}</span>
                        @endfor
                    </div>
                    <span class="text-2xl font-bold text-gray-800">{{ number_format($avgRating, 1) }}</span>
                    <span class="text-gray-500">({{ $reviews->count() }} تقييم)</span>
                </div>
            @endif
        </div>

        <!-- زر إضافة تقييم -->
        <div class="text-center mb-8">
            <button 
                onclick="openReviewModal()"
                class="btn-primary px-8 py-3 rounded-xl font-bold hover:shadow-xl transition inline-flex items-center gap-2"
            >
                <span>⭐</span>
                <span>أضف تقييمك</span>
            </button>
        </div>

        <!-- قائمة التقييمات -->
        <div class="space-y-6" id="reviews-list">
            @forelse($reviews as $review)
                <div class="bg-gray-50 rounded-2xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-full flex items-center justify-center text-xl font-bold text-primary">
                                {{ substr($review->customer_name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">{{ $review->customer_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                <span>{{ $i <= $review->rating ? '⭐' : '☆' }}</span>
                            @endfor
                        </div>
                    </div>

                    @if($review->comment)
                        <p class="text-gray-700 mb-4">{{ $review->comment }}</p>
                    @endif

                    @if($review->images->isNotEmpty())
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            @foreach($review->images as $image)
                                <img src="{{ $image->url }}" 
                                     alt="{{ $image->alt }}"
                                     class="aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80 transition"
                                     onclick="openImageModal('{{ $image->url }}')">
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12 bg-gray-50 rounded-2xl">
                    <div class="text-6xl mb-4">💬</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد تقييمات لهذا المنتج بعد</h3>
                    <p class="text-gray-500">كن أول من يقيّم هذا المنتج!</p>
                </div>
            @endforelse
        </div>

        @if($reviews->hasPages())
            <div class="mt-8">
                {{ $reviews->links() }}
            </div>
        @endif

    </div>
</section>

<!-- Modal التقييم -->
<div id="review-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        
        <!-- الرأس -->
        <div class="sticky top-0 bg-white border-b p-6 flex justify-between items-center rounded-t-3xl">
            <h3 class="text-2xl font-black text-gray-800">قيّم هذا المنتج</h3>
            <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 text-3xl">×</button>
        </div>

        <!-- المحتوى -->
        <div class="p-6">
            
            <!-- الخطوة 1: التحقق من الرمز -->
            <div id="step-1" class="space-y-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🔍</span>
                    </div>
                    <h4 class="text-xl font-bold text-gray-800 mb-2">أدخل رمز طلبك</h4>
                    <p class="text-gray-500 text-sm">للتحقق من أنك اشتريت هذا المنتج فعلاً</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">رمز التتبع</label>
                    <input 
                        type="text" 
                        id="tracking-code-input"
                        placeholder="ORD-XXXXXX"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition text-center text-xl font-mono font-bold uppercase"
                    >
                    <p id="tracking-error" class="text-red-500 text-sm mt-2 hidden"></p>
                </div>

                <button 
                    onclick="verifyTrackingCode()"
                    id="verify-btn"
                    class="w-full btn-primary py-3 rounded-xl font-bold hover:shadow-lg transition"
                >
                    تحقق من الرمز
                </button>
            </div>

            <!-- الخطوة 2: نموذج التقييم -->
            <div id="step-2" class="space-y-6 hidden">
                
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-2 text-green-700">
                        <span class="text-2xl">✅</span>
                        <span class="font-bold">تم التحقق بنجاح! يمكنك الآن تقييم المنتج</span>
                    </div>
                </div>

                <form id="review-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_id" id="verified-order-id">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="restaurant_id" value="{{ $restaurant->id }}">

                    <!-- التقييم بالنجوم -->
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-3">كيف كان هذا المنتج؟</label>
                        <div class="flex gap-2 justify-center" id="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="hidden" required>
                                    <span class="text-5xl text-gray-300 hover:text-yellow-400 transition" data-rating="{{ $i }}">⭐</span>
                                </label>
                            @endfor
                        </div>
                    </div>

                    <!-- التعليق -->
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-3">أخبرنا عن تجربتك (اختياري)</label>
                        <textarea 
                            name="comment" 
                            rows="4" 
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition"
                            placeholder="ما الذي أعجبك في هذا المنتج؟"
                        ></textarea>
                    </div>

                    <!-- رفع الصور -->
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-3">أضف صور (اختياري - حتى 5 صور)</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary transition cursor-pointer" id="upload-area">
                            <input 
                                type="file" 
                                name="images[]" 
                                multiple 
                                accept="image/*"
                                class="hidden"
                                id="image-input"
                            >
                            <div class="text-4xl mb-2">📷</div>
                            <p class="text-gray-600 font-bold">اضغط لاختيار الصور</p>
                            <p class="text-sm text-gray-400 mt-1">PNG, JPG, GIF حتى 2MB لكل صورة</p>
                        </div>
                        <div id="image-preview" class="grid grid-cols-3 gap-3 mt-4"></div>
                    </div>

                    <!-- أزرار الإرسال -->
                    <div class="flex gap-3 mt-6">
                        <button 
                            type="submit"
                            id="submit-review-btn"
                            class="flex-1 btn-primary py-3 rounded-xl font-bold hover:shadow-lg transition"
                        >
                            إرسال التقييم ✅
                        </button>
                        <button 
                            type="button"
                            onclick="backToStep1()"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition"
                        >
                            رجوع
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal لعرض الصور -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
    <img id="modal-image" src="" class="max-w-full max-h-full rounded-lg">
</div>

<script>
    // متغيرات عامة
    let selectedRating = 0;

    // فتح/إغلاق Modal التقييم
    function openReviewModal() {
        document.getElementById('review-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeReviewModal() {
        document.getElementById('review-modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        resetReviewForm();
    }

    function resetReviewForm() {
        document.getElementById('step-1').classList.remove('hidden');
        document.getElementById('step-2').classList.add('hidden');
        document.getElementById('tracking-code-input').value = '';
        document.getElementById('tracking-error').classList.add('hidden');
        document.getElementById('review-form').reset();
        document.getElementById('image-preview').innerHTML = '';
        selectedRating = 0;
        updateStars(0);
    }

    // التحقق من رمز التتبع
    async function verifyTrackingCode() {
        const code = document.getElementById('tracking-code-input').value.trim().toUpperCase();
        const errorDiv = document.getElementById('tracking-error');
        const verifyBtn = document.getElementById('verify-btn');

        if (!code) {
            errorDiv.textContent = 'الرجاء إدخال رمز التتبع';
            errorDiv.classList.remove('hidden');
            return;
        }

        // حالة التحميل
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="animate-spin">⏳</span> جاري التحقق...';
        errorDiv.classList.add('hidden');

        try {
            const response = await axios.post('{{ route("review.verify") }}', {
                tracking_code: code,
                product_id: {{ $product->id }},
                restaurant_id: {{ $restaurant->id }}
            });

            if (response.data.success) {
                // نجاح التحقق
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

    // تفاعل النجوم
    const stars = document.querySelectorAll('#rating-stars label span');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            selectedRating = rating;
            updateStars(rating);
            document.querySelector(`input[name="rating"][value="${rating}"]`).checked = true;
        });
    });

    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    // رفع الصور
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
                    <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                        صورة ${index + 1}
                    </div>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });

    // إرسال التقييم
    document.getElementById('review-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (selectedRating === 0) {
            alert('الرجاء اختيار عدد النجوم');
            return;
        }

        const submitBtn = document.getElementById('submit-review-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin">⏳</span> جاري الإرسال...';

        const formData = new FormData(this);

        try {
            const response = await axios.post('{{ route("review.store.ajax") }}', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (response.data.success) {
                // عرض رسالة نجاح
                alert('شكراً لتقييمك! تم إرساله بنجاح');
                closeReviewModal();
                
                // إضافة التقييم الجديد للقائمة (اختياري - يمكن إعادة تحميل الصفحة)
                location.reload();
            }
        } catch (error) {
            alert(error.response?.data?.message || 'حدث خطأ، حاول مرة أخرى');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'إرسال التقييم ✅';
        }
    });

    // Modal الصور
    function openImageModal(url) {
        document.getElementById('modal-image').src = url;
        document.getElementById('image-modal').classList.remove('hidden');
    }

    function closeImageModal() {
        document.getElementById('image-modal').classList.add('hidden');
    }
</script>
@endsection
