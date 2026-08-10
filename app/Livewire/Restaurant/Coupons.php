<?php

namespace App\Livewire\Restaurant;

use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('أكواد الخصم')]
class Coupons extends Component
{
    public $search = '';
    public $showAddModal = false;
    public $showEditModal = false;
    public $couponId = null;
    
    // Form fields
    public $code = '';
    public $description = '';
    public $discount_type = 'percentage'; // percentage or fixed
    public $discount_value = 0;
    public $min_order_amount = 0;
    public $max_uses = null;
    public $uses_count = 0;
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;

    protected $rules = [
        'code' => 'required|string|max:50|unique:coupons,code',
        'description' => 'nullable|string',
        'discount_type' => 'required|in:percentage,fixed',
        'discount_value' => 'required|numeric|min:0',
        'min_order_amount' => 'nullable|numeric|min:0',
        'max_uses' => 'nullable|integer|min:1',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'is_active' => 'boolean',
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

    public function openEditModal($couponId)
    {
        $coupon = Coupon::findOrFail($couponId);
        
        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->description = $coupon->description;
        $this->discount_type = $coupon->discount_type;
        $this->discount_value = $coupon->discount_value;
        $this->min_order_amount = $coupon->min_order_amount;
        $this->max_uses = $coupon->max_uses;
        $this->uses_count = $coupon->uses_count;
        $this->start_date = $coupon->start_date->format('Y-m-d');
        $this->end_date = $coupon->end_date->format('Y-m-d');
        $this->is_active = $coupon->is_active;
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->code = '';
        $this->description = '';
        $this->discount_type = 'percentage';
        $this->discount_value = 0;
        $this->min_order_amount = 0;
        $this->max_uses = null;
        $this->uses_count = 0;
        $this->start_date = '';
        $this->end_date = '';
        $this->is_active = true;
    }

    public function saveCoupon()
    {
        $this->validate();

        $restaurant = Auth::user()->restaurant;

        Coupon::create([
            'restaurant_id' => $restaurant->id,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'min_order_amount' => $this->min_order_amount,
            'max_uses' => $this->max_uses,
            'uses_count' => 0,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ]);

        $this->closeAddModal();
        
        session()->flash('success', 'تم إضافة كود الخصم بنجاح');
    }

    public function updateCoupon()
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $this->couponId,
            'discount_value' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::findOrFail($this->couponId);
        
        $coupon->update([
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'min_order_amount' => $this->min_order_amount,
            'max_uses' => $this->max_uses,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
        ]);

        $this->closeEditModal();
        
        session()->flash('success', 'تم تحديث كود الخصم بنجاح');
    }

    public function deleteCoupon($couponId)
    {
        $coupon = Coupon::findOrFail($couponId);
        $coupon->delete();
        
        session()->flash('success', 'تم حذف كود الخصم بنجاح');
    }

    public function toggleActive($couponId)
    {
        $coupon = Coupon::findOrFail($couponId);
        $coupon->update(['is_active' => !$coupon->is_active]);
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Coupon::where('restaurant_id', $restaurant->id);

        if ($this->search) {
            $query->where('code', 'like', '%' . $this->search . '%');
        }

        $coupons = $query->latest()->get();

        return view('livewire.restaurant.coupons', [
            'coupons' => $coupons,
        ]);
    }
}
