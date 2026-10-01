<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\Restaurant;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RestaurantProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.restaurant-profile-settings';

    protected static ?string $title = 'إعدادات المطعم والهوية';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'إعدادات المطعم والهوية';

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    private const EDITABLE_FIELDS = [
        'name',
        'description',
        'phone',
        'email',
        'logo',
        'cover_image',
        'address',
        'city',
        'currency_symbol',
        'tax_percentage',
        'delivery_fee',
        'whatsapp_number',
        'instagram_url',
        'facebook_url',
    ];

    public static function getSlug(?Panel $panel = null): string
    {
        return 'restaurant-profile-settings';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin' && filled($user->restaurant_id);
    }

    public function mount(): void
    {
        $restaurant = $this->restaurant();

        $this->form->fill($restaurant->only([
            ...self::EDITABLE_FIELDS,
            'slug',
            'subdomain',
            'plan',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المطعم')
                    ->schema([
                        TextInput::make('name')->label('اسم المطعم')->required()->maxLength(255),
                        Textarea::make('description')->label('الوصف')->rows(3)->columnSpanFull(),
                        TextInput::make('phone')->label('رقم الهاتف')->tel()->maxLength(30),
                        TextInput::make('email')->label('بريد الدعم')->email()->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('هوية المطعم')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('الشعار')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/logos'),
                        FileUpload::make('cover_image')
                            ->label('صورة الغلاف')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/covers'),
                    ])
                    ->columns(2),
                Section::make('معلومات التشغيل')
                    ->schema([
                        Textarea::make('address')->label('العنوان')->rows(2),
                        TextInput::make('city')->label('المدينة')->maxLength(120),
                        TextInput::make('currency_symbol')->label('رمز العملة')->required()->maxLength(12),
                        TextInput::make('tax_percentage')->label('نسبة الضريبة')->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                        TextInput::make('delivery_fee')->label('رسوم التوصيل')->numeric()->minValue(0),
                    ])
                    ->columns(2),
                Section::make('روابط التواصل')
                    ->schema([
                        TextInput::make('whatsapp_number')->label('رقم واتساب')->tel()->maxLength(30),
                        TextInput::make('instagram_url')->label('رابط إنستغرام')->url()->maxLength(2048),
                        TextInput::make('facebook_url')->label('رابط فيسبوك')->url()->maxLength(2048),
                    ])
                    ->columns(2),
                Section::make('بيانات الرابط والباقة')
                    ->schema([
                        TextInput::make('slug')
                            ->label('الرابط المختصر')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('تواصل مع الدعم الفني لتغيير الرابط الأساسي'),
                        TextInput::make('subdomain')
                            ->label('النطاق الفرعي')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('تواصل مع الدعم الفني لتغيير الرابط الأساسي'),
                        TextInput::make('plan')
                            ->label('الباقة الحالية')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only(
            Arr::except($data, ['slug', 'subdomain', 'plan', 'is_active', 'id']),
            self::EDITABLE_FIELDS,
        );
    }

    public function save(): void
    {
        $restaurant = $this->restaurant();
        $restaurant->fill($this->mutateFormDataBeforeSave($this->form->getState()));
        $restaurant->save();

        Notification::make()
            ->title('تم حفظ إعدادات المطعم')
            ->success()
            ->send();
    }

    private function restaurant(): Restaurant
    {
        $restaurant = Auth::user()?->restaurant;

        abort_unless($restaurant instanceof Restaurant, 403);

        return $restaurant;
    }
}
