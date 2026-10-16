<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KdsOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->tracking_code ?? $this->id, // استخدام ORD-POMXZW أو المعرف
            'db_id' => $this->id,
            'type' => $this->delivery_type === 'dine_in' ? 'dine-in' : $this->delivery_type,
            'table_number' => $this->restaurantTable?->number,
            'dine_in_arrival_at' => $this->dine_in_arrival_at?->toIso8601String(),
            'dine_in_duration_minutes' => $this->dine_in_duration_minutes,
            'reservation_status' => $this->reservation_status,
            'requested_pickup_at' => $this->requested_pickup_at?->toIso8601String(),
            'deposit_amount' => (float) $this->deposit_amount,
            'payment_status' => $this->payment_status,
            'dine_in_guest_count' => $this->dine_in_guest_count,
            'dine_in_allergy_notes' => $this->dine_in_allergy_notes,
            'dine_in_confirmation_call' => $this->dine_in_confirmation_call,
            'customer_name' => $this->customer_name,
            'customer_lat' => $this->customer_lat ?? $this->delivery_latitude,
            'customer_lng' => $this->customer_lng ?? $this->delivery_longitude,
            'distance_km' => $this->distance_km,
            'driver_name' => $this->driver?->name,
            // تحويل حالة pending في لارافيل إلى new للسكربت
            'status' => $this->status === 'pending' ? 'new' : $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'notes' => $this->notes ?? '',
            'items' => $this->items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'variant_name' => $item->variant_name,
                    'qty' => $item->quantity,
                    'unit_price' => (float) $item->price,
                    'total' => (float) $item->total,
                ];
            }),
        ];
    }
}
