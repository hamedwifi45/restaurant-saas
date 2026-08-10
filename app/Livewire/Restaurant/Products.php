<?php

namespace App\Livewire\Restaurant;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Title('المنتجات')]
class Products extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $showAddModal = false;
    public $showEditModal = false;
    public $productId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $price = '';
    public $discount_price = '';
    public $category_id = '';
    public $is_available = true;
    public $image = null;
    public $existingImage = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'discount_price' => 'nullable|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'is_available' => 'boolean',
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
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    public function openEditModal($productId)
    {
        $product = Product::findOrFail($productId);
        
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->discount_price = $product->discount_price;
        $this->category_id = $product->category_id;
        $this->is_available = $product->is_available;
        $this->existingImage = $product->image;
        
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
        $this->price = '';
        $this->discount_price = '';
        $this->category_id = '';
        $this->is_available = true;
        $this->image = null;
        $this->existingImage = '';
    }

    public function saveProduct()
    {
        $this->validate();

        $restaurant = Auth::user()->restaurant;
        
        $imagePath = $this->existingImage;
        if ($this->image) {
            $imagePath = $this->image->store('products', 'public');
        }

        Product::create([
            'restaurant_id' => $restaurant->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'category_id' => $this->category_id,
            'is_available' => $this->is_available,
            'image' => $imagePath,
        ]);

        $this->closeAddModal();
        
        session()->flash('success', 'تم إضافة المنتج بنجاح');
    }

    public function updateProduct()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($this->productId);
        
        $imagePath = $this->existingImage;
        if ($this->image) {
            $imagePath = $this->image->store('products', 'public');
        }

        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'category_id' => $this->category_id,
            'is_available' => $this->is_available,
            'image' => $imagePath,
        ]);

        $this->closeEditModal();
        
        session()->flash('success', 'تم تحديث المنتج بنجاح');
    }

    public function deleteProduct($productId)
    {
        $product = Product::findOrFail($productId);
        $product->delete();
        
        session()->flash('success', 'تم حذف المنتج بنجاح');
    }

    public function toggleAvailability($productId)
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_available' => !$product->is_available]);
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Product::where('restaurant_id', $restaurant->id)
            ->with('category');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->statusFilter === 'available') {
            $query->where('is_available', true);
        } elseif ($this->statusFilter === 'unavailable') {
            $query->where('is_available', false);
        }

        $products = $query->latest()->paginate(10);
        $categories = $restaurant->categories()->get();

        return view('livewire.restaurant.products', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
