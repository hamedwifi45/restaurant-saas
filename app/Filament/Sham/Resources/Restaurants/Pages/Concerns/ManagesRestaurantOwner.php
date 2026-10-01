<?php

namespace App\Filament\Sham\Resources\Restaurants\Pages\Concerns;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Arr;

trait ManagesRestaurantOwner
{
    protected ?array $ownerData = null;

    protected function extractOwnerData(array $data): array
    {
        $this->ownerData = Arr::only($data, ['owner_name', 'owner_email', 'owner_password', 'owner_phone']);

        return Arr::except($data, ['owner_name', 'owner_email', 'owner_password', 'owner_phone']);
    }

    protected function saveRestaurantOwner(Restaurant $restaurant): void
    {
        if ($this->ownerData === null) {
            return;
        }

        $owner = $restaurant->restaurantAdmin()->first() ?? new User;
        $owner->fill([
            'name' => $this->ownerData['owner_name'],
            'email' => $this->ownerData['owner_email'],
            'phone' => $this->ownerData['owner_phone'] ?? null,
            'role' => 'restaurant_admin',
            'restaurant_id' => $restaurant->id,
        ]);

        if (filled($this->ownerData['owner_password'] ?? null)) {
            $owner->password = $this->ownerData['owner_password'];
        }

        $owner->save();
    }
}
