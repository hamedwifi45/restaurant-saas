<?php

namespace App\Http\Controllers;

use App\Helpers\ThemeHelper;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // جلب العروض النشطة فقط
        $offers = $restaurant->activeOffers()
            ->orderBy('created_at', 'desc')
            ->get();

        $themePath = ThemeHelper::getThemePath($restaurant);


        return view("themes.{$themePath}.pages.offers", compact('restaurant', 'offers'));
    }
}
