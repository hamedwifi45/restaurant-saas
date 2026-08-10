<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\RestaurantThemeSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Title('تخصيص الثيم')]
class ThemeCustomizer extends Component
{
    use WithFileUploads;

    public ?Theme $theme = null;
    public ?RestaurantThemeSetting $themeSetting = null;
    
    // إعدادات الثيم الحالية
    public array $settings = [];
    
    // تعريفات المتغيرات المسموحة من theme.json
    public array $allowedVariables = [];
    
    // الإعدادات الافتراضية
    public array $defaultSettings = [];
    
    // حالة التحميل
    public bool $isLoading = true;
    
    // رسالة الحفظ
    public string $saveMessage = '';
    
    // قائمة الثيمات المتاحة
    public array $availableThemes = [];
    
    // الثيم المحدد حالياً
    public ?int $selectedThemeId = null;

    protected function rules(): array
    {
        return [
            'selectedThemeId' => 'nullable|exists:themes,id',
            'settings.*' => 'nullable|string|max:255',
        ];
    }

    public function mount()
    {
        $this->authorizeRestaurant();
        
        $restaurant = Auth::user()->restaurant;
        
        if (!$restaurant) {
            abort(403, 'ليس لديك مطعم');
        }

        // تحميل جميع الثيمات النشطة
        $this->availableThemes = Theme::active()
            ->select('id', 'name', 'slug', 'folder_name', 'preview_image', 'default_settings', 'allowed_variables')
            ->get()
            ->map(function ($theme) {
                return [
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'folder_name' => $theme->folder_name,
                    'preview_image' => $theme->preview_image,
                    'default_settings' => $theme->default_settings ?? [],
                    'allowed_variables' => $theme->allowed_variables ?? [],
                ];
            })
            ->toArray();

        // تعيين الثيم الحالي
        $this->selectedThemeId = $restaurant->theme_id;
        
        // تحميل الثيم الحالي
        $this->loadCurrentTheme();
        
        $this->isLoading = false;
    }

    private function authorizeRestaurant()
    {
        if (!Auth::check() || !Auth::user()->restaurant) {
            abort(403, 'ليس لديك مطعم');
        }
    }

    private function loadCurrentTheme()
    {
        $restaurant = Auth::user()->restaurant;
        
        if ($this->selectedThemeId) {
            $this->theme = Theme::find($this->selectedThemeId);
            
            if ($this->theme) {
                $this->allowedVariables = $this->theme->allowed_variables ?? [];
                $this->defaultSettings = $this->theme->default_settings ?? [];
                
                // تحميل إعدادات المطعم المخصصة
                $this->themeSetting = RestaurantThemeSetting::where('restaurant_id', $restaurant->id)
                    ->where('theme_id', $this->theme->id)
                    ->first();
                
                if ($this->themeSetting) {
                    $this->settings = $this->themeSetting->settings ?? [];
                } else {
                    $this->settings = $this->defaultSettings;
                }
            }
        }
        
        // إذا لم يكن هناك ثيم محدد، استخدام الإعدادات الافتراضية
        if (empty($this->settings)) {
            $this->settings = [
                'primary_color' => $restaurant->primary_color ?? '#FF6B35',
                'secondary_color' => $restaurant->secondary_color ?? '#FFFFFF',
                'background_color' => $restaurant->background_color ?? '#1A1A1A',
            ];
        }
    }

    public function selectTheme(int $themeId)
    {
        $this->selectedThemeId = $themeId;
        $this->loadCurrentTheme();
        $this->dispatch('theme-changed');
    }

    public function updateSetting(string $key, $value)
    {
        $this->settings[$key] = $value;
        $this->dispatch('setting-updated', key: $key, value: $value);
    }

    public function resetToDefaults()
    {
        $this->settings = $this->defaultSettings;
        $this->dispatch('settings-reset');
    }

    public function saveSettings()
    {
        $restaurant = Auth::user()->restaurant;
        
        if (!$this->theme) {
            $this->saveMessage = 'يرجى اختيار ثيم أولاً';
            return;
        }

        $this->validate();

        // تصفية الإعدادات المسموحة فقط
        $filteredSettings = [];
        foreach ($this->settings as $key => $value) {
            if (isset($this->allowedVariables[$key])) {
                $filteredSettings[$key] = $value;
            }
        }

        // تحديث أو إنشاء سجل الإعدادات
        RestaurantThemeSetting::updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'theme_id' => $this->theme->id,
            ],
            ['settings' => $filteredSettings]
        );

        // تحديث ألوان المطعم الأساسية أيضاً
        $restaurant->update([
            'primary_color' => $filteredSettings['primary_color'] ?? $restaurant->primary_color,
            'secondary_color' => $filteredSettings['secondary_color'] ?? $restaurant->secondary_color,
            'background_color' => $filteredSettings['background_color'] ?? $restaurant->background_color,
        ]);

        $this->saveMessage = 'تم حفظ تخصيصات الثيم بنجاح!';
        
        $this->dispatch('settings-saved');
        
        // إعادة تعيين الرسالة بعد 3 ثواني
        $this->dispatch('clear-message', delay: 3000);
    }

    public function applyTheme()
    {
        $restaurant = Auth::user()->restaurant;
        
        if (!$this->theme) {
            $this->saveMessage = 'يرجى اختيار ثيم أولاً';
            return;
        }

        $restaurant->update([
            'theme_id' => $this->theme->id,
        ]);

        $this->saveMessage = 'تم تطبيق الثيم بنجاح!';
        $this->dispatch('theme-applied');
    }

    public function getPreviewUrlProperty(): string
    {
        $restaurant = Auth::user()->restaurant;
        return route('restaurant.home', ['slug' => $restaurant->slug]);
    }

    public function render()
    {
        return view('livewire.restaurant.theme-customizer')
            ->layout('layouts.app');
    }
}
