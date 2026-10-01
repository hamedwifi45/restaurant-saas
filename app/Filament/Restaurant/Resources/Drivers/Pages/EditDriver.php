<?php

namespace App\Filament\Restaurant\Resources\Drivers\Pages;

use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof User) {
            throw new \LogicException('The driver resource must edit a user account.');
        }

        $profile = $record->driverProfile;

        return array_merge($data, [
            'vehicle_type' => $profile?->vehicle_type,
            'vehicle_model' => $profile?->vehicle_model,
            'plate_number' => $profile?->plate_number,
            'is_available' => $profile ? (bool) $profile->is_available : true,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            throw new \LogicException('The driver resource must update a user account.');
        }

        return DB::transaction(function () use ($record, $data): User {
            $record->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => 'driver',
                'restaurant_id' => Auth::user()->restaurant_id,
            ]);

            if (filled($data['password'] ?? null)) {
                $record->password = $data['password'];
            }

            $record->save();

            $record->driverProfile()->updateOrCreate([], [
                'phone' => $data['phone'] ?? null,
                'vehicle_type' => $data['vehicle_type'],
                'vehicle_model' => $data['vehicle_model'] ?? null,
                'plate_number' => $data['plate_number'],
                'is_available' => (bool) ($data['is_available'] ?? true),
            ]);

            return $record;
        });
    }
}
