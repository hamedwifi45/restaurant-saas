<?php

namespace App\Filament\Sham\Resources\SubscriptionPlans\Schemas;

use App\Models\PlatformFeature;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات باقة الاشتراك والتسعير')
                    ->columns(2)
                    ->schema([
                        TextInput::make('slug')
                            ->label('المعرف البرمجي للباقة (Slug)')
                            ->placeholder('مثال: starter, pro, enterprise')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name_ar')
                            ->label('اسم الباقة (بالعربية)')
                            ->placeholder('مثال: الباقة الاحترافية')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('name_en')
                            ->label('اسم الباقة (بالإنجليزية)')
                            ->placeholder('Pro Plan')
                            ->maxLength(120),
                        TextInput::make('monthly_price')
                            ->label('السعر الشهري للباقة ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->default(0)
                            ->required(),
                        TextInput::make('annual_price')
                            ->label('السعر السنوي للباقة ($)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->default(0)
                            ->required(),
                        TextInput::make('commission_rate')
                            ->label('نسبة العمولة الافتراضية (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step('0.01')
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('الباقة نشطة ومتاحة للتعيين للمطاعم')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('باقة مميزة (موصى بها)')
                            ->default(false),
                        Placeholder::make('estimated_features_valuation')
                            ->label('مجموع التسعير التقريبي للميزات المحددة في هذه الباقة')
                            ->content(function (Get $get): string {
                                $keys = (array) ($get('feature_keys') ?? []);
                                if ($keys === []) {
                                    return '0.00 $ / شهرياً';
                                }

                                $prices = self::featurePricesMap();
                                $sum = 0.0;
                                foreach ($keys as $key) {
                                    $sum += (float) ($prices[$key] ?? 0);
                                }

                                return number_format($sum, 2).' $ / شهرياً (مجموع القيم التقريبية للميزات المشمولة)';
                            })
                            ->columnSpanFull(),
                        Textarea::make('description_ar')
                            ->label('وصف الباقة ومزاياها')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('الميزات المشمولة في الباقة')
                    ->description('اختر الميزات المضمّنة تلقائياً لأي مطعم يشترك في هذه الباقة.')
                    ->schema([
                        CheckboxList::make('feature_keys')
                            ->label('قائمة الميزات المتاحة في المنصة')
                            ->options(fn (): array => self::featureOptions())
                            ->columns(2)
                            ->live()
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    /**
     * @return array<string, float>
     */
    private static function featurePricesMap(): array
    {
        $prices = [];
        foreach (PlatformFeature::defaultCatalog() as $item) {
            $prices[$item['key']] = (float) ($item['approximate_monthly_price'] ?? 0.0);
        }

        try {
            if (DbSchema::hasTable('platform_features')) {
                foreach (PlatformFeature::query()->get(['key', 'approximate_monthly_price']) as $feature) {
                    $prices[$feature->key] = (float) $feature->approximate_monthly_price;
                }
            }
        } catch (\Throwable) {
        }

        return $prices;
    }

    /**
     * @return array<string, string>
     */
    private static function featureOptions(): array
    {
        $prices = self::featurePricesMap();
        $options = [];

        foreach (PlatformFeature::defaultCatalog() as $item) {
            $key = $item['key'];
            $price = number_format((float) ($prices[$key] ?? 0), 2);
            $options[$key] = "{$item['name_ar']} (~{$price}$)";
        }

        try {
            if (DbSchema::hasTable('platform_features')) {
                foreach (PlatformFeature::query()->where('is_active', true)->orderBy('sort_order')->get() as $feature) {
                    $price = number_format((float) $feature->approximate_monthly_price, 2);
                    $options[$feature->key] = "{$feature->name_ar} (~{$price}$)";
                }
            }
        } catch (\Throwable) {
        }

        return $options;
    }
}
