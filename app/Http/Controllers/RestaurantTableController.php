<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantTableController extends Controller
{
    public function __construct(private readonly RestaurantTableSession $tableSession) {}

    public function enter(string $slug, string $token): RedirectResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $table = $restaurant->tables()
            ->where('is_active', true)
            ->where(function ($query) use ($token) {
                $query->where('qr_token', $token);

                if (ctype_digit($token)) {
                    $query->orWhere('number', $token)->orWhere('id', (int) $token);
                }
            })
            ->firstOrFail();

        $this->tableSession->remember($restaurant, $table);

        return redirect()->route('restaurant.menu', $restaurant->slug);
    }

    public function leave(string $slug): RedirectResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $this->tableSession->forget();

        return redirect()->route('restaurant.menu', $restaurant->slug);
    }

    public function qr(Request $request, RestaurantTable $table): View
    {
        abort_unless($request->user()?->restaurant_id === $table->restaurant_id, 403);

        return view('restaurant.table-qr', [
            'table' => $table->load('restaurant'),
            'url' => $table->qrUrl(),
        ]);
    }
}
