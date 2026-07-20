<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{

    public function showInvoice($slug, $trackingCode)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $order = Order::where('tracking_code', $trackingCode)
            ->where('restaurant_id', $restaurant->id)
            ->with(['items.product', 'offer', 'coupon', 'invoice'])
            ->firstOrFail();
        $themePath = ThemeHelper::getThemePath($restaurant);
        return view('themes.{$themePath}.orders.invoice', compact('restaurant', 'order'));
    }
}
