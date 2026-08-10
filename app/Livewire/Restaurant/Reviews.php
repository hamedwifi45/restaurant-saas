<?php

namespace App\Livewire\Restaurant;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('التقييمات')]
class Reviews extends Component
{
    use WithPagination;

    public $search = '';
    public $ratingFilter = 'all';

    public function mount()
    {
        $this->authorizeRestaurant();
    }

    private function authorizeRestaurant()
    {
        if (!Auth::user()->restaurant) {
            abort(403, 'ليس لديك مطعم');
        }
    }

    public function deleteReview($reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $review->delete();
        
        session()->flash('success', 'تم حذف التقييم بنجاح');
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Review::whereHas('order', function($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            })
            ->with(['order.user', 'order']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('comment', 'like', '%' . $this->search . '%')
                  ->orWhereHas('order.user', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->ratingFilter !== 'all') {
            $query->where('rating', $this->ratingFilter);
        }

        $reviews = $query->latest()->paginate(15);

        return view('livewire.restaurant.reviews', [
            'reviews' => $reviews,
        ]);
    }
}
