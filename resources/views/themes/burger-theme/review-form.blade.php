@extends("themes.burger-theme.layout")

@section('content')
<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
            
            <!-- الرأس -->
            <div class="bg-gradient-to-br from-primary to-orange-600 p-8 text-center text-white">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-5xl">⭐</span>
                </div>
                <h1 class="text-3xl font-black mb-2">قيّم تجربتك</h1>
                <p class="text-white text-opacity-90">رأيك يساعدنا على التحسين</p>
            </div>

            <!-- النموذج -->
            <div class="p-8">
                
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('review.store', [$restaurant->slug, $order->tracking_code]) }}" 
                      method="POST" 
                      enctype="multipart/form-data"
                      class="space-y-6">
                    @csrf

                    <!-- التقييم بالنجوم -->
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-3">كيف كانت تجربتك؟</label>
                        <div class="flex gap-2 justify-center" id="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="hidden" required>
                                    <span class="text-5xl text-gray-300 hover:text-yellow-400 transition" data-rating="{{ $i }}">⭐</span>
                                </label>
                            @endfor
                        </div>
                        <p class="text-center text-sm text-gray-500 mt-2">اختر عدد النجوم</p>
                    </div>

                    <!-- التعليق -->
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-3">أخبرنا عن تجربتك (اختياري)</label>
                        <textarea 
                            name="comment" 
                            rows="4" 
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary focus:ring-opacity-20 transition"
                            placeholder="ما الذي أعجبك؟ ما الذي يمكننا تحسينه؟"
                        >{{ old('comment') }}</textarea>
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
                        
                        <!-- معاينة الصور -->
                        <div id="image-preview" class="grid grid-cols-3 gap-3 mt-4"></div>
                    </div>

                    <!-- أزرار الإرسال -->
                    <div class="flex gap-3">
                        <button 
                            type="submit" 
                            class="flex-1 btn-primary py-4 rounded-xl font-bold text-lg hover:shadow-xl transition"
                        >
                            إرسال التقييم ✅
                        </button>
                        <a 
                            href="{{ route('order.track', [$restaurant->slug, $order->tracking_code]) }}"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-4 rounded-xl font-bold text-center transition"
                        >
                            إلغاء
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</section>

<script>
    // 1. تفاعل النجوم
    const stars = document.querySelectorAll('#rating-stars label span');
    const radios = document.querySelectorAll('#rating-stars input[type="radio"]');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('text-gray-300');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-300');
                }
            });
        });
    });

    // 2. رفع الصور ومعاينتها
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('image-preview');
    
    uploadArea.addEventListener('click', () => imageInput.click());
    
    imageInput.addEventListener('change', function(e) {
        previewContainer.innerHTML = '';
        const files = Array.from(e.target.files).slice(0, 5); // حد أقصى 5 صور
        
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
</script>
@endsection