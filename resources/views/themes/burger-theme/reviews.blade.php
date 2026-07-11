@extends("themes.burger-theme.layout")

@section('content')
<section class="py-16 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <!-- الرأس -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-gray-800 mb-3">تقييمات العملاء</h1>
            <div class="flex items-center justify-center gap-2">
                <div class="flex text-yellow-400 text-2xl">
                    @for($i = 1; $i <= 5; $i++)
                        <span>{{ $i <= round($restaurant->average_rating) ? '⭐' : '☆' }}</span>
                    @endfor
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $restaurant->average_rating }}</span>
                <span class="text-gray-500">({{ $restaurant->reviews_count }} تقييم)</span>
            </div>
        </div>

        <!-- قائمة التقييمات -->
        <div class="space-y-6">
            @forelse($reviews as $review)
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    
                    <!-- رأس التقييم -->
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

                    <!-- التعليق -->
                    @if($review->comment)
                        <p class="text-gray-700 mb-4">{{ $review->comment }}</p>
                    @endif

                    <!-- الصور -->
                    @if($review->images->isNotEmpty())
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            @foreach($review->images as $image)
                                <img 
                                    src="{{ $image->url }}" 
                                    alt="{{ $image->alt }}"
                                    class="aspect-square object-cover rounded-lg cursor-pointer hover:opacity-80 transition"
                                    onclick="openImageModal('{{ $image->url }}')"
                                >
                            @endforeach
                        </div>
                    @endif

                </div>
            @empty
                <div class="text-center py-12 bg-white rounded-2xl">
                    <div class="text-6xl mb-4">💬</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">لا توجد تقييمات بعد</h3>
                    <p class="text-gray-500">كن أول من يقيّم هذا المطعم!</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($reviews->hasPages())
            <div class="mt-8">
                {{ $reviews->links() }}
            </div>
        @endif

    </div>
</section>

<!-- Modal لعرض الصور بالحجم الكامل -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
    <img id="modal-image" src="" class="max-w-full max-h-full rounded-lg">
</div>

<script>
    function openImageModal(url) {
        document.getElementById('modal-image').src = url;
        document.getElementById('image-modal').classList.remove('hidden');
    }

    function closeImageModal() {
        document.getElementById('image-modal').classList.add('hidden');
    }
</script>
@endsection