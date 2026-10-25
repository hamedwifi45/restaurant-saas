<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\Restaurant;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RestaurantOrderExperiments extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.restaurant-order-experiments';

    protected static ?string $title = 'ميزات الطلب التجريبية';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'ميزات الطلب التجريبية';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    public static function getSlug(?Panel $panel = null): string
    {
        return 'order-experiments';
    }

    public static function canAccess(): bool
    {
        $restaurant = Auth::user()?->restaurant;

        return Auth::user()?->role === 'restaurant_admin'
            && $restaurant?->theme?->folder_name === 'albasha-burger';
    }

    public function mount(): void
    {
        $this->form->fill($this->restaurant()->getExperimentalOrderFeatures());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('قيد التجربة')
                    ->description('هذه الخيارات تجريبية وغير رسمية. فعّل فقط ما يقدمه مطعمك فعلياً، وتحمّل مسؤولية إعدادها ومتابعة الطلبات الناتجة عنها. تبقى جميعها متوقفة حتى تفعيلها يدوياً.')
                    ->schema([
                        Toggle::make('table_reservations')
                            ->label('السماح بحجز الطاولات مسبقاً')
                            ->helperText('يعرض الطاولات المتاحة فعلياً في الوقت المختار ويربط الحجز بطاولة من قائمة طاولات مطعمك.'),
                        Toggle::make('scheduled_pickup')
                            ->label('السماح باستلام الطلب من المطعم في موعد محدد')
                            ->helperText('يتطلب دفع عربون مسبق ورفع إيصال التحويل لمراجعته يدوياً.'),
                        TextInput::make('deposit_percentage')
                            ->label('نسبة العربون')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('تُحسب من إجمالي الطلب وتُطلب للحجوزات والاستلام بموعد.'),
                        CheckboxList::make('reservation_duration_options')
                            ->label('مدد الحجز التي يمكن للعميل اختيارها')
                            ->options([
                                30 => '30 دقيقة',
                                60 => 'ساعة',
                                90 => 'ساعة ونصف',
                                120 => 'ساعتان',
                                150 => 'ساعتان ونصف',
                                180 => '3 ساعات',
                                240 => '4 ساعات',
                            ])
                            ->required()
                            ->minItems(1)
                            ->columns(3)
                            ->helperText('مدة الحجز التي يحددها العميل تُستخدم لحساب إشغال الطاولة ومنع تعارض الحجوزات.'),
                        Toggle::make('risk_acknowledged')
                            ->label('أقر بأن هذه الميزات تجريبية وعلى مسؤوليتي، وسأراجع العربون والحجوزات يدوياً.')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('تفاصيل حجز الطاولة الاختيارية')
                    ->description('فعّل فقط الخدمات المتوفرة فعلياً في مطعمك. لن تظهر التفاصيل غير المفعّلة للعميل.')
                    ->schema([
                        Toggle::make('reservation_seating_preference')->label('تفضيل نوع الجلسة'),
                        Toggle::make('reservation_occasion')->label('تحديد المناسبة'),
                        Toggle::make('reservation_high_chairs')->label('طلب كراسي أطفال'),
                        Toggle::make('reservation_stroller_space')->label('مساحة عربة الأطفال'),
                        Toggle::make('reservation_accessibility_notes')->label('متطلبات الوصول والحركة'),
                        Toggle::make('reservation_allergy_notes')->label('ملاحظات الحساسية الغذائية'),
                        Toggle::make('reservation_dietary_preference')->label('التفضيل الغذائي'),
                        Toggle::make('reservation_confirmation_call')->label('طلب اتصال لتأكيد الحجز'),
                        Toggle::make('reservation_celebration_notes')->label('تجهيزات المناسبة'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $features = $this->form->getState();
        $restaurant = $this->restaurant();
        $hasEnabledExperiment = $features['table_reservations']
            || $features['scheduled_pickup']
            || collect($features)
                ->only([
                    'reservation_seating_preference',
                    'reservation_occasion',
                    'reservation_high_chairs',
                    'reservation_stroller_space',
                    'reservation_accessibility_notes',
                    'reservation_allergy_notes',
                    'reservation_dietary_preference',
                    'reservation_confirmation_call',
                    'reservation_celebration_notes',
                ])
                ->contains(true);

        if ($hasEnabledExperiment && ! ($features['risk_acknowledged'] ?? false)) {
            Notification::make()
                ->title('يلزم الإقرار بمسؤولية الميزات التجريبية')
                ->danger()
                ->send();

            return;
        }

        $features['reservation_duration_options'] = collect($features['reservation_duration_options'] ?? [])
            ->map(fn (mixed $duration): int => (int) $duration)
            ->filter(fn (int $duration): bool => in_array($duration, [30, 60, 90, 120, 150, 180, 240], true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($features['reservation_duration_options'] === []) {
            Notification::make()
                ->title('اختر مدة حجز واحدة على الأقل')
                ->danger()
                ->send();

            return;
        }

        if (
            ($features['table_reservations'] || $features['scheduled_pickup'])
            && (! $restaurant->hasFeature('bank_transfer') || blank($restaurant->bank_details))
        ) {
            Notification::make()
                ->title('أضف تفاصيل التحويل البنكي أولاً')
                ->body('احفظ معلومات الدفع المسبق من صفحة إعدادات المطعم قبل تفعيل الحجوزات أو الاستلام بموعد.')
                ->danger()
                ->send();

            return;
        }

        $serviceConfig = $restaurant->getServiceConfig();
        $serviceConfig['table_reservations'] = (bool) ($features['table_reservations'] ?? false);
        $serviceConfig['scheduled_pickup'] = (bool) ($features['scheduled_pickup'] ?? false);

        $restaurant->update([
            'service_config' => $serviceConfig,
            'experimental_order_features' => $features,
        ]);

        Notification::make()
            ->title('تم حفظ إعدادات الميزات التجريبية')
            ->success()
            ->send();
    }

    private function restaurant(): Restaurant
    {
        return Auth::user()->restaurant;
    }
}
