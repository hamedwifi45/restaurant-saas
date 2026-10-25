<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PosReceiptController extends Controller
{
    public function show(Request $request, string $slug, string $trackingCode): View
    {
        Gate::authorize('access-pos', $slug);
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->whereKey($request->user()?->restaurant_id)
            ->firstOrFail();

        return $this->receipt($restaurant, $trackingCode);
    }

    private function receipt(Restaurant $restaurant, string $trackingCode): View
    {
        $order = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('tracking_code', $trackingCode)
            ->with(['items', 'restaurantTable', 'invoice'])
            ->firstOrFail();

        return view('pos.receipt', [
            'restaurant' => $restaurant,
            'order' => $order,
            'pageTitle' => 'إيصال '.$order->tracking_code,
            'newOrderUrl' => route('pos.index', ['slug' => $restaurant->slug]),
        ]);
    }
}
