<?php

namespace App\Filament\Restaurant\Resources\Drivers\Pages;

use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $restaurantId = Auth::user()->restaurant_id;
        $profileData = [
            'phone' => $data['phone'] ?? null,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'plate_number' => $data['plate_number'],
            'is_available' => (bool) ($data['is_available'] ?? true),
        ];

        return DB::transaction(function () use ($data, $restaurantId, $profileData): User {
            if (filled($data['existing_user_id'] ?? null)) {
                $user = User::query()
                    ->where('role', 'driver')
                    ->where('restaurant_id', $restaurantId)
                    ->whereKey($data['existing_user_id'])
                    ->first();

                if (! $user) {
                    throw ValidationException::withMessages([
                        'data.existing_user_id' => 'الحساب المحدد غير متاح لهذا المطعم.',
                    ]);
                }

                $profileData['phone'] = $user->phone;
            } else {
                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => $data['password'],
                    'role' => 'driver',
                    'restaurant_id' => $restaurantId,
                    'is_active' => true,
                ];

                $user = User::query()->create($userData);
            }

            $user->driverProfile()->updateOrCreate([], $profileData);

            return $user;
        });
    }
}
