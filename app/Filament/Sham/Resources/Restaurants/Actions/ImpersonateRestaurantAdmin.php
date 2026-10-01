<?php

namespace App\Filament\Sham\Resources\Restaurants\Actions;

use App\Models\Restaurant;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonateRestaurantAdmin
{
    public function handle(Restaurant $restaurant): ?RedirectResponse
    {
        $owner = $restaurant->restaurantAdmin;

        if (! $restaurant->is_active || ! $owner instanceof User || ! $owner->is_active) {
            Notification::make()->title('لا يوجد حساب مالك نشط لهذا المطعم')->danger()->send();

            return null;
        }

        session(['super_admin_impersonator_id' => Auth::id()]);
        Auth::login($owner);
        request()->session()->regenerate();

        return redirect('/restaurant');
    }
}
