<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\DeliveryProvider;
use App\Models\Restaurant;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use UnitEnum;

class RestaurantDeliverySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.restaurant.pages.restaurant-delivery-settings';

    protected static ?string $title = 'إعدادات التوصيل';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'إعدادات التوصيل';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 7;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && $user->restaurant instanceof Restaurant;
    }

    public function mount(): void
    {
        $restaurant = $this->restaurant();
        $this->form->fill([
            'delivery' => $restaurant->serviceEnabled('delivery'),
            'own_delivery_drivers' => $restaurant->ownDeliveryDriversEnabled(),
            'external_delivery' => $restaurant->externalDeliveryEnabled(),
            'provider_ids' => $restaurant->deliveryProviders()
                ->wherePivot('is_enabled', true)
                ->where('delivery_providers.is_active', true)
                ->where('delivery_providers.assignment_method', 'manual')
                ->orderBy('delivery_providers.name')
                ->pluck('delivery_providers.id')
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('خيارات التوصيل')
                    ->description('يمكن للمطعم استخدام سائقيه أو شركات التوصيل أو كليهما. إيقاف خدمة التوصيل يمنع الطلبات والإحالات الجديدة دون حذف السجلات السابقة.')
                    ->schema([
                        Toggle::make('delivery')
                            ->label('استقبال طلبات التوصيل')
                            ->helperText('هذا الخيار مستقل عن إدارة الطاولات والحجوزات.')
                            ->required(),
                        Toggle::make('own_delivery_drivers')
                            ->label('استخدام سائقي المطعم')
                            ->helperText('لا يؤثر إيقافه في حسابات السائقين أو سجلات الطلبات السابقة.')
                            ->required(),
                        Toggle::make('external_delivery')
                            ->label('استخدام شركات التوصيل الخارجية')
                            ->helperText('الإحالة يدوية فقط؛ لا يوجد ربط آلي مع أي شركة.')
                            ->required(),
                        CheckboxList::make('provider_ids')
                            ->label('الشركات المرتبطة بهذا المطعم')
                            ->options(fn (): array => DeliveryProvider::query()
                                ->where('is_active', true)
                                ->where('assignment_method', 'manual')
                                ->orderBy('delivery_providers.name')
                                ->pluck('name', 'id')
                                ->all())
                            ->columns(2)
                            ->helperText('لن تكون الشركة متاحة للإحالة ما لم تكن نشطة ومرتبطة بالمطعم مع تفعيل خياري التوصيل والتوصيل الخارجي.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $validated = Validator::make($data, [
            'delivery' => ['required', 'boolean'],
            'own_delivery_drivers' => ['required', 'boolean'],
            'external_delivery' => ['required', 'boolean'],
            'provider_ids' => ['nullable', 'array'],
            'provider_ids.*' => [
                'integer',
                Rule::exists('delivery_providers', 'id')
                    ->where('is_active', true)
                    ->where('assignment_method', 'manual'),
            ],
        ])->validate();
        $restaurant = $this->restaurant();
        $providerIds = collect($validated['provider_ids'] ?? [])
            ->map(fn (mixed $providerId): int => (int) $providerId)
            ->unique()
            ->all();
        $restaurantId = $restaurant->getKey();

        DB::transaction(function () use ($restaurantId, $validated, $providerIds): void {
            $restaurant = Restaurant::query()
                ->whereKey($restaurantId)
                ->lockForUpdate()
                ->firstOrFail();
            $eligibleProviderIds = $providerIds === []
                ? []
                : DeliveryProvider::query()
                    ->whereKey($providerIds)
                    ->where('is_active', true)
                    ->where('assignment_method', 'manual')
                    ->lockForUpdate()
                    ->pluck('id')
                    ->map(fn (mixed $providerId): int => (int) $providerId)
                    ->all();

            if (count($eligibleProviderIds) !== count($providerIds)) {
                throw ValidationException::withMessages([
                    'data.provider_ids' => 'توجد شركة غير نشطة أو لا تدعم الإحالة اليدوية.',
                ]);
            }

            $serviceConfig = $restaurant->getServiceConfig();
            $serviceConfig['delivery'] = (bool) $validated['delivery'];
            $serviceConfig['own_delivery_drivers'] = (bool) $validated['own_delivery_drivers'];
            $serviceConfig['external_delivery'] = (bool) $validated['external_delivery'];

            $restaurant->update(['service_config' => $serviceConfig]);
            $restaurant->deliveryProviders()->sync(
                collect($eligibleProviderIds)
                    ->mapWithKeys(fn (int $providerId): array => [$providerId => ['is_enabled' => true]])
                    ->all(),
            );
        });

        Notification::make()
            ->title('تم حفظ إعدادات التوصيل')
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
