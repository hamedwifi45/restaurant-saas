<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;


class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Restaurant $restaurant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Restaurant $restaurant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        //
    }
    /**
     * عرض الصفحة الرئيسية للمطعم
     */
    public function home($slug)
    {
        // تحميل المطعم مع الثيم
        $restaurant = Restaurant::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // تحديد مسار الثيم (سنستخدم burger-theme كافتراضي حالياً)
        $themePath = 'burger-theme';

        return view("themes.{$themePath}.home", compact('restaurant'));
    }

    /**
     * عرض قائمة الطعام (سنضيفها في المرحلة 4.2)
     */
    public function menu($slug)
    {
        $restaurant = Restaurant::with('theme')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // تحميل التصنيفات النشطة مع منتجاتها المتاحة
        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->with(['products' => function($query) {
                $query->where('is_available', true)
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        $themePath = 'burger-theme';

        return view("themes.{$themePath}.menu", compact('restaurant', 'categories'));
    }
}
