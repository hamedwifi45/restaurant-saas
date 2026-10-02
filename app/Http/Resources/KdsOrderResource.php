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
                    'name' => $item->product_name,
                    'qty' => $item->quantity,
                ];
            }),
        ];
    }
}
