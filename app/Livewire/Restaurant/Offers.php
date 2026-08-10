<?php

namespace App\Livewire\Restaurant;

use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('العروض')]
class Offers extends Component
{
    use WithFileUploads;

    public $search = '';
    public $showAddModal = false;
    public $showEditModal = false;
    public $offerId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $discount_percentage = 0;
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;
    public $image = null;
    public $existingImage = '';
    public $selectedProducts = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'discount_percentage' => 'required|numeric|min:0|max:100',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'is_active' => 'boolean',
        'image' => 'nullable|image|max:2048',
    ];

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

    public function openAddModal()
    {
        $this->resetForm();
        $this->selectedProducts = [];
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    public function openEditModal($offerId)
    {
        $offer = Offer::findOrFail($offerId);
        
        $this->offerId = $offer->id;
        $this->name = $offer->name;
        $this->description = $offer->description;
        $this->discount_percentage = $offer->discount_percentage;
        $this->start_date = $offer->start_date->format('Y-m-d');
        $this->end_date = $offer->end_date->format('Y-m-d');
        $this->is_active = $offer->is_active;
        $this->existingImage = $offer->image;
        $this->selectedProducts = $offer->products()->pluck('product_id')->toArray();
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->discount_percentage = 0;
        $this->start_date = '';
        $this->end_date = '';
        $this->is_active = true;
        $this->image = null;
        $this->existingImage = '';
        $this->selectedProducts = [];
    }

    public function saveOffer()
    {
        $this->validate();

        $restaurant = Auth::user()->restaurant;
        
        $imagePath = $this->existingImage;
        if ($this->image) {
            $imagePath = $this->image->store('offers', 'public');
        }

        $offer = Offer::create([
            'restaurant_id' => $restaurant->id,
            'name' => $this->name,
            'description' => $this->description,
            'discount_percentage' => $this->discount_percentage,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'image' => $imagePath,
        ]);

        if (!empty($this->selectedProducts)) {
            $offer->products()->sync($this->selectedProducts);
        }

        $this->closeAddModal();
        
        session()->flash('success', 'تم إضافة العرض بنجاح');
    }

    public function updateOffer()
    {
        $this->validate();

        $offer = Offer::findOrFail($this->offerId);
        
        $imagePath = $this->existingImage;
        if ($this->image) {
            $imagePath = $this->image->store('offers', 'public');
        }

        $offer->update([
            'name' => $this->name,
            'description' => $this->description,
            'discount_percentage' => $this->discount_percentage,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'image' => $imagePath,
        ]);

        $offer->products()->sync($this->selectedProducts);

        $this->closeEditModal();
        
        session()->flash('success', 'تم تحديث العرض بنجاح');
    }

    public function deleteOffer($offerId)
    {
        $offer = Offer::findOrFail($offerId);
        $offer->delete();
        
        session()->flash('success', 'تم حذف العرض بنجاح');
    }

    public function toggleActive($offerId)
    {
        $offer = Offer::findOrFail($offerId);
        $offer->update(['is_active' => !$offer->is_active]);
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Offer::where('restaurant_id', $restaurant->id)
            ->withCount('products');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $offers = $query->latest()->get();
        $products = $restaurant->products()->get();

        return view('livewire.restaurant.offers', [
            'offers' => $offers,
            'products' => $products,
        ]);
    }
}
