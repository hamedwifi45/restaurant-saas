<?php

namespace App\Livewire\Restaurant;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('الطلبات')]
class Orders extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showOrderDetails = false;
    public $selectedOrderId = null;

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
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

    public function viewOrder($orderId)
    {
        $this->selectedOrderId = $orderId;
        $this->showOrderDetails = true;
    }

    public function closeOrderDetails()
    {
        $this->showOrderDetails = false;
        $this->selectedOrderId = null;
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);
        
        session()->flash('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        $query = Order::where('restaurant_id', $restaurant->id)
            ->with(['items.product', 'user']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('tracking_code', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->latest()->paginate(15);

        return view('livewire.restaurant.orders', [
            'orders' => $orders,
        ]);
    }
}
