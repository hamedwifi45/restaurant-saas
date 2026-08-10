<?php

namespace App\Livewire\Restaurant;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('التصنيفات')]
class Categories extends Component
{
    public $search = '';
    public $showAddModal = false;
    public $showEditModal = false;
    public $categoryId = null;
    
    // Form fields
    public $name = '';
    public $description = '';
    public $icon = '';
    public $sort_order = 0;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'icon' => 'nullable|string|max:255',
        'sort_order' => 'integer|min:0',
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

    public function openEditModal($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->icon = $category->icon;
        $this->sort_order = $category->sort_order;
        
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
        $this->icon = '';
        $this->sort_order = 0;
    }

    public function saveCategory()
    {
        $this->validate();

        $restaurant = Auth::user()->restaurant;

        Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
        ]);

        $this->closeAddModal();
        
        session()->flash('success', 'تم إضافة التصنيف بنجاح');
    }

    public function updateCategory()
    {
        $this->validate();

        $category = Category::findOrFail($this->categoryId);
        
        $category->update([
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
        ]);

        $this->closeEditModal();
        
        session()->flash('success', 'تم تحديث التصنيف بنجاح');
    }

    public function deleteCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();
        
        session()->flash('success', 'تم حذف التصنيف بنجاح');
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Category::where('restaurant_id', $restaurant->id);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $categories = $query->orderBy('sort_order')->get();

        return view('livewire.restaurant.categories', [
            'categories' => $categories,
        ]);
    }
}
