<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\RestaurantThemeSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class RestaurantThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public string $view = 'filament.pages.restaurant-theme-settings';

    protected static ?string $title = 'إعدادات المظهر';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'إعدادات المظهر';

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'theme-settings';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $restaurant = auth()->user()?->restaurant;

        if ($restaurant) {
            $setting = RestaurantThemeSetting::where('restaurant_id', $restaurant->id)->first();
            // تعبئة النموذج بالبيانات المحفوظة سابقاً
            $this->form->fill($setting?->settings ?? []);
        } else {
            $this->form->fill();
        }
    }

    /**
     * إضافة زر "إرجاع للوضع الافتراضي" في أعلى الصفحة
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetToDefaults')
                ->label('إرجاع للوضع الافتراضي')
                ->icon('heroicon-m-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('استعادة الإعدادات الافتراضية')
                ->modalDescription('هل أنت ممتأكد من رغبتك في إعادة جميع الألوان والتنسيقات إلى الوضع الافتراضي للثيم؟')
                ->action(function () {
                    $restaurant = auth()->user()?->restaurant;

                    if (! $restaurant) return;

                    // جلب الإعدادات الافتراضية المحددة في الثيم
                    $allowed = $restaurant->theme?->allowed_variables ?? [];
                    $defaults = [];

                    foreach ($allowed as $varKey => $varData) {
                        $defaults[$varKey] = $varData['default'] ?? null;
                    }

                    // تحديث قاعدة البيانات بالقيم الافتراضية
                    RestaurantThemeSetting::updateOrCreate(
                        ['restaurant_id' => $restaurant->id],
                        [
                            'theme_id' => $restaurant->theme_id,
                            'settings' => $defaults,
                        ]
                    );

                    // تفريغ كاش المطعم فوراً
                    Cache::forget("restaurant_theme_settings_{$restaurant->id}");

                    // إعادة تعبئة النموذج بالبيانات الافتراضية
                    $this->form->fill($defaults);

                    Notification::make()
                        ->title('تمت إعادة الإعدادات الافتراضية بنجاح')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $restaurant = auth()->user()?->restaurant;
        $theme = $restaurant?->theme;

        if (! $theme || empty($theme->allowed_variables)) {
            return $schema
                ->schema([
                    Section::make('خيارات المتغيرات')
                        ->description('لا توجد متغيرات قابلة للتعديل لهذا الثيم حالياً.')
                        ->schema([]),
                ])
                ->statePath('data');
        }

        $formComponents = [];

        foreach ($theme->allowed_variables as $key => $config) {
            $label = $config['label'] ?? $key;
            $type = $config['type'] ?? 'text';

            $component = match ($type) {
                'color' => ColorPicker::make($key)->label($label),
                'select' => Select::make($key)->label($label)->options($config['options'] ?? []),
                'boolean', 'toggle' => Toggle::make($key)->label($label),
                default => TextInput::make($key)->label($label),
            };

            $formComponents[] = $component
                ->default($config['default'] ?? null)
                ->live()
                ->afterStateUpdated(fn ($state) => $this->dispatch(
                    'theme-variable-updated',
                    key: $key,
                    value: $state,
                    cssVar: $config['css_var'] ?? "--{$key}"
                ));
        }

        return $schema
            ->schema([
                Section::make('خيارات المتغيرات')
                    ->description('يتم جلب الحقول تلقائياً بناءً على إعدادات الثيم النشط')
                    ->schema($formComponents),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $restaurant = auth()->user()?->restaurant;

        if (! $restaurant || ! $restaurant->theme_id) {
            Notification::make()->title('خطأ')->body('لا يوجد ثيم مرتبط بالمطعم')->danger()->send();

            return;
        }

        $formData = $this->form->getState();

        RestaurantThemeSetting::updateOrCreate(
            ['restaurant_id' => $restaurant->id],
            [
                'theme_id' => $restaurant->theme_id,
                'settings' => $formData,
            ]
        );

        // مسح الكاش لضمان انطباق التعديلات فوراً
        Cache::forget("restaurant_theme_settings_{$restaurant->id}");

        Notification::make()
            ->title('تم حفظ التغييرات')
            ->body('تم تطبيق التعديلات على موقع المطعم بنجاح.')
            ->success()
            ->send();
    }
}