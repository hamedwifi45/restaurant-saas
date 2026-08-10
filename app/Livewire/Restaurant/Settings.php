<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('إعدادات المطعم')]
class Settings extends Component
{
    use WithFileUploads;

    public $name = '';
    public $slug = '';
    public $subdomain = '';
    public $description = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $delivery_fee = 0;
    public $estimated_delivery_time = 30;
    public $primary_color = '#FF6B35';
    public $secondary_color = '#2E86AB';
    public $background_color = '#FFFFFF';
    public $logo = null;
    public $cover_image = null;
    public $qr_code_image = null;
    public $bank_details = '';
    public $existingLogo = '';
    public $existingCoverImage = '';
    public $existingQrCode = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:100|unique:restaurants,slug',
        'description' => 'nullable|string',
        'phone' => 'required|string|max:20',
        'email' => 'required|email|max:255',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'delivery_fee' => 'nullable|numeric|min:0',
        'estimated_delivery_time' => 'nullable|integer|min:1',
        'primary_color' => 'nullable|string|max:7',
        'secondary_color' => 'nullable|string|max:7',
        'background_color' => 'nullable|string|max:7',
        'logo' => 'nullable|image|max:2048',
        'cover_image' => 'nullable|image|max:4096',
        'bank_details' => 'nullable|string',
    ];

    public function mount()
    {
        $this->authorizeRestaurant();
        
        $restaurant = Auth::user()->restaurant;
        
        if ($restaurant) {
            $this->name = $restaurant->name;
            $this->slug = $restaurant->slug;
            $this->subdomain = $restaurant->subdomain ?? '';
            $this->description = $restaurant->description ?? '';
            $this->phone = $restaurant->phone ?? '';
            $this->email = $restaurant->email ?? '';
            $this->address = $restaurant->address ?? '';
            $this->city = $restaurant->city ?? '';
            $this->delivery_fee = $restaurant->delivery_fee ?? 0;
            $this->estimated_delivery_time = $restaurant->estimated_delivery_time ?? 30;
            $this->primary_color = $restaurant->primary_color ?? '#FF6B35';
            $this->secondary_color = $restaurant->secondary_color ?? '#2E86AB';
            $this->background_color = $restaurant->background_color ?? '#FFFFFF';
            $this->existingLogo = $restaurant->logo ?? '';
            $this->existingCoverImage = $restaurant->cover_image ?? '';
            $this->existingQrCode = $restaurant->qr_code_image ?? '';
            $this->bank_details = $restaurant->bank_details ?? '';
        }
    }

    private function authorizeRestaurant()
    {
        if (!Auth::user()->restaurant) {
            abort(403, 'ليس لديك مطعم');
        }
    }

    public function saveSettings()
    {
        $restaurant = Auth::user()->restaurant;
        
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:restaurants,slug,' . $restaurant->id,
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        $logoPath = $this->existingLogo;
        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        $coverImagePath = $this->existingCoverImage;
        if ($this->cover_image) {
            $coverImagePath = $this->cover_image->store('covers', 'public');
        }

        $qrCodePath = $this->existingQrCode;
        if ($this->qr_code_image) {
            $qrCodePath = $this->qr_code_image->store('qrcodes', 'public');
        }

        $restaurant->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'subdomain' => $this->subdomain,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'delivery_fee' => $this->delivery_fee,
            'estimated_delivery_time' => $this->estimated_delivery_time,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'background_color' => $this->background_color,
            'logo' => $logoPath,
            'cover_image' => $coverImagePath,
            'qr_code_image' => $qrCodePath,
            'bank_details' => $this->bank_details,
        ]);

        session()->flash('success', 'تم حفظ الإعدادات بنجاح');
    }

    public function render()
    {
        return view('livewire.restaurant.settings');
    }
}
