# دليل مشروع منصة المطاعم

هذا الملف هو نقطة العودة السريعة للمشروع بعد انقطاع طويل عن التطوير. اقرأه بالترتيب في أول جلسة عمل، ثم حدّث قسم المشكلات والقرارات كلما تغيّر السلوك الأساسي.

## 1. ما هو المشروع؟

المشروع SaaS لإدارة مطاعم متعددة. لكل مطعم:

- صفحة عامة يمكن للعميل فتحها عبر `/{slug}`.
- قائمة أصناف وتصنيفات.
- سلة مشتريات محفوظة في Session.
- Checkout لإنشاء الطلب.
- فاتورة، صفحة نجاح، وتتبع بالـ tracking code.
- تقييمات وعروض وكوبونات وPWA.
- ثيم قابل للاختيار وإعدادات مخصصة.

يوجد أيضًا نوعان من لوحات Filament:

- `/restaurant`: لوحة صاحب المطعم وموظفيه.
- `/sham`: لوحة الإدارة العامة.

## 2. التقنيات والإصدارات

المشروع مبني على:

- PHP `^8.3`، والبيئة المقصودة في إرشادات المشروع PHP 8.4.
- Laravel `^13.17`.
- Filament 5.
- Livewire 4 وFlux 2.
- Laravel Fortify للمصادقة و2FA وPasskeys.
- Laravel Reverb/Echo للتجهيزات الفورية.
- Tailwind CSS 4 وVite 8.
- Pest 4 للاختبارات.
- Larastan 3 وLaravel Pint.
- قاعدة البيانات الافتراضية في `.env.example`: SQLite.

مصدر الإصدارات الفعلي هو `composer.json` و`package.json`، وليس هذا الملف.

## 3. تشغيل المشروع محليًا

### المتطلبات

- PHP 8.3 أو أحدث.
- Composer.
- Node.js وnpm.
- امتدادات PHP المطلوبة من Laravel، وعلى الأقل SQLite إذا استُخدمت قاعدة SQLite.

### تشغيل أول مرة

من جذر المشروع:

```powershell
composer run setup
```

هذا الأمر يثبت Composer، ينشئ `.env` عند عدم وجوده، يولد `APP_KEY`، يشغل الهجرات، يثبت npm، ويبني ملفات الواجهة.

إذا كان `.env` موجودًا، راجع خصوصًا `APP_KEY` و`DB_CONNECTION` و`APP_URL` قبل التشغيل.

### التشغيل أثناء التطوير

```powershell
composer run dev
```

يشغل عادةً ثلاثة processos متوازية:

- Laravel server على `php artisan serve`.
- Queue worker عبر `php artisan queue:listen --tries=1`.
- Vite عبر `npm run dev`.

بديل يدوي عند الحاجة:

```powershell
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

### أوامر يومية مفيدة

```powershell
php artisan route:list --except-vendor
php artisan migrate:status
php artisan migrate
php artisan optimize:clear
npm run build
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

لا تشغّل `migrate:fresh` على بيانات مهمة؛ فهو يحذف الجداول والبيانات.

## 4. خريطة الملفات

### منطق التطبيق

- `app/Models`: نماذج Eloquent والعلاقات والتحويلات.
- `app/Http/Controllers`: واجهة HTTP العامة مثل المطعم والسلة والطلب والفاتورة والتقييم.
- `app/Filament`: موارد وصفحات لوحتي Filament.
- `app/Livewire`: صفحات الحساب التفاعلية مثل الملف الشخصي والأمان.
- `app/Services`: منطق أكبر من أن يوضع داخل Controller، وأهمه اكتشاف ورفع الثيمات.
- `app/Helpers/ThemeHelper.php`: اختيار View الثيم وقراءة متغيراته.
- `app/Providers`: تسجيل Laravel وFilament.

### الواجهة

- `resources/views/themes`: قوالب واجهة العميل، حاليًا `burger-theme`.
- `resources/views/filament`: صفحات مخصصة داخل Filament.
- `resources/views/livewire`: قوالب Livewire.
- `resources/css`: CSS الخاص بالتطبيق ولوحتي Filament.
- `resources/js`: JavaScript وتهيئة Vite/Echo.

### البيانات والاختبارات

- `database/migrations`: مخطط قاعدة البيانات.
- `database/factories`: بيانات الاختبار.
- `database/seeders`: البيانات الأولية.
- `tests/Feature`: اختبارات التدفقات HTTP وFilament والمصادقة.
- `tests/Unit`: الاختبارات الوحدية.

## 5. نموذج البيانات والعلاقات

العلاقات الأساسية:

```text
User -> Restaurant
Restaurant -> Categories -> Products
Restaurant -> Orders -> Order_items
Order -> Invoice
Restaurant -> Offers -> Products (pivot offer_product)
Restaurant -> Coupons
Restaurant -> Theme -> RestaurantThemeSetting
Order -> Review
```

النقاط المهمة:

- `Restaurant.slug` هو معرف المطعم في URLs العامة.
- `Product` يستخدم Soft Deletes، ويملك `is_available` للتحكم في ظهوره وقابليته للطلب.
- `Order_item` يحفظ اسم المنتج وسعره وقت الطلب، لذلك لا تعتمد الفاتورة على سعر المنتج الحالي.
- `Order` يحفظ subtotal والضريبة/التوصيل والخصومات والمبلغ النهائي وحالة الدفع وحالة الطلب.
- `Theme` يربط المطعم بمجلد تحت `resources/views/themes`.
- إعدادات الثيم الافتراضية في `theme.json`، وإعدادات المطعم المخصصة في `restaurant_theme_settings`.

## 6. تدفق العميل الكامل

1. يزور العميل `/{slug}`، ويجلب `RestaurantController` المطعم والثيم.
2. يزور `/{slug}/menu` لعرض التصنيفات والمنتجات المتاحة.
3. يضيف منتجًا عبر `POST /{slug}/cart/add`.
4. تحفظ السلة في Session تحت المفتاح `cart`، وبنيتها تقريبًا:

```php
[
    12 => ['qty' => 2],
]
```

5. تعرض `CartController` السلة وتعيد حساب الإجمالي من أسعار قاعدة البيانات.
6. يعرض `OrderController@checkout` Checkout بعد التأكد من وجود المنتجات وتبعيتها للمطعم وتوفرها.
7. يرسل العميل `POST /{slug}/checkout`، فيعاد حساب الأسعار من قاعدة البيانات، ثم ينشأ:
   - الطلب.
   - عناصر الطلب.
   - الفاتورة.
8. تفرغ السلة، ثم ينتقل العميل إلى `/order/success/{code}`.
9. يمكن تتبع الطلب عبر `/track/{code}` أو فتح الفاتورة والتقييم.

لا تعتمد على قيم السعر القادمة من المتصفح؛ المصدر الصحيح للسعر هو المنتج في قاعدة البيانات.

## 7. العروض والكوبونات

- `apply-offer` و`apply-coupon` مساران AJAX للتحقق قبل الإرسال.
- العرض مرتبط بالمطعم، وله فترة نشاط وحد أدنى وحد استخدام.
- الكوبون مرتبط بالمطعم، وله كود وصلاحية وحد أدنى وقواعد استخدام.
- عند التخزين يعاد التحقق من العرض والكوبون، لأن فحص AJAX وحده لا يكفي.
- لا تضع ثقة في `offer_id` أو `coupon_id` أو `subtotal` القادمة من العميل؛ يجب أن تطابق ما يحسبه الخادم من السلة.

## 8. الثيمات

كل ثيم صالح يحتاج مجلدًا تحت `resources/views/themes` وملف `theme.json`، إضافة إلى القوالب التي يستدعيها التدفق.

الثيم الحالي `burger-theme` يحتوي على:

- `layout.blade.php`.
- صفحات home/menu/product/offers.
- قوالب cart وcheckout وsuccess.
- قوالب التتبع والفاتورة.
- قوالب التقييمات.

يكتشف `ThemeDiscoveryService` المجلدات التي تحتوي `theme.json` ويزامنها مع جدول `themes`. يستخدم `ThemeHelper` مسار الثيم الحالي لبناء اسم View ديناميكيًا.

عند إضافة ثيم جديد:

1. أنشئ مجلدًا باسم واضح.
2. أضف `theme.json` صالحًا.
3. وفّر كل القوالب التي تحتاجها المسارات العامة، لا الصفحة الرئيسية فقط.
4. شغّل مزامنة الثيمات بالطريقة المستخدمة في المشروع أو راجع Seeder/Filament إذا لم تكن هناك Command مخصصة.
5. اختبر home وmenu وcart وcheckout وsuccess وtrack وinvoice وreviews.

## 9. المصادقة والصلاحيات

- Fortify يوفّر تسجيل الدخول والتسجيل وإعادة تعيين كلمة المرور والتحقق من البريد و2FA وPasskeys.
- لوحة `/restaurant` محمية بـ Filament authentication، وتستخدم `restaurant_id` للمستخدم لعزل بيانات المطعم.
- لوحة `/sham` مخصصة للإدارة العامة، ويجب التأكد من أن Gate/Policy يميز صلاحيات الإدارة عن مستخدم المطعم.
- إعدادات الحساب تحت `/settings` محمية بـ `auth`، والأمان يضيف `password.confirm`.
- صفحات العميل العامة لا تحتاج تسجيل دخول لإضافة الطلب، لذلك يجب تدقيق كل معرف عام وصلاحية عرض بياناته.

## 10. الاختبارات الحالية

الاختبارات الموجودة تغطي بدرجة جيدة:

- المصادقة والتسجيل وإعادة تعيين كلمة المرور والتحقق والبصمة.
- الملف الشخصي والأمان.
- اختبارات لوحة المطعم والعزل والصلاحيات.
- رفع الثيمات وإعدادات الثيم.

التغطية الناقصة الأهم هي التدفق التجاري: `cart -> checkout -> order -> items -> invoice`، مع الأسعار والخصومات والتزامن وفشل الحفظ.

قبل كل تغيير:

```powershell
php artisan test --compact --filter=اسم_الاختبار
```

وبعد التغيير شغّل الاختبار المتأثر، ثم Pint، ثم مجموعة الاختبارات الأوسع عند الحاجة.

## 11. مشكلات معروفة حاليًا

### مؤكدة من الاختبارات

آخر تشغيل للاختبارات في بيئة هذا الدليل: `41` اختبارًا، منها `38` ناجحًا و`3` فاشلة.

1. `tests/Feature/DashboardTest.php` يتوقع أن المسار `dashboard` موجود ويعيد Redirect للضيف و200 للمستخدم، لكن النتيجة الحالية 404 في الحالتين. راجع تعريف المسار والاختبار؛ المسار الحالي في `routes/web.php` يستخدم View `dashboard`.
2. `tests/Feature/Filament/RestaurantPanelTest.php` يفشل بحالة 403 عند محاولة المستخدم المصادق عليه فتح Dashboard المطعم. راجع دور المستخدم وPolicy/تحديد المطعم قبل تعديل الاختبار.
3. الأمر `php artisan types:check` غير موجود رغم وجود Script بالاسم نفسه في `composer.json`. الفحص الصحيح المتاح مباشرة هو `vendor/bin/phpstan analyse`، أو يجب إصلاح Script ليشير إلى الأمر المناسب.

### مخاطر واضحة من قراءة الكود وتحتاج إصلاحًا واختبارًا

1. **مسارات إدارة الثيمات بلا حماية ظاهرة:** مسارات `themes` وعمليات activate/clone/reset موجودة في `routes/web.php` خارج `auth`، و`ThemeController` لا يفرض Authorization داخليًا. هذا قد يسمح لزائر باستدعاء عمليات إدارية.
2. **قيمة `total_amount` غير صحيحة:** إنشاء الطلب يحفظ `total_amount` كـ `$subtotal`، بينما المبلغ بعد الضريبة والتوصيل والخصومات محفوظ في `final_amount`. يجب توضيح معنى العمودين ثم اختبار القيم والفاتورة.
3. **إنشاء الطلب ليس داخل Transaction:** قد يُحفظ Order دون عناصر أو Invoice إذا فشل أي Insert لاحق. استخدم Transaction بعد تثبيت الاختبارات المطلوبة.
4. **احتمال التلاعب بقيم الخصم:** `store` يعتمد على `offer_id` و`coupon_id`، ويقبل `coupon_code` من الطلب، بينما يجب مطابقة الكود والسلة وإعادة الحساب من الخادم في عملية ذرية.
5. **تطبيق العرض على مستوى الإجمالي فقط:** توجد علاقة offer-product، لكن مسار التخزين الظاهر يتحقق من المطعم والنشاط والحد الأدنى ولا يثبت أن منتجات السلة مشمولة بالعرض.
6. **السلة عامة داخل Session:** لا يوجد معرف مطعم مستقل محفوظ مع السلة. توجد حماية عند الإضافة، لكن يجب جعل سلوك الانتقال بين مطعمين صريحًا واختباره.
7. **السلة قد تعرض منتجات غير متاحة:** `CartController@index` يجلب المنتج دون شرط `is_available`; المنع يظهر لاحقًا في checkout. الأفضل عرض حالة واضحة أو تنظيف العنصر.
8. **`Appearance` غير مكتمل:** مسار إعدادات المظهر موجود، لكن مكوّن `app/Livewire/Settings/Appearance.php` لا يقدم سلوكًا فعليًا بحسب القراءة الحالية.
9. **كود CRUD قديم غير مستخدم:** بعض Controllers مثل `ProductController` تبدو Stubs بينما الإدارة الفعلية في Filament. لا تحذفها قبل التأكد من عدم وجود روابط أو API تعتمد عليها.
10. **تغييرات محلية غير ملتزم بها:** توجد تعديلات كثيرة في Controllers وModels وSeeder و`burger-theme/layout.blade.php`، إضافة إلى Factories جديدة واسم ملف غريب `origin)`. راجعها قبل الدمج أو البناء حتى لا تختلط إصلاحات قديمة بتطوير جديد.

## 12. ترتيب مقترح لاستئناف التطوير

1. احفظ نسخة من الحالة الحالية، ثم راجع `git diff` لكل ملف معدل؛ لا تفقد تغييراتك المحلية.
2. أصلح فشل Dashboard و403 في اختبار لوحة المطعم، لأنهما يعيقان الثقة في البيئة الأساسية.
3. أصلح Script فحص الأنواع أو وثّق الأمر الصحيح، ثم شغّل Pint وPHPStan.
4. أضف اختبارات checkout والطلب والفاتورة والخصم قبل إعادة هيكلة `OrderController`.
5. ضع إنشاء الطلب والعناصر والفاتورة وتحديث استخدام العرض/الكوبون داخل Transaction مناسبة.
6. أغلق مسارات إدارة الثيمات بـ middleware وصلاحيات واضحة، ثم أضف اختبار زائر ومستخدم مطعم ومدير.
7. راجع السلة عند اختلاف المطعم أو حذف/تعطيل منتج.
8. بعد استقرار المنطق، أكمل `Appearance` واحذف أو اعزل الـ stubs غير المستخدمة.
9. اختبر كل ثيم على كل المسارات العامة، ثم شغّل `npm run build`.

## 13. طريقة العمل الآمنة

- ابدأ دائمًا بـ `git status` و`git diff`؛ توجد تغييرات محلية يجب احترامها.
- ابحث عن المسار أو Model أو Resource المسؤول قبل تعديل ملفات كثيرة.
- غيّر أصغر سطح ممكن، ثم شغّل اختبارًا يثبت السلوك.
- لا تثق ببيانات الأسعار والخصومات القادمة من المتصفح.
- استخدم Policies وmiddleware للحدود الأمنية، لا الاعتماد على إخفاء زر في الواجهة.
- عند تغيير Schema، أضف Migration جديدة بدل تعديل Migration مطبقة.
- بعد تعديل PHP شغّل `vendor/bin/pint --dirty --format agent`.
- لا تستخدم بيانات الإنتاج لتجارب `migrate:fresh` أو Seeders.

## 14. أول جلسة عملية مقترحة

```powershell
git status --short
php artisan migrate:status
php artisan route:list --except-vendor
php artisan test --compact
vendor/bin/phpstan analyse
npm run build
```

بعد ذلك ابدأ من قسم **مشكلات معروفة حاليًا**، وسجّل في هذا الملف ما تم إصلاحه مع اختبار يثبت الإصلاح.



تقرير فني شامل: منصة المطاعم (Restaurant SaaS)
تاريخ الفحص: 2026-09-28 · نطاق التقرير: مسح كامل للكود المصدري في app/, config/, routes/, resources/, database/, tests/ ملاحظة منهجية مهمة: التقرير مبني على قراءة فعلية للملفات وليس على الوثائق الموجودة (PROJECT_GUIDE_AR.md). حيث تتعارض الوثيقة مع الكود، أشرتُ إلى الواقع الفعلي في الكود.

1. نظرة عامة والمعمارية (Architecture & Tech Stack)
1.1 الهوية التقنية
المشروع هو SaaS متعدد المستأجرين (Multi-tenant) لإدارة المطاعم، مبني على مشروع Laravel الرسمي laravel/livewire-starter-kit. الإصدارات الفعلية من composer.json / package.json:

الطبقة	الحزمة / التقنية	الإصدار
Framework	laravel/framework	^13.17
PHP	php	^8.3 (بيئة التطوير الفعلية: 8.4.12)
Admin Panel	filament/filament	^5.0
Livewire	livewire/livewire	^4.4
UI Kit	livewire/flux	^2.13.1
Compiler	livewire/blaze	^1.0
Auth	laravel/fortify	^1.37.2
Realtime	laravel/reverb	^1.0
Echo/WS client	laravel-echo ^2.5 + pusher-js ^8.6	—
Tailwind	tailwindcss	^4.3.3
Build	vite ^8.0 + laravel-vite-plugin ^3.1	—
Passkeys	@laravel/passkeys	^0.2.0
Testing	pestphp/pest ^4.7	—
Static Analysis	larastan/larastan ^3.9	—
Formatter	laravel/pint ^1.27	—
1.2 كيف تم بناء الـ Multi-tenancy؟ (السؤال الجوهري)
الإجابة القاطعة: لم يُستخدم أي حزمة tenancy (لا stancl/tenancy ولا غيرها)، ولا يوجد عزل على مستوى قاعدة البيانات أو الـ Subdomains.

المعمارية المستخدمة هي Shared Database + Shared Schema + Application-Level Scoping (Single Database, Row-Level Tenancy)، أي عزل منطقي برمجي بالكامل عبر الأعمدة restaurant_id. الأدلة من الكود:

لا توجد حزم tenancy في composer.json — القائمة كاملة ولا وجود لـ stancl/tenancy.
الـ Subdomain غير مستغل فعلياً: حقل restaurants.subdomain موجود في المخطط وunique، ويوجد حتى accessor:
php

// app/Models/Restaurant.php:85
public function getFullSubdomainAttribute(): string
{
    return $this->subdomain ? "{$this->subdomain}.".config('app.domain') : $this->slug;
}
لكن لا يوجد أي Middleware للـ subdomain، ولا Route::domain() في routes/web.php، ولا أي استعلام يعتمد على subdomain. المعرّف الفعلي للمستأجر في الـ URLs هو {slug} كـ Route Parameter (/{slug}, /{slug}/menu, ...).
لم يُستخدم Tenancy Middleware — لا توجد أي Middleware مخصصة للـ tenancy.
آليات العزل الفعلية المستخدمة (ثلاث طبقات):

الـ Route Model Binding عبر slug: كل المسارات العامة تحت Route::prefix('{slug}') وتُحمّل المطعم عبر Restaurant::where('slug', $slug)->firstOrFail() ثم تُقيّد كل الاستعلامات بـ where('restaurant_id', $restaurant->id).
getEloquentQuery() في موارد Filament: كل Resource في لوحة المطعم تعيد تعريف الاستعلام:
php

// app/Filament/Restaurant/Resources/Products/ProductResource.php:50
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth('web')->user();
    if ($user instanceof User && filled($user->restaurant_id)) {
        $query->where('restaurant_id', $user->restaurant_id);
    }
    return $query;
}

مطبّق على ProductResource, OrderResource, وDriverResource (->where('role','driver')->where('restaurant_id', ...)) وRestaurantTableResource.
الـ Policies: app/Policies/{Category,Product,Order}Policy.php — كلها تتحقق من $model->restaurant_id === $user->restaurant_id ومن isRestaurantScoped().
الـ Model Boot Hooks (حقن تلقائي): عدة موديلات تحقن restaurant_id تلقائياً عند الإنشاء حسب المستخدم المسجّل:
Product, Category, Invoice (شرط role != 'super_admin'), Coupon (نفس الشرط), Offer (نفس الشرط), RestaurantTable.
Order::boot() يحقن restaurant_id من auth()->user()->restaurant_id عند creating إذا كان فارغاً.
⚠️ ملاحظة خطر: منطق الـ Boot هذا غير متسق: بعض الموديلات تحقن بدون شرط الدور (Product), وبعضها يتحقق من role != 'super_admin' (Category, Coupon, Offer, Invoice). كما أن Offer, Coupon, Invoice تُغلّف تسجيل الحدث (event listener) داخل if (auth()->check() ...) — وهذا يعني أن الـ hook لا يُسجّل أصلاً في الطلبات العامة (بدون auth) لـ Coupon/Offer/Invoice. كذلك يوجد خطأ نسخ/لصق واضح: المتغير في الـ closure اسمه $category بينما هو في موديل Coupon/Offer/Invoice. هذا كود يحتاج تنظيفاً.

1.3 Redis والكاش
Redis غير مستخدم في المشروع نهائياً. لا وجود لـ predis/predis ولا phpredis في composer.json.

الإعدادات الفعلية من .env و config/:

المكوّن	القيمة الفعلية
CACHE_STORE	database (جدول cache)
SESSION_DRIVER	database (جدول sessions)
QUEUE_CONNECTION	database (جدول jobs)
BROADCAST_CONNECTION	reverb
DB_CONNECTION	mysql (DB: restuarant, user: root, no password)
استخدام الكاش فعلياً في المشروع محصور في مكان واحد: إعدادات الثيم.

php

// app/helpers.php:15 — دالة theme_setting() العالمية
$cacheKey = "restaurant_theme_settings_{$restaurant->id}";
$allSettings = Cache::remember($cacheKey, 86400, function () use ($restaurant) {
    // يدمج default من allowed_variables مع settings المحفوظة
});
إبطال الكاش يتم عبر Observer Pattern:

app/Observers/RestaurantObserver.php: يمسح restaurant_theme_settings_{id} عند updated/saved/deleted.
app/Observers/RestaurantThemeSettingObserver.php: يمسح الكاش عند saved/deleted.
كلاهما مُسجّل في AppServiceProvider::boot().

ملاحظة: restaurant_theme_settings_{id} هو نفس رقم المفتاح الذي تستخدمه theme_setting(), لكن ThemeHelper::getVar() تقرأ مباشرة من getThemeSettings() بدون كاش — أي يوجد مساران لقراءة نفس الإعدادات، أحدهما مُكاش والآخر لا. هذا تفصيل يستحق التوحيد.

1.4 الـ Realtime Stack (Reverb + Echo)
خادم WebSocket: Laravel Reverb (config/reverb.php), معرّف في .env بـ REVERB_APP_KEY, REVERB_HOST=localhost, REVERB_PORT=8080, REVERB_SCHEME=http.

العميل: resources/js/echo.js يُهيّئ window.Echo ببروتوكول reverb، مع enabledTransports: ['ws', 'wss']، ويُستورد من resources/js/app.js.

القنوات: routes/channels.php تعرّف قناتين فقط:

php

Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
Broadcast::channel('restaurant.{restaurantId}.kds', fn (User $user, int $restaurantId) => true);
⚠️ ثغرة صلاحيات: قناة الـ KDS تُرجع true دائماً — أي أي مستخدم مصادق عليه يستطيع الاشتراك في قناة مطبخ أي مطعم بمعرفته فقط. لا يوجد تحقق من أن $user->restaurant_id === $restaurantId. هذا خطر أمني واضح (تسريب بيانات طلبات مطعم آخر لحظياً).

الأحداث (Events):

OrderCreated implements ShouldBroadcastNow — يُبثّ على PrivateChannel("restaurant.{id}.kds") باسم order.created، ويُرسل معه KdsOrderResource جاهز.
OrderStatusChanged — معطّل فعلياً: الكلاس لا يُنفّذ ShouldBroadcastNow/ShouldBroadcast (تعريفه مجرد class OrderStatusChanged مع use Dispatchable, InteractsWithSockets, SerializesModels;)، وbroadcastOn() تُعيد Channel غير private. أي أنه لن يُبثّ فعلياً ولن يُشتعل عبر الـ WebSocket. كذلك لا يوجد أي مكان في الكود يُطلق event(new OrderStatusChanged(...)) — فقط OrderCreated مُطلق من OrderController::store.
KDS board يستمع فعلياً على Echo.private('restaurant.{id}.kds').listen('.order.created', ...) (سطر ~1346 في kds-board.blade.php).
2. قاعدة البيانات ونموذج البيانات (Database Schema & Models)
2.1 هيكل جدول restaurants
database/migrations/2026_07_03_164411_create_restaurants_table.php + 2026_07_19_000001_fix_theme_schema.php:

العمود	النوع	ملاحظات
id	bigint PK	
name	string	
slug	string unique	المعرّف الفعلي في الـ URLs
subdomain	string unique nullable	غير مستخدم برمجياً
description, logo, cover_image	text/string	
phone, email, address, city	string/text	
delivery_fee	decimal(10,2) default 0	لا يوجد حساب مسافة فعلي (رسوم ثابتة)
estimated_delivery_time	integer nullable	بالدقائق
theme_id	FK → themes onDelete restrict	إلزامي
theme_settings	json nullable	مُضاف لاحقاً (legacy)
custom_sections	json nullable	مُضاف لاحقاً
primary_color, secondary_color, background_color	string	ألوان قديمة موازية لنظام الثيمات
qr_code_image, bank_details	string/text	تفاصيل الدفع
pricing_type	enum(commission,subscription)	نموذج تسعير المنصة
commission_rate, subscription_fee	decimal	
is_active	boolean default true	
trial_ends_at	timestamp nullable	
deleted_at, created_at, updated_at		SoftDeletes
فهارس: subdomain, slug, is_active.

⚠️ ازدواجية نموذجية (Schema Duplication): يوجد مساران موازيان لإعدادات المظهر:

restaurants.theme_settings (JSON) — النظام القديم، ويقرأه الـ Observer ويعتمده theme_setting() كـ fallback.
restaurant_theme_settings (جدول مستقل 1:1) — النظام الحديث الذي يستخدمه Filament. هذا الازدواج يخلق مصدر حقيقة مزدوجاً (أي القيمة الفعلية؟). helpers.php يقرأ themeSettings?->settings ?? restaurant->theme_settings ?? [] بينما theme-styles.blade.php يقرأ $restaurant?->themeSetting?->settings باسم علاقة خاطئ (الموديل يعرّف العلاقة themeSettings() لا themeSetting() — راجع القسمين 2.3 و 3.2).
2.2 جدول themes
2026_07_03_160811_create_themes_table.php + التحديثات:

العمود	النوع
id, name, slug(unique), author, version	
description	text
folder_name (unique)	اسم المجلد تحت resources/views/themes/
preview_image	string
default_settings	json (من theme.json)
allowed_variables	json (واجهة التحكم في المتغيرات)
sections	json nullable (أُضيف لاحقاً — غير مستخدم فعلياً)
settings	json nullable (أُضيف لاحقاً — غير مستخدم)
is_active, is_default	boolean
2.3 جدول restaurant_theme_settings
2026_07_18_043347:

php

$table->foreignId('restaurant_id')->unique()->constrained()->onDelete('cascade'); // 1:1
$table->foreignId('theme_id')->constrained()->onDelete('cascade');
$table->json('settings')->nullable();
سجل واحد فقط لكل مطعم (علاقة HasOne). الموديل RestaurantThemeSetting يوفّر getMergedSettings() وgetSetting()/setSetting() وresetToDefaults().

⚠️ تنبيه حرج: الـ Observer الخاص به (RestaurantThemeSettingObserver) يُنفّذ Cache::forget على مفتاح restaurant_theme_settings_{id} — وهو نفس مفتاح الكاش المستخدم في theme_setting(). لكن restaurant_theme_settings هو اسم الجدول نفسه، وهي مصادفة اسمية (نفس النمط لكن أحدهما جدول والآخر مفتاح كاش). لا مشكلة تقنية هنا، لكن يجب الحذر عند البحث/الاستبدال.

⚠️ عدم اتساق العلاقة: app/Models/Restaurant.php يعرّف public function themeSettings(): HasOne، لكن resources/views/components/theme-styles.blade.php يستدعي $restaurant?->themeSetting?->settings (مفرد!) — وهذه العلاقة غير موجودة، فسيعيد Laravel null دائماً → أي أن theme-styles لا يطبّق أي إعدادات محفوظة، ويكتفي بالقيم الافتراضية من allowed_variables. هذا خلل وظيفي حقيقي في محرر الثيمات (تفصيل أكثر في القسم 3).

2.4 جدول orders (المجمع مركّزياً)
النواة 2026_07_03_164519 + تعديلات متتالية (update_orders_table_for_checkout, add_offer_id, add_coupon_fields, add_delivery_tracking, add_on_way_order_status, add_restaurant_table_id, add_dine_in_delivery_type):

المجموعة	الأعمدة
العلاقات	restaurant_id (cascade), user_id (nullOnDelete), driver_id → users (nullOnDelete), restaurant_table_id (nullOnDelete), offer_id (nullOnDelete), coupon_id (nullOnDelete)
العميل	customer_name, customer_phone (صار nullable), customer_email
التوصيل	delivery_type enum(delivery,takeaway,dine_in), delivery_address, delivery_city, delivery_fee, delivery_latitude, delivery_longitude, driver_latitude, driver_longitude, driver_location_updated_at
المبالغ	subtotal, discount (خصم العرض), coupon_discount, discount_amount (عمود إضافي غير مستخدم في store), total_amount, final_amount, delivery_fee
الدفع	payment_method enum(cash,transfer), payment_status enum(pending,verified,rejected), payment_receipt, payment_proof (من migration لكن ليس في $fillable)
الحالة	status enum(pending,confirmed,preparing,ready,on_way,delivered,cancelled)
أخرى	notes, rejection_reason, tracking_code(unique), device_token, rating, review, delivered_at (في migration وليس في $fillable)
فهارس: ['restaurant_id','status'], tracking_code, customer_phone, ['restaurant_id','restaurant_table_id'].

جداول أخرى: categories, products (كلاهما SoftDeletes + فهرس ['restaurant_id','category_id','is_available']), order_items, invoices, reviews, images (morph), offers + pivot offer_product, coupons, restaurant_tables (qr_token unique)، drivers (جدول منفصل لكن غير مستخدم فعلياً — التفاصيل أدناه)، users, sessions, cache, jobs, passkeys.

2.5 العلاقات بين النماذج (Relationships)
markdown

User (role: customer | restaurant_admin | super_admin | driver)
   └─belongsTo─> Restaurant (restaurant_id, nullable)

Restaurant
   ├─hasMany─> Category ─hasMany─> Product
   ├─hasMany─> Product (SoftDeletes, images morphMany)
   ├─hasMany─> Order ─hasMany─> Order_item
   │              ├─hasOne─> Invoice
   │              ├─hasOne─> Review
   │              ├─belongsTo─> Coupon / Offer / Driver / RestaurantTable
   ├─hasMany─> RestaurantTable ─hasMany─> Order
   ├─hasMany─> Review / Offer / Coupon
...6 lines truncated. Use the buttons above to view or insert the full code.

ملاحظات دقيقة:

Order يعرّف public function review(): HasOne ويوجد أيضاً public $appends = ['type'] مع accessor getTypeAttribute() يرجع delivery_type.
Order_item هو الاسم الفعلي للكلاس (snake_case) — يخالف اصطلاح Laravel (OrderItem)، ويعرّف $appends = ['name','qty'] كـ aliases لـ product_name/quantity.
Order_item::product() يستخدم ->withTrashed() للحفاظ على إمكانية قراءة المنتج المحذوف في الطلبات القديمة. ✅ ممارسة صحيحة.
app/Models/Driver.php فارغ تماماً (class Driver { // }) — لا $fillable ولا علاقات. عملياً السائقون يُدارون عبر موديل User (دور driver)، وجدول drivers (migration 2026_09_17) معزول وغير مستخدم في أي Controller. علاقة Order::driver() تربط driver_id → Driver (الموديل الفارغ!) بينما driver_id هو FK فعلياً إلى users (حسب الـ migration constrained('users'))!!
🚨 عدم تطابق خطير بين الموديل والـ Schema: Order::driver() يقول belongsTo(Driver::class, 'driver_id') لكن قاعدة البيانات تُشير إلى users.id. أي استدعاء لـ $order->driver سيُنشئ استعلاماً على جدول drivers بمعرّف هو أصلاً users.id → نتائج خاطئة/فارغة. لاحظ أن DriverController وKdsOrderResource يستخدمان $order->driver?->name فعلاً. (بينما OrderForm في Filament يستخدم User::where('role','driver') كخيارات — تناقض آخر.)

2.6 الـ Observers المربوطة بالأحداث
مُسجّلة في AppServiceProvider::boot():

php

Restaurant::observe(RestaurantObserver::class);
RestaurantThemeSetting::observe(RestaurantThemeSettingObserver::class);
دورها حصرياً: إبطال كاش restaurant_theme_settings_{id}. لا توجد Observers لـ Orders/Products، ولا أحداث created على الطلبات تنتج إشعارات/بريد/واتساب.

3. محرر الثيمات والمعاينة الحية (Theme Editor & Live Preview)
3.1 كيف يعمل محرك الثيمات — النظرة الكاملة
المعمارية عبارة عن نظام ثيمات قائم على الملفات + سجل قاعدة بيانات + متغيرات قابلة للتخصيص لكل مطعم.

أ) دورة حياة الثيم:

الثيم موجود كمجلد تحت resources/views/themes/{folder_name}/ يحتوي theme.json حتمياً.
ThemeDiscoveryService::discover() يفحص المجلدات ويقرأ كل theme.json.
ThemeDiscoveryService::sync() يزامن المكتشف مع جدول themes (إنشاء/تحديث)، ويحذف سجلات الثيمات التي حُذف مجلدها بشرط ألا تكون مرتبطة بمطاعم (doesntHave('restaurants')).
ThemeHelper::view($restaurant, $viewName) يبني اسم الـ View ديناميكياً: themes.{folder_name}.{viewName} مع burger-theme كاحتياطي.
ب) الثيمات الفعلية الموجودة على القرص (3 ثيمات):

burger-theme (الافتراضي — theme.json يسمّي نفسه "برجر 2 كلاسيك" وslug: classic-2-burger)
classic-2-burger
classic-burger
⚠️ ملاحظة: الثلاثة متطابقة في البنية والمحتوى تقريباً (نفس أحجام الملفات تقريباً؛ نفس القوالب). ليس لدينا 3 تصاميم مختلفة فعلاً — بل نسخ من نفس الثيم. كذلك ThemeDiscoveryService لا يُستدعى من أي مكان في الكود (لا Artisan Command، لا Seeder يستدعيه) — أي أن المزامنة لا تحدث تلقائياً. (الدليل: grep لم يُظهر استدعاءً لـ sync().)

ج) theme.json والـ allowed_variables: هذه هي واجهة التحكم بالثيم — مصفوفة من المتغيرات الموصوفة بالبيانات (metadata-driven form). مثال فعلي من burger-theme/theme.json:

json

"allowed_variables": {
  "primary_color":  { "section":"الألوان والخطوط","type":"color","label":"اللون الرئيسي","default":"#FF6B35","css_var":"--primary-color" },
  "secondary_color":{ "section":"الألوان والخطوط","type":"color","default":"#FFFFFF","css_var":"--secondary-color" },
  "background_color":{ "section":"الألوان والخطوط","type":"color","default":"#1A1A1A","css_var":"--bg-color" },
  "font_family":    { "section":"الألوان والخطوط","type":"select","options":{"Tajawal":"...","Cairo":"...","Almarai":"..."},"default":"Tajawal","css_var":"--font-family" },
...8 lines truncated. Use the buttons above to view or insert the full code.
3.2 الخيارات المتاحة حالياً للتخصيص
المجموعة	المتغيرات	النوع	الربط بـ CSS
الألوان والخطوط	primary_color, secondary_color, background_color	color	--primary-color, --secondary-color, --bg-color
font_family	select (Tajawal/Cairo/Almarai)	--font-family
الهيدر والبانر	header_style	select (sticky/transparent)	❌ لا css_var
show_hero_banner	toggle	❌ لا css_var (يُقرأ بمنطق شرطي)
hero_title, hero_subtitle	text	❌ لا css_var (نصوص)
تصميم البطاقات	border_radius	select	--border-radius (مع تحويل تلقائي لـ px في theme_setting())
card_style	select (elevated/flat)	❌ لا css_var
3.3 مسار التخصيص الفعلي (الـ Flow)
يوجد مساران متوازيان لتخصيص الثيم — وهذا جوهر التعقيد في هذا الجزء:

المسار الأول: صفحة Filament RestaurantThemeSettings (المسار الحديث والمعتمد)

app/Filament/Restaurant/Pages/RestaurantThemeSettings.php — صفحة Filament مخصّصة (ليست Livewire جديدة، بل Page في لوحة Filament تستخدم InteractsWithForms).
بناء النموذج ديناميكياً (metadata-driven): تقرأ $theme->allowed_variables وتولّد الحقول بـ match($type):
php

'color'            => ColorPicker::make($key)->label($label),
'select'           => Select::make($key)->label($label)->options($config['options'] ?? []),
'boolean','toggle' => Toggle::make($key)->label($label),
default            => TextInput::make($key)->label($label),
وكل حقل ->live()->afterStateUpdated(fn ($state) => $this->dispatch('theme-variable-updated', key:..., value:..., cssVar:...)).
الحفظ: save() → RestaurantThemeSetting::updateOrCreate ثم Cache::forget(...) ثم Notification.
زر "إرجاع للوضع الافتراضي": getHeaderActions() → يقرأ defaults من allowed_variables[*].default ويحفظها.
الواجهة (resources/views/filament/pages/restaurant-theme-settings.blade.php):

شبكة من عمودين: (يسار) الفورم، (يمين) <iframe id="theme-preview-iframe" src="{{ route('restaurant.home', $slug) }}?preview=1">.
آلية المعاينة الحية عبر postMessage:
js

Livewire.on('theme-variable-updated', (event) => {
    const iframe = document.getElementById('theme-preview-iframe');
    iframe.contentWindow.postMessage({
        type: 'THEME_VARIABLE_CHANGE', key: event.key, value: event.value, cssVar: event.cssVar
    }, '*');
});
والـ iframe يستمع في components/theme-styles.blade.php:
js

window.addEventListener('message', (event) => {
    if (event.data?.type === 'THEME_VARIABLE_CHANGE') {
        document.documentElement.style.setProperty(event.data.cssVar, event.data.value);
    }
});
✅ هذه هي الآلية الأنيقة للمعاينة الحية — لا إعادة تحميل، تعديل مباشر لمتغيرات CSS داخل الـ iframe.
⚠️ مشاكل مؤكدة في مسار المعاينة:

theme-styles.blade.php يستخدم $restaurant?->themeSetting?->settings (مفرد) — العلاقة اسمها themeSettings (جمع) في Restaurant.php. النتيجة: $settings دائماً [] → لا تُطبّق الإعدادات المحفوظة عند تحميل الصفحة الفعلية، وتظهر القيم الافتراضية فقط.
الـ postMessage يستخدم '*' كـ targetOrigin — أي نافذة/إطار آخر قد يستمع للرسائل؛ ممارسة غير آمنة (يُفضّل تقييدها بالأصل).
cssVar قد يكون undefined: المتغيرات التي لا تملك css_var (header_style, card_style, hero_title...) يُمرّر لها "--{$key}" كاحتياطي — ثم يُنفّذ setProperty('--header_style', ...) وهو متغير غير موجود في CSS → لا تأثير فعلي عند تعديلها في المعاينة. أي أن نصف الخيارات لا تعطي معاينة حية.
الحفظ عبر save() لا يُحدّث الـ iframe (المعاينة الحية تحدث فقط قبل الحفظ من live()).
المسار الثاني: theme-customizer.blade.php (Legacy — غير مستخدم في مسار Filament)

مكوّن Blade <x-theme-customizer :allowedVariables :currentSettings> يولّد حقولاً من نفس الـ metadata ولكن بأنواع color|select|boolean|else، ويحفظ عبر axios.post('/restaurant/api/theme/settings').
يقابلها App\Http\Controllers\Restaurant\ThemeSettingsController بـ getSettings()/updateSettings().
updateSettings() فيه فلترة أمنية جيدة: يقبل فقط المفاتيح الموجودة في allowed_variables:
php

$allowedKeys = array_keys($allowedVariables);
foreach ($allowedKeys as $key) {
    if (array_key_exists($key, $submittedSettings)) $filteredSettings[$key] = $submittedSettings[$key];
}
لكن هذا المكوّن لا يبدو مستدعى من أي View في المسار الحالي (لم يظهر في resources/views/themes/*)، أي أنه كود ميت أو شبه ميت.
المسار الثالث: ThemeController (إدارة على مستوى المنصة)

app/Http/Controllers/ThemeController.php — CRUD للثيمات: index, create, store, show, edit, update, activate, clone, resetSettings, preview, destroy.
المسارات في routes/web.php داخل middleware(['auth']) ✅ (الوثيقة القديمة قالت إنها غير محمية — الواقع الحالي يقول إنها محمية بـ auth، لكن بدون أي فحص دور: أي مستخدم مصادق عليه — حتى دور customer — يمكنه POST /themes/{theme}/activate وحذف/استنساخ الثيمات! 🔴 ثغرة صلاحيات حقيقية).
preview() تُعيد view('themes.preview') — لكن ملف resources/views/themes/preview.blade.php غير موجود على القرص (glob لم يجد أي .blade.php في resources/views/themes/ مباشرة). أي أن زر "معاينة" في ThemesTable سيُطلق ViewException. 🔴
ولا يوجد app/Http/Controllers/ThemeController في الـ ThemeResource الخاصة بلوحة sham — التفعيل/النسخ يتمّان مباشرة داخل ThemesTable عبر Actions (أفضل).
أ) أين تُطبَّق متغيرات الثيم على الـ CSS؟ في resources/views/themes/burger-theme/layout.blade.php (الأسطر 42–56):

css

:root {
    --primary: {{ theme_setting('primary_color', '#FF6B35') }};
    --secondary: {{ theme_setting('secondary_color', '#FFFFFF') }};
    --background: {{ theme_setting('background_color', '#1A1A1A') }};
    --dark: {{ theme_setting('secondary_color', '#111827') }};
    --font-family: '{{ theme_setting("font_family", "Tajawal") }}', sans-serif;
    --border-radius: {{ theme_setting('border_radius', '8px') }};
}

⚠️ لاحظ عدم تطابق أسماء المتغيرات: هنا --primary و--secondary، لكن theme.json يعرّف css_var: "--primary-color" و--secondary-color. أي أن الحفظ من Filament يستهدف --primary-color بينما القالب يقرأ --primary. النتيجة: قد لا تنعكس بعض التعديلات المحفوظة على القالب الفعلي — إلا عبر دالة theme_setting() التي تقرأ القيمة من قاعدة البيانات مباشرة عند render (وهو ما ينقذ الموقف في القالب الحالي). لكن theme-styles.blade.php (الذي يعتمد على css_var) يُنتج متغيرات بأسماء أخرى لا يستخدمها القالب. خلل مقاربة (Approach Mismatch) مهم يستحق التوحيد.

ب) كمية العمل: burger-theme/layout.blade.php حجمه ~36 KB، وdriver/tracking.blade.php ~80 KB، وkds-board.blade.php ~64 KB — ملفات Blade ضخمة جداً تحتوي CSS و JS مضمّنين مباشرةً. هذا يزيد كلفة الصيانة بشكل كبير.

4. لوحة Filament وإدارة المطاعم
4.1 اللوحتان (Two Panels)
لوحة /restaurant — app/Providers/Filament/RestaurantPanelProvider.php:

الإعداد	القيمة
id / path	restaurant / restaurant
brandName	"لوحة المطاعم"
colors	primary: Orange, gray: Zinc
font	Tajawal
spa()	✅ مُفعّل
darkMode	❌ معطّل
viteTheme	resources/css/filament/restaurant/theme.css
مجموعات التنقل	التشغيل / القائمة / التسويق / الإعدادات
لوحة /sham — app/Providers/Filament/ShamPanelProvider.php:

default() ✅ (اللوحة الافتراضية عند /admin؟ لا — المسار sham)؛ path: sham؛ نفس الألوان والخط؛ spa() مُفعّل؛ darkMode معطّل.
⚠️ ملاحظة كود: authMiddleware([Authenticate::class]) معرّفة مرتين في نفس السلسلة (تكرار غير ضار لكنه دليل على تعديل متعجّل).

4.2 الـ Resources المعرّفة
لوحة المطعم (App\Filament\Restaurant\Resources) — 8 موارد:

Resource	Model	Policy	Group
CategoryResource	Category	CategoryPolicy	القائمة
ProductResource	Product	ProductPolicy	القائمة
OrderResource	Order	OrderPolicy	التشغيل
InvoiceResource	Invoice	—	التشغيل
OfferResource	Offer	—	التسويق
CouponResource	Coupon	—	التسويق
DriverResource	User (role=driver)	—	التشغيل
RestaurantTableResource	RestaurantTable	—	التشغيل
لوحة الإدارة العامة (App\Filament\Sham\Resources) — 6 موارد: RestaurantResource, CategoryResource, ProductResource, OrderResource, InvoiceResource, ThemeResource.

🚨 ملاحظة خطيرة: لوحة sham تُعيد إعادة استخدام نفس مسارات App\Filament\Sham\Resources\{Categories,Products,Orders,Invoices} — وهي نسخ مكرّرة من موارد لوحة المطعم (نفس البنية تحت مسار مختلف). وموارد لوحة sham لا تعيد تعريف getEloquentQuery() (تحققت: RestaurantResource وThemeResource لا تعرّفانه) — وهذا مقبول لأن sham مخصّصة للـ super_admin. لكن موارد Categories/Products/Orders في sham — يجب التحقق هل تُقيّد أم تفتح كل شيء فعلاً. بحسب الحاجة التصميمية، admin عام يرى الكل. لا خطر هنا بشرط أن canAccessPanel تمنع غير الـ super_admin (وهي كذلك — القسم 4.4).

4.3 الـ Pages
RestaurantDashboard (app/Filament/Restaurant/Pages/RestaurantDashboard.php): getSlug() تُعيد '' → أي أنها صفحة الهوم الفعلية لـ /restaurant. تجلب بياناتها من RestaurantDashboardService::forRestaurant(auth()->user()?->restaurant).
الخدمة تُنتج: sales_today (مجموع total_amount لطلبات اليوم)، active_orders (pending/confirmed/preparing/ready)، customers_today (عدد هواتف فريدة)، average_order, وrecent_orders (آخر 6).
⚠️ sales_today تجمع total_amount — وهو subtotal فعلياً حسب OrderController::store (السطر 250: 'total_amount' => $subtotal). لذا "مبيعات اليوم" المعروضة في اللوحة لا تشمل الضريبة ولا رسوم التوصيل وتتجاهل الخصومات — رقم مضلل تجارياً. في المقابل recent_orders تستخدم $order->total_amount ?? 0. العمود الصحيح للعرض المالي هو final_amount.

RestaurantThemeSettings: صفحة إعدادات المظهر (تفصيلها في القسم 3.3).
Dashboard (Filament القياسي) في لوحة sham.
4.4 الأدوار والصلاحيات (Authorization)
الأدوار الفعلية (من users.role enum + migration عدّلته إلى string default customer): customer | restaurant_admin | super_admin | driver (مُضاف في الكود/المصانع دون أن يكون في enum الـ migration الأصلي!).

التحكم بالوصول للوحات — User::canAccessPanel():

php

public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'restaurant' => in_array($this->role, ['restaurant_admin','super_admin'], true)
            && ($this->role === 'super_admin' || filled($this->restaurant_id)),
        'sham' => $this->role === 'super_admin',
        default => false,
    };
}

✅ منطق سليم: مطعم يحتاج restaurant_id؛ sham للـ super_admin فقط. لكن: دور driver والـ customer لا يستطيعان دخول أي لوحة Filament — والسائق يدخل لوحة /{slug}/driver/... عبر Controllers عادية (راجع 5).

الـ Policies (app/Policies/): CategoryPolicy, ProductPolicy, OrderPolicy — ثلاثتها متطابقة البنية:

php

protected function isRestaurantScoped(User $user): bool
{
    return isset($user->restaurant_id) && $user->restaurant_id !== null;
}
// view: isRestaurantScoped($user) && $model->restaurant_id === $user->restaurant_id
⚠️ ثغرة منطقية محتملة: isRestaurantScoped() تتحقق فقط من وجود restaurant_id، ولا تتحقق من الدور. أي super_admin بلا restaurant_id سيُرفض من كل عمليات هذه الـ Policies — بما فيها في لوحة sham! (لوحة sham تعتمد على canAccessPanel فقط وليس على Policies هذه إلا إذا كانت موارد sham تستخدمها). عملياً: موارد sham للـ Categories/Products/Orders قد تفشل للـ super_admin بلا restaurant_id. هذا تناقض يستحق التحقق.

OfferResource/CouponResource/InvoiceResource/DriverResource/RestaurantTableResource بلا Policies — حمايتها تعتمد فقط على getEloquentQuery() في الـ Resource، وهذا لا يحمي عمليات create/update/delete (فقط الفهرسة). أي حسب مستندات Filament، قد يكون مستخدمو مطعم قادرين على تعديل سجلات خارج نطاقهم عبر استدعاء مباشر للـ Action IDs. يُنصح بإضافة Policies لهذه الموارد.

4.5 الإجراءات (Actions) والوظائف المتاحة
لصاحب المطعم (لوحة /restaurant):

الوحدة	الوظائف
المنتجات	CRUD + تعطيل/تنشيط (is_available) + is_featured + sort_order + صور (FileUpload على disk public في products/ + بديل رابط URL)
التصنيفات	CRUD + sort_order + is_active
الطلبات	قائمة بـ 19 عموداً (بحث/ترتيب) + Edit + حذف جماعي. حقل driver_id يُعبّأ من User::where('role','driver')->where('restaurant_id', auth()->user()->restaurant_id)
الفواتير	CRUD
العروض	CRUD بنموذج مقسّم 5 أقسام (معلومات، النوع/القيمة، الفترة، حدود الاستخدام، المنتجات المشمولة) + apply_to_all/product_ids
الكوبونات	CRUD بنفس النمط + max_uses_per_user + منتجات محددة
المندوبون	CRUD على User بدور driver (auto-filled restaurant_id وrole=driver مخفيان)
الطاولات و QR	CRUD + عرض صفحة QR (/restaurant/tables/{table}/qr)
لوحة التحكم	إحصائيات مباشرة
إعدادات المظهر	محرر ثيم + معاينة حية
المطبخ (KDS)	خارج Filament: /{slug}/kds → /kitchen/{slug}/display (صفحة ثيم)
لأدمن المنصة (لوحة /sham):

Resource	الوظائف
المطاعم	CRUD كامل: معلومات، شعار/غلاف (رفع أو رابط)، تواصل/موقع، delivery_fee, estimated_delivery_time, اختيار theme_id (required)، ألوان، QR الدفع، bank_details, نموذج التسعير (pricing_type/commission_rate/subscription_fee), is_active، trial_ends_at
السمات (Themes)	headerAction رفع ثيم ZIP + Actions على كل سجل: معاينة، تفعيل، إيقاف، نسخ، إعادة تعيين إعدادات المطاعم، تعديل، حذف (مع حماية من حذف ثيم مستخدم)
المطاعم/الفئات/المنتجات/الطلبات/الفواتير	إدارة على مستوى المنصة
رفع الثيم (ZIP) — أكبر إجراء تقني في المشروع: ThemeUploadService::upload() (422 سطراً) — تفصيله في القسم التالي، وهو يطبّق فحوصاً أمنية متعددة الطبقات.
4.6 ThemeUploadService — الفحوص الأمنية (نقطة قوة حقيقية في المشروع)
الطبقة	الفحص	التفصيل
1	Magic Bytes / MIME	mime_content_type($zipPath) يجب أن يكون application/zip أو application/x-zip-compressed أو multipart/x-zip
2	Zip Slip + Zip Bomb	inspectZipArchive(): حظر .. و/ و\ في أسماء الملفات؛ حد أقصى 150 ملف؛ حجم إجمالي بعد فك الضغط 30 MB
3	حظر الملفات التنفيذية	يرفض .php (غير .blade.php)، .phar, .phtml, .php3-7, .phps, .exe, .sh, .bat, .htaccess, .env
4	Deep Scan لمحتوى Blade	scanExtractedFiles(): يرفض وجود <?php, <?=, @php داخل أي ملف *.blade.php ويبحث عن forbiddenPhpFunctions (system, exec, shell_exec, eval, base64_decode, proc_open, popen, phpinfo, assert, preg_replace, unserialize...)
5	Whitelist الامتدادات	json, css, js, png, jpg, jpeg, webp, svg, gif, woff, woff2, ttf, eot
6	الملفات الإلزامية	17 ملفاً إلزامياً: theme.json, layout.blade.php, pages/{home,menu,product,offers,kds-board}, cart/{cart,checkout,success}, orders/{track,track-form,invoice}, reviews/{reviews,review-form}, driver/{dashboard,orders,tracking}
7	Sanitize البيانات الوصفية	filter_var(..., FILTER_SANITIZE_SPECIAL_CHARS) على name/author/version/description
8	عزل مؤقت + تنظيف	فك الضغط في storage/app/temp/themes/{random32}، وحذفه في finally دائماً
9	حماية من الدهس (Overwrite)	يرفض إذا وُجد مجلد بالاسم مسبقاً
⚠️ ثغرة متبقية وهامة: رغم حظر @php و<?php>، لا يوجد حظر لـ Blade Directives الأخرى مثل @include(path) أو @extends أو {{ }} مع استدعاء دوال خطيرة أو @php ببديل (سبايس @ph@p)، ولا فحص لـ نقص sanitization في SVG (XSS عبر SVG). كما أن دالة preg_replace في قائمة الحظر مبالغ فيها (دالة شائعة ومفيدة). بشكل عام: هذا أفضل جزء أمني في المشروع، لكنه ليس تحصيناً كاملاً (لا sandboxing حقيقي — الثيم يُنفّذ كـ Blade داخل تطبيق Laravel، وBlade قادر على استدعاء أي helper أو facade مسموح في السياق).

⚠️ تعارض مع الاختبار: tests/Feature/Theme/ThemeUploadServiceTest.php فاشل حالياً (asserts that false is true عند الـ upload) — أي أن هذه الخدمة مكسورة أو متطلباتها (17 ملفاً إلزامياً) لا يحققها الاختبار.

5. واجهة المتجر والتوصيل (Storefront & Delivery)
5.1 تدفق تصفح المنيو وإنشاء السلة
المسارات (من routes/web.php, Route::prefix('{slug}')):

GET  /{slug}                → RestaurantController@home       (restaurant.home)
GET  /{slug}/menu           → RestaurantController@menu       (restaurant.menu)
GET  /{slug}/product/{id}   → RestaurantController@showProduct(product.show)
GET  /{slug}/offers         → OfferController@index           (offers.index)
GET  /{slug}/table/{token}  → RestaurantTableController@enter (restaurant.table.menu)
GET  /{slug}/cart           → CartController@index            (cart.index)
POST /{slug}/cart/add       → CartController@add              (cart.add)
POST /{slug}/cart/update    → CartController@update           (cart.update)
POST /{slug}/cart/remove    → CartController@remove           (cart.remove)
GET  /{slug}/checkout       → OrderController@checkout        (checkout)
POST /{slug}/checkout       → OrderController@store           (checkout.store)
GET  /{slug}/order/success/{code}  → OrderController@success
POST /{slug}/apply-offer    → OrderController@applyOffer
POST /{slug}/apply-coupon   → OrderController@applyCoupon
GET/POST /{slug}/track...   → OrderController (trackForm/trackSearch/track/trackingLocation)
GET  /{slug}/reviews        → ReviewController@index
GET/POST /{slug}/review/{code} → ReviewController
GET  /{slug}/invoice/{code} → InvoiceController@showInvoice
GET  /{slug}/manifest.json  → PwaController@manifest
GET  /{slug}/sw.js          → PwaController@serviceWorker
آلية السلة (Cart):

مخزنة في Session بمفتاح فريد لكل مطعم: sessionKey = 'cart_'.$restaurant->id — وهذا إصلاح جيد يحلّ مشكلة اختلاط سلال المطاعم التي أشارت إليها الوثيقة القديمة. بنيتها: [productId => ['qty' => n]].
الإضافة (CartController@add) تتحقق من: وجود المنتج restaurant_id = المطعم الحالي و**is_available = true** → ثم تحدّث الـ Session.
رد AJAX: إذا $request->wantsJson() || ajax() يُعيد JSON فيه count, subtotal, total, grand_total = total*1.15, وitems[]. القالب يعترض الـ submit في layout.blade.php (سطر 484+) ويرسل axios.post(form.action, new FormData(form)) ثم showToast() وupdateMiniCart().
السلة المصغّرة (Mini-cart): تُحدَّث ديناميكياً عبر updateMiniCart(data) من الـ JSON (badge, count, total, items HTML).
CartController@index يجلب المنتجات بـ Product::find($id) ثم يتحقق من restaurant_id — أي لا يفلتر is_available عند العرض (عرض منتج غير متاح ممكن؛ المنع يحدث في checkout). (متوافق مع ملاحظة الوثيقة).
تدفق الـ Checkout والحسابات — OrderController:

checkout(): يحسب subtotal من أسعار DB، ثم tax = subtotal * 0.15 (ضريبة 15% مضمّنة hard-coded)، ثم يشتقّ baseSubtotal = total / 1.15 وbaseTax = total - baseSubtotal (أي يفترض أن الأسعار شاملة الضريبة)، ويحسب خصم العرض التلقائي (activeOffers بحد أدنى ≤ baseSubtotal), ويجهّز initialFinalTotal.
store(): يُعيد التحقق من كل شيء من الخادم (لا يثق بأسعار المتصفح ✅)، يحسب:
php

$tax = $subtotal * 0.15;
$deliveryFee = $restaurant->delivery_fee ?? 0;
$totalDiscount = $offerDiscount + $couponDiscount;
$finalAmount = $subtotal + $tax + $deliveryFee - $totalDiscount;
ثم يُنشئ Order + Order_item[] + Invoice (بترقيم INV-{year}-{6-digit})، يزيد used_count للعرض والكوبون، يفرّغ السلة، ثم event(new OrderCreated($order)).
device_token (40 حرفاً عشوائياً) يُحفظ في Cookie order_device_token_{orderId} لمدة 7 أيام — يُستخدم لتأمين صفحة التتبع: لا يمكن الوصول لصفحة تتبع الطلب إلا من نفس جهاز الإنشاء (أو السائق المسؤول). ✅ آلية أمنية ذكية.
🚨 مشاكل حرجة مؤكدة في OrderController::store() (الأهم في التقرير كله):

لا يوجد DB::transaction: إنشاء الطلب ثم العناصر ثم الفاتورة ثم تحديث used_count كلها عمليات منفصلة. أي فشل في المنتصف → طلب بلا عناصر أو بلا فاتورة. (مخاطرة مالية/تشغيلية).
total_amount محفوظ كـ $subtotal (سطر 250) بينما final_amount هو المبلغ الحقيقي. أي أن عمود "الإجمالي" في اللوحة والفواتير والتقارير خاطئ ويجب توحيد المعنى. (مثال حي: RestaurantDashboardService تجمع total_amount → تظهر مبيعات ناقصة).
used_count يُزاد قبل نجاح العملية — إذا فشل إنشاء العناصر أو الفاتورة، يُستهلك الكوبون/العرض بلا طلب.
coupon_id يُقبل من الطلب مع coupon_code — والتحقق يشترط فقط restaurant_id مطابق. لا يوجد تحقق أن coupon_code المُرسل يطابق coupon.code الفعلي. أي يمكن إرسال coupon_id صحيح مع coupon_code مزيف — يُسجّل الكود المزيف في الطلب/الفاتورة.
offer_id/coupon_id لا يُتحقق أنهما من نفس الطلب — إمكانية ازدواج خصومات (لا قاعدة "منع الجمع" مطبقة).
العروض لا تُقيّد بـ appliesToProduct: يوجد في الموديل appliesToProduct() وعلاقة offer_product، لكن store() لا يستخدمها إطلاقاً — أي عرض محدّد بمنتجات معينة سيطبَّق على كامل السلة. 🚨
ضريبة 15% مضمّنة (hardcoded) في 3 أماكن مختلفة بمنطقين مختلفين (شامل/غير شامل) — تعارض فعلي بين checkout() (يفترض شامل: total/1.15) وstore() (يضيف 15% على subtotal). قد يختلف المبلغ المعروض في صفحة الـ checkout عن المبلغ المحفوظ في الطلب!
CartController@index يعرض المنتجات غير المتاحة (لا فلترة is_available).
applyCoupon يقبل subtotal من العميل ('subtotal' => 'required|numeric') ويحسب الخصم عليه — لكن store() يُعيد الحساب من الخادم ✅ (الـ AJAX عرض فقط، لا خطر مباشر، ولكن يجب توثيقه).
5.2 تحديد الموقع (GPS)، حساب تكلفة التوصيل، الخرائط
✅ نعم — النظام غني بالفعل بهذه الميزات:

أ) الخرائط (Leaflet + OpenStreetMap): لا يوجد Google Maps، بل Leaflet 1.9.4 من CDN unpkg مع بلاطات tile.openstreetmap.org. موجودة في:

cart/checkout.blade.php (سطر 142+): خريطة اختيار موقع التوصيل — $table متغيّر يحكم الإظهار (لا خريطة للـ dine_in). نقطة افتراضية = دمشق [33.5138, 36.2765]. عند click → setCheckoutLocation(lat, lng) تُحدّث الحقول المخفية latitude/longitude ونص #map-status. وعند تحميل الصفحة: navigator.geolocation.getCurrentPosition() تُرتكز الخريطة على موقع العميل.
driver/dashboard.blade.php وdriver/orders.blade.php: خريطة السائق وموقع العميل (@json([$featuredOrder->delivery_latitude, ...])).
orders/track.blade.php: خريطة تتبّع موقع التوصيل.
driver/tracking.blade.php (80 KB): نظام تتبّع كامل مع توجيه المسار عبر OSRM (router.project-osrm.org) — يظهر في CSP: connect-src 'self' https://router.project-osrm.org.
ب) GPS الحي من جهة السائق:

DriverController@updateLocation() يستقبل latitude/longitude (validated between:-90,90 وbetween:-180,180)، يحفظ في orders.driver_latitude/longitude/driver_location_updated_at، ويحوّل الحالة تلقائياً ready → on_way. محمي بـ authorizeDriverOrder() (يتحقق من دور driver + نفس restaurant_id + order->driver_id === $driver->id). ✅ صلاحيات صحيحة.
في الواجهة: navigator.geolocation.watchPosition(...) يرسل الموقع دورياً (سطر ~1999 في driver/tracking.blade.php).
ج) حساب تكلفة التوصيل:

رسوم ثابتة فقط (restaurants.delivery_fee) — تُطبَّق في OrderController::store() كما هي، وتصبح صفراً في حالة dine_in.
🔴 لا يوجد حساب مسافة فعلي (لا Haversine، لا OSRM distance) ولا تسعير حسب المنطقة/المسافة. حقل delivery_city موجود لكنه لا يؤثر على السعر.
د) تتبع الطلب للعميل:

مسار GET /{slug}/track/{code}/location (OrderController@trackingLocation) يُعيد JSON: الحالة + إحداثيات العميل + إحداثيات السائق + وقت التحديث. (⚠️ هذا الـ endpoint عام بدون أي مصادقة — أي معرفة tracking_code تتيح قراءة موقع السائق والعميل. 🔴 خطر خصوصية: يجب تأمينه بكوكي device_token على الأقل، كما يفعل DriverController@tracking).
هـ) الطاولات والـ QR (Dine-in):

restaurant_tables بـ qr_token (24 حرفاً عشوائياً من Str::random). RestaurantTable::qrUrl() تُولّد الرابط المطلق {APP_URL}/{slug}/table/{token}.
زيارته → RestaurantTableController@enter: يحفظ restaurant_table_id + restaurant_table_restaurant_id في الـ Session ويحوّل للـ menu. بعدها يصبح نوع الطلب dine_in تلقائياً (لا يطلب عنواناً ولا موقعاً ولا رسوم توصيل).
table-qr.blade.php + filament/tables/qr-code.blade.php لعرض رمز QR للطباعة.
و) PWA: PwaController@manifest() يُولّد manifest ديناميكياً لكل مطعم (name/short_name/start_url/scope/icons/screenshots/theme_color من ألوان المطعم)، وserviceWorker() يُولّد SW ديناميكياً بـ cache-first واسم كاش بإصدار {slug}-v3. يُسجَّل في layout.blade.php (سطر 555+).

ز) KDS (شاشة المطبخ):

/{slug}/kds → redirect إلى /kitchen/{slug}/display → KdsController@index يعرض الطلبات pending+preparing في قالب kds-board. يستمع لحظياً على Echo.private('restaurant.{id}.kds') لحدث order.created.
KdsController@updateStatus يحدّث الحالة عبر tracking_code.
⚠️ ثغرة: updateStatus لا تتحقق من صلاحيات — أي مستخدم مصادق يمكنه تغيير حالة أي طلب (وأيضاً: قائمة الحالات المسموحة في الـ validation new,preparing,ready,completed لا تطابق enum قاعدة البيانات (pending, confirmed, preparing, ready, on_way, delivered, cancelled) — أي completed سيرمي خطأ SQL.)

6. تكامل الواتساب والتنبيهات (WhatsApp Integration)
🚨 الإجابة الحاسمة: لا يوجد أي تكامل واتساب في هذا المشروع — لا Direct Link ولا Node.js/Baileys ولا WhatsApp Business API.
هذا استنتاج مؤكد من فحوص متعددة:

لا يوجد مجلد whatsapp-service ولا whatsapp في جذر المشروع (تحقّقنا: Test-Path أعاد False).
البحث الكامل في المشروع عن whatsapp, WhatsApp, wa.me, baileys لم يُنتج إلا نتيجتين، وكلتاهما في resources/views/welcome.blade.php (صفحة تعريفية ثابتة تسويقية):
سطر 1126–1127: <i class="fa-brands fa-whatsapp"> ... +963 944 123 456 (رقم دعم تجريبي في صفحة الهبوط).
سطر 1138–1140: زر <a href="#"> بـ أيقونة واتساب في الفوتر. لا كود، لا طلبات شبكة، لا منطق إرسال.
لا حزم Node.js للواتساب (whatsapp-web.js, @whiskeysockets/baileys) في package.json — القائمة كاملة: passkeys, tailwind, concurrently, vite, echo, pusher-js, postcss, autoprefixer.
لا تكامل طرف ثالث في config/services.php (تحقّقنا من قائمة الحزم).
لا Jobs/Listeners/Notifications: لا يوجد app/Jobs, ولا app/Listeners, ولا app/Notifications في القائمة الكاملة لملفات app/.
آلية التنبيهات الفعلية (بديل الواتساب الحالي)
القناة	الحالة	التفصيل
WebSocket (Reverb)	✅ الوحيدة المُفعّلة	OrderCreated (ShouldBroadcastNow) → قناة restaurant.{id}.kds خاصة → يستمع لها kds-board فقط لعرض الطلب الجديد لحظياً في شاشة المطبخ
Filament Notifications	✅ محلي فقط	تُستخدم في RestaurantThemeSettings وThemesTable لأرشفة نجاح/فشل الإجراءات (تعرض للمستخدم الحاضر فقط)
البريد (Mail)	⚙️ مُهيّأ نظرياً	MAIL_MAILER=log — أي أن أي بريد يُكتب في السجلات فقط، لا إرسال فعلي
Queue	✅ مُهيّأ	QUEUE_CONNECTION=database + jobs table، لكن لا يوجد أي Job مُعرّف في المشروع؛ الـ queue worker يعمل بلا مهام
كيف تصل الطلبات للمطعم فعلاً اليوم؟ فقط بطريقتين:

شاشة KDS المفتوحة (/kitchen/{slug}/display) تتلقى order.created لحظياً.
لوحة Filament يدوياً: صاحب المطعم يفتح قائمة الطلبات ويراجعها (لا إشعار صوتي، لا Polling).
🔴 النتيجة العملية: أي "ربط واتساب" في وثائق/شاشات المشروع هو وعد غير منفّذ. إذا كان المستشار التقني يفترض وجود تكامل، فيجب تصحيح هذا الفهم فوراً. إضافته ستحتاج:

إما WhatsApp Cloud API من Meta (رسمي، مع قوالب معتمدة وwebhook) — خارج Laravel حالياً.
أو خدمة Node.js منفصلة بـ Baileys/whatsapp-web.js مع Queue/Broadcast مشترك (القناة الموجودة OrderCreated جاهزة كنقطة بدء للإرسال).
7. الملفات الهيكلية الأساسية (Key Code Snippets)
7.1 app/ — منطق التطبيق
النماذج (app/Models/) — 14 موديل:

الملف	الدور
Restaurant.php	المستأجر المركزي: I'll continue the report from Section 7.
7.1 app/ — منطق التطبيق
النماذج (app/Models/) — 14 موديل:

الملف	الدور الجوهري
Restaurant.php	المستأجر المركزي (Tenant). يحمل slug, subdomain (غير مستخدم), theme_id, delivery_fee, حقول التسعير (pricing_type/commission_rate/subscription_fee), و SoftDeletes. العلاقات: categories, products, orders, tables, reviews, offers/activeOffers, coupons/activeCoupons, theme, themeSettings. Helpers: getThemePath(), getThemeSettings(), getAverageRatingAttribute, getReviewsCountAttribute, getFullSubdomainAttribute() (غير مستخدم). getRouteKeyName() → slug.
User.php	يطبّق FilamentUser و PasskeyUser. أهم دالة: canAccessPanel(Panel) للحكم على دخول اللوحات. يستخدم #[Fillable(...)] و#[Hidden(...)] (Attributes بدل الخصائص). ⚠️ المصفوفة Fillable تحتوي 'addreas' (خطأ إملائي لكلمة address!) بينما العمود الفعلي في القاعدة هو address — أي أن حقل العنوان غير قابل للتعبئة الجماعية (mass assignment) عبر User::create لأن الاسم خطأ.
Theme.php	وصف الثيم: folder_name, default_settings, allowed_variables. دوال: themeExists(), hasThemeJson(), loadThemeJson(), activate()/deactivate() (تفعيل حصري واحد), resetSettingsForRestaurants(), duplicate().
RestaurantThemeSetting.php	جدول الإعدادات المخصّصة 1:1 للمطعم. getMergedSettings() (defaults + custom)، getSetting()/setSetting(), resetToDefaults().
RestaurantTable.php	طاولة dine-in + qr_token (يُولد في booted() عبر Str::random(24)). qrUrl() تبني رابط QR مطلق. ⚠️ تحتوي boot() مكرر (boot + booted) مع حقن restaurant_id غير ضروري.
Order.php	النموذج المركزي للطلبات. $appends=['type']. العلاقات: review, coupon, offer, restaurant, driver (⚠️ مرتبطة بموديل Driver الفارغ)، restaurantTable, invoice, items. getIsReviewedAttribute(). boot() حقن restaurant_id.
Order_item.php	عنصر الطلب. يحفظ product_name وprice لقطة زمنية (Snapshot) — ممتاز لسلامة الفواتير. product() بـ withTrashed(). $appends=['name','qty'] (aliases).
Product.php	SoftDeletes, is_available, is_featured, old_price, sort_order. علاقات morph للصور: images(), primaryImage(). boot() حقن restaurant_id.
Category.php	تصنيف المنتجات + sort_order, is_active. boot() حقن restaurant_id (بشرط role != super_admin).
Offer.php	عروض تلقائية: type (percentage/fixed_amount/free_product/free_shipping), value, min_order_amount, max_uses, starts_at/ends_at, apply_to_all/product_ids, علاقة products() عبر pivot offer_product. scopeActive(), isCurrentlyActive(), getDiscountAmount(), appliesToProduct().
Coupon.php	كوبونات برمز: نفس البنية + max_uses_per_user. isValidFor($subtotal, $customerPhone) (تحقق كامل مع رسائل عربية). ⚠️ منطق boot() غير مُسجّل عند غياب auth.
Invoice.php	فاتورة: invoice_number, subtotal, tax_amount, total_amount, status, paid_at.
Review.php	تقييم مربوط بـ order_id + restaurant_id، مع صور morph.
Image.php	صور polymorphic (imageable) لـ Product و Review.
Driver.php	🔴 فارغ تماماً — كود ميت/ناقص رغم وجود جدول drivers وعلاقة Order::driver().
الـ Controllers (app/Http/Controllers/) — 18 ملف:

الملف	الدور
RestaurantController.php	واجهة العميل: home (الصفحة الرئيسية + التصنيفات)، menu (تصنيفات + منتجات متاحة + سلة مصغّرة محسوبة)، showProduct (منتج + تقييماته)، و index (Legacy — يستخدم restaurant.dashboard).
CartController.php	السلة بالكامل: index/add/update/remove بمفتاح cart_{restaurantId}، مع رد JSON للتحديث اللحظي.
OrderController.php	🔴 أهم وأخطر ملف في المشروع: checkout, store (إنشاء الطلب+العناصر+الفاتورة), success, trackForm, trackSearch, track, trackingLocation, applyOffer, applyCoupon. كل المخاطر المذكورة في القسم 5.1 هنا.
DriverController.php	لوحة السائق: dashboard, index (الطلبات المتاحة)، acceptOrder, show, tracking, updateLocation, updateStatus + authorizeDriverOrder() وorderPayload(). ✅ صلاحيات متسقة.
KdsController.php	شاشة المطبخ: index, updateStatus. ⚠️ updateStatus بلا صلاحيات + قيم حالات غير مطابقة للـ enum.
ReviewController.php	التقييمات: create/store/index + AJAX (verify, storeAjax) + رفع صور التقييم. يشترط حالة delivered وانتظار 10 دقائق.
InvoiceController.php	showInvoice — عرض فاتورة الطلب.
OfferController.php	index — صفحة العروض النشطة.
RestaurantTableController.php	enter (QR → session → menu) و qr (عرض QR للمطعم، محمي بـ restaurant_id).
PwaController.php	manifest + serviceWorker ديناميكي لكل مطعم.
ThemeController.php	🔴 CRUD للثيمات على مستوى المنصة مع activate/clone/resetSettings/preview/destroy. 🚨 محمي بـ auth فقط بلا تحقق دور، وpreview() يستدعي View غير موجود.
Restaurant/ThemeSettingsController.php	API قديم لإعدادات الثيم (getSettings/updateSettings) بفلترة قوية على المفاتيح المسموحة.
RestaurantDashboardController.php	summary — JSON لإحصائيات اللوحة.
ThemeController / ImageController / ProductController / CategoryController / CouponController / RestaurantTableController / OrderItemController	🔴 معظمها Stubs فارغة (كل الدوال //) — كود CRUD قديم متروك بعد الانتقال إلى Filament. ImageController مثال كامل على ذلك. ProductController/CategoryController/OrderItemController/CouponController كذلك.
الـ Livewire (app/Livewire/):

الملف	الدور
Settings/Profile.php, Settings/Security.php, Settings/Appearance.php, Settings/DeleteUserForm.php, Settings/TwoFactor/RecoveryCodes.php	صفحات إعدادات الحساب (من الـ starter kit). ⚠️ Appearance.php فارغ فعلياً (//) رغم وجود مسار وواجهة — ميزة غير مكتملة.
Actions/Logout.php	تسجيل الخروج.
المفارقة المعمارية الأهم: المشروع انتقل لبناء لوحات الإدارة بالكامل في Filament (PHP Classes)، لكن واجهة العميل لا تزال Blade + Controllers تقليدية + jQuery-less Vanilla/Axios، وليس Livewire. أي أن Livewire 4 موجود فعلياً لصفحات الإعدادات فقط — لا دور له في المتجر أو Filament.

الـ Services (app/Services/):

الملف	الدور
ThemeDiscoveryService.php	discover() (قراءة المجلدات + theme.json)، sync() (مزامنة مع جدول themes + حذف غير المرتبط)، exists(), getThemeJson(). ⚠️ لا يُستدعى من أي مكان — المزامنة لا تحدث تلقائياً.
ThemeUploadService.php	⭐ أقوى ملف هندسياً (422 سطراً): تحميل ZIP بفحوص أمنية 9 طبقات (راجع 4.6)، cloneTheme(), delete(). ⚠️ اختباره فاشل حالياً.
RestaurantDashboardService.php	تجميع إحصائيات لوحة المطعم. ⚠️ تجمع total_amount (وهو subtotal فعلياً) → رقم مضلل.
الـ Observers, Policies, Events, Resources, Console, Providers: (تفاصيلها في الأقسام 2.6 و4.4 و1.4). ملاحظة إضافية: app/Filament/Resources/Concerns/HandlesImageUrl.php هو trait معرّف لكن لا يستخدمه أي مورد (كود ميت) وظيفته دمج حقول _url البديلة للصور في الحقل الأساسي. بينما النماذج (RestaurantForm, OfferForm, ThemeForm) تعرّف حقول _url (logo_url, image_url, qr_code_image_url...) — وما من شيء يعالجها فعلياً قبل الحفظ (إلا لو كان هناك mutateFormDataBeforeSave غير موجود) → أي أن "رابط الصورة" قد يُحفظ في عمود غير موجود أو يُهمل.

الـ Helpers:

الملف	الدور
app/helpers.php	دالة theme_setting($key, $default) العالمية — تقرأ الإعدادات مع كاش 24 ساعة وتحويل border_radius إلى px.
app/Helpers/ThemeHelper.php	getThemePath() (اختيار المجلد مع fallback)، view() (بناء اسم View)، getVar()، getCurrentRestaurant().
app/Helpers/media.php	دالة media_url($path) — تدعم الروابط المطلقة والمسارات النسبية على disk public.
7.2 config/ — الإعدادات
الملف	التخصيص الجوهري
app.php	⚠️ لا يوجد مفتاح domain مُعرّف لكن Restaurant::getFullSubdomainAttribute() يستدعي config('app.domain') → null عند الاستخدام.
broadcasting.php	default = env('BROADCAST_CONNECTION','null')؛ اتصال reverb مُعرّف كاملاً.
reverb.php	إعدادات خادم WebSocket.
cache.php	default = database (جدول cache) — Redis غير مُستخدم.
session.php	driver = database.
queue.php	driver = database (جدول jobs) — بلا Jobs معرّفة فعلياً.
filesystems.php	أقراص local (للملفات المؤقتة/الثيمات) وpublic (للصور/المنتجات/الشعارات).
fortify.php	يفعّل: registration, reset passwords, email verification, 2FA, passkeys.
auth.php, database.php, logging.php, mail.php, services.php	القياسي — mail = log، وservices بلا أي مفاتيح تكاملات طرف ثالث.
7.3 routes/ — المسارات
الملف	الدور
web.php	129 سطراً، منظم في 5 أقسام: عام (/, welcome)، محمي بـ auth (themes CRUD + KDS + API اللوحة + Driver)، تقييم AJAX، المسارات العامة بـ {slug} (المتجر والسلة والطلب والتتبع والفواتير + PWA)، ثم require settings.php. ⚠️ مزيج نموذجي: Route::redirect('/{slug}/kds', ...) + مسارات kitchen كلها داخل middleware(['auth']) — أي شاشة المطبخ تتطلب تسجيل دخول (وهذا صحيح أمنياً لكن يعني أن شاشة المطبخ لا يمكن تركها مفتوحة بلا جلسة).
settings.php	مسارات الحساب: /settings/profile (auth)، /settings/appearance و/settings/security (auth + verified، والأمان + password.confirm)، و.well-known/passkey-endpoints. ⚠️ لا يوجد مسار dashboard — وهو سبب فشل DashboardTest (رغم أن sidebar.blade.php يستدعي route('dashboard')! → خطأ UrlGenerationException في كل الصفحات التي تستخدم التخطيط).
channels.php	قناتا البث (راجع 1.4) — 🚨 قناة KDS تُرجع true دائماً.
console.php	أوامر Artisan المخصصة/الجدولة (قياسي).
7.4 resources/views/ — الواجهة
المسار	الدور
welcome.blade.php (77 KB)	صفحة الهبوط التسويقية (الوحيدة التي فيها ذكر واتساب كرقم/أيقونة).
dashboard.blade.php, layouts/app/* (header/sidebar)	تخطيط الـ starter kit — يعتمد على route('dashboard') غير الموجود → مصدر الـ 500 في اختبارات Settings وUrlGenerationException في عدة اختبارات.
components/theme-styles.blade.php	🔴 يُطبّق متغيرات الثيم عبر CSS vars + يستقبل postMessage للمعاينة الحية. يستخدم themeSetting (مفرد) الخاطئ.
components/theme-customizer.blade.php (125 سطراً)	لوحة تخصيص قديمة بـ JS/Axios إلى /restaurant/api/theme/settings — غير مستدعاة (كود ميت).
components/{app-logo, passkey-*, auth-*, placeholder-pattern, desktop-user-menu}	مكوّنات الـ starter kit.
filament/pages/restaurant-dashboard.blade.php (7 KB)	واجهة لوحة المطعم (بطاقات إحصائية + آخر الطلبات) بتنسيق Tailwind مخصّص بالكامل وRTL.
filament/pages/restaurant-theme-settings.blade.php (80 سطراً)	⭐ محرر الثيم + المعاينة الحية بالـ iframe وpostMessage.
filament/tables/qr-code.blade.php	عرض QR للطاولة.
livewire/auth/*	7 صفحات مصادقة (login, register, forgot/reset/confirm password, two-factor, verify-email).
livewire/settings/security.blade.php (17 KB)	صفحة الأمان (2FA + passkeys) — الأكبر في مجموعة الإعدادات.
restaurant/dashboard.blade.php (56 KB)	⚠️ لوحة قديمة ضخمة — يبدو أنها تخطيط بديل غير مستخدم (اللوحة الفعلية هي filament/pages/restaurant-dashboard).
restaurant/table-qr.blade.php	صفحة QR للطاولة.
flux/*	مكوّنات Flux مُخصّصة (أيقونات + navlist.group).
مجلد resources/views/themes/ — 3 ثيمات متطابقة البنية (17 ملفاً لكل ثيم):

لكل ثيم: theme.json + layout.blade.php + pages/{home, menu, product, offers, kds-board} + cart/{cart, checkout, success} + orders/{track, track-form, invoice} + reviews/{reviews, review-form} + driver/{dashboard, orders, tracking}.

أبرز الأحجام (دليل على التعقيد):

driver/tracking.blade.php — 80 KB (نظام تتبّع + خريطة + OSRM + watchPosition).
pages/kds-board.blade.php — 64 KB (شاشة مطبخ + Echo + سحب وإفلات حالات).
driver/tracking.blade.php مضافاً إليه CSP صريح (Content-Security-Policy) — الوحيد في المشروع.
layout.blade.php — ~36 KB، يحتوي CDN لـ Tailwind + QRCode.js + Axios + Google Fonts + @vite + كل الـ CSS/JS المشترك (السلة المصغّرة، Toast، PWA install، SW registration).
🔴 مشاكل الواجهة الجوهرية:

layout.blade.php يستدعي <script src="https://cdn.tailwindcss.com"> ومعه @vite(['resources/css/app.css', ...]) — أي Tailwind CDN يُحمّل من الإنترنت في كل طلب، ويتعارض مع Tailwind المُبنى بـ Vite. النتيجة: احتمال تعارض/تكرار ضخم + تبعية CDN إنتاجية غير مقبولة.
axios, qrcodejs, Leaflet من CDN خارجي — تبعيات شبكية إنتاجية.
حجم Blade المهول (36–80 KB) يعني HTML/CSS/JS مضمّن ضخم، وهذا يقصف أولوية الأداء (LCP) وتجربة الموبايل.
7.5 whatsapp-service/
غير موجود. لا مجلد، لا package.json منفصل، لا Node.js service. اعتماد الواجهة الأمامية على Node محصور في Vite/Echo/Pusher-JS فقط.

7.6 database/
34 Migration — 3 أساسية للـ framework (cache, jobs, users/sessions)، والبقية تشمل كل النطاق التجاري. ملاحظة معمارية: توجد هجرات مُصحّحة بصياغة دفاعية (if (! Schema::hasColumn(...))) — نمط ذكي لتجنّب الانهيار على قواعد بيانات قديمة، لكنه يعني أن المخطط الفعلي يعتمد على تاريخ التنفيذ لا على الملفات فقط.
15 Factory: Category, Coupon, Image, Invoice, Offer, Order, Order_item, Product, Restaurant, RestaurantTable, RestaurantThemeSetting, Review, Theme, User (+ DeliveryTracking seeder).
Seeders: DatabaseSeeder (ينشئ ثيم burger-theme، مطعم burger-house، مستخدم admin@example.com/customer@example.com/super@example.com بكلمة password، تصنيفات ومنتجات وعرض وكوبون WELCOME20 وطلب مسلّم مع فاتورة وتقييم)، وDeliveryTrackingSeeder.
7.7 tests/ — حالة الجودة (مُختبرة فعلياً اليوم)
تشغيل php artisan test --compact أعطى: 17 فاشلاً، 38 ناجحاً (120 assertion) في 21 ثانية.

الجذر الأول للأغلبية (UrlGenerationException): التخطيط layouts/app/sidebar.blade.php يستدعي route('dashboard')، والمسار غير موجود → يفشل أي صفحة تستخدم التخطيط. هذا يُسقط: AuthenticationTest, EmailVerificationTest (×2), RegistrationTest, DashboardTest (×2), DeliveryTrackingTest (×5), ProfileUpdateTest, SecurityTest, RestaurantAuthorizationTest.

مجموعات الاختبار الموجودة (20 ملفاً): Auth (6 ملفات)، Filament (3)، Settings (2)، Theme (2)، Dashboard، DeliveryTracking، RestaurantTableOrdering، Example (feature/unit).

اختبارات غائبة تماماً (فجوات خطيرة):

🔴 لا اختبار واحد لمسار cart → checkout → order → items → invoice (التدفق التجاري الأهم).
لا اختبار للخصومات (العروض/الكوبونات) وصحّة المبالغ.
لا اختبار لحماية قناة KDS أو صلاحيات KdsController.
لا اختبار لـ trackingLocation العام ومعالجة الخصوصية.
لا اختبار للمزامنة بين الثيمات (ThemeDiscoveryService::sync).
لا اختبار لرحلة السائق (accept → on_way → delivered).
7.8 ملفات الجذر الهيكلية
الملف	الدور
composer.json	يعرّف files autoload للـ Helpers الثلاثة (ThemeHelper, media, helpers)، وسكربتات: setup, dev (server+queue+vite عبر concurrently), lint, lint:check, ci:check, types:check (⚠️ يستدعي phpstan analyse وليس أمر types:check رغم تسميته)، test (lint + types + pest).
phpstan.neon, pint.json, phpunit.xml, vite.config.js	إعدادات الجودة والأدوات.
MANAGER_GUIDE_AR.md (1042 سطراً)	دليل إداري/تشغيلي شامل وغير تقني (رحلة العميل، الأدوار، أولويات P0/P1، مؤشرات الأداء، قوالب المهام). ممتاز كمرجع إداري.
PROJECT_GUIDE_AR.md (285 سطراً)	دليل تقني سريع (تشغيل، خريطة ملفات، نموذج بيانات، مشكلات معروفة). يحتاج تحديثاً لأن أرقامه عن الاختبارات (41/38/3) وحماية مسارات الثيمات تغيّرت.
getNameWithReadWriteType(), isUniqueConstraintError($e)), MANAGER_GUIDE_AR.md, origin), prepareBindings($bindings)	🔴 ملفات/مجلدات شاذة في جذر المشروع بأسماء مثل كود PHP مسرّب (getNameWithReadWriteType(), prepareBindings($bindings), origin), isUniqueConstraintError($e))) — تبدو نتائج لصق خاطئ لأوامر/مقتطفات. يجب التحقق من محتواها وحذفها أو عزلها؛ مذكورة في الوثيقة كـ "اسم ملف غريب origin)" وممتدة الآن لعدة ملفات.
8. الخلاصة التنفيذية للمستشار التقني
8.1 الصورة المختصرة (ما هو المشروع فعلاً)
منصة SaaS لمطاعم متعددة، عزل منطقي أحادي القاعدة عبر restaurant_id (لا Stancl ولا Subdomains ولا Redis)، بواجهة متجر Blade/Controllers للعميل، ولوحتَي إدارة Filament 5 (/restaurant للمطعم، /sham للمنصة)، مع نظام ثيمات قائم على الملفات + متغيرات metadata-driven ومحرر معاينة حية بالـ iframe، وتتبّع توصيل GPS/خرائط Leaflet/Socket واحدة لحظية للمطبخ. لا يوجد تكامل واتساب بأي شكل.

8.2 نقاط القوة الحقيقية
ThemeUploadService — تحصين أمني متعدد الطبقات متقدم (Zip Slip/Bomb/RCE/Whitelist).
Order_item Snapshot للأسعار + withTrashed — سلامة تاريخية للفواتير.
device_token + Cookie لتأمين تتبّع الطلب من نفس الجهاز.
canAccessPanel() سليم + getEloquentQuery() للتقييد في موارد المطعم + 3 Policies.
سلة بمفتاح cart_{restaurantId} — يحل تداخل المطاعم.
محرر ثيم metadata-driven (allowed_variables → حقول + CSS vars) + معاينة حية عبر postMessage.
8.3 المخاطر الحاسمة (مرتبة بالأولوية)
P0 — أمنية/مالية:

#	الخطر	الملف
1	قناة KDS تُرجع true دائماً → تسريب لحظي لطلبات أي مطعم	routes/channels.php:9
2	OrderController::store بلا Transaction → طلبات بلا عناصر/فاتورة	OrderController.php:233-285
3	used_count يُزاد قبل نجاح العملية → استهلاك كوبونات بلا طلب	OrderController.php:201,222
4	total_amount = subtotal → كل التقارير المالية "مبيعات اليوم/الإجمالي" خاطئة	OrderController.php:250 + RestaurantDashboardService
5	تعارض ضريبة 15% (شامل في checkout vs مضاف في store) → المبلغ المعروض ≠ المحفوظ	OrderController.php:94,227
6	ثغرة صلاحيات: أي مستخدم مصادق (حتى customer) يستدعي themes/*/activate|clone|destroy	routes/web.php:33-37 + ThemeController
7	KdsController@updateStatus بلا صلاحيات + قيم enum غير مطابقة (SQL error)	KdsController.php:30-48
8	trackingLocation عام بلا مصادقة → قراءة موقع السائق/العميل بمعرفة tracking_code	OrderController.php:35
9	coupon_code يُقبل من العميل دون مطابقة coupon.code → كود مزيف في الفاتورة	OrderController.php:221
10	appliesToProduct وoffer_product غير مستخدمين → عروض محدودة تُطبَّق على كل السلة	OrderController.php:193-203
P1 — معمارية/وظيفية:

#	الخطر
11	تناقض Order::driver() (موديل Driver ↔ جدول users) → خرائط السائق تعمل بأرقام خاطئة
12	theme-styles.blade.php يستخدم علاقة themeSetting غير موجودة → الإعدادات المحفوظة لا تُطبَّق
13	تعارض أسماء CSS vars (--primary في القالب ↔ --primary-color في theme.json)
14	نصف متغيرات الثيم بلا css_var → لا معاينة حية ولا تطبيق فعلي
15	Route::dashboard مفقود لكن التخطيط يستدعيه → 17 اختباراً فاشلاً
16	themeController::preview() يستدعي View غير موجود
17	ThemeDiscoveryService::sync() غير مستدعى أبداً
18	موارد Filament بلا Policies (Offers/Coupons/Invoices/Drivers/Tables)
19	User::$fillable فيه addreas بدل address
20	3 ثيمات متطابقة فعلياً (لا تنوّع حقيقي) + ملفات Blade 36–80 KB + Tailwind CDN إنتاجي
8.4 توصيات مرتبة للتنفيذ
إصلاح فوري (P0): تحصين قناة KDS بفحص restaurant_id؛ لفّ store() في DB::transaction؛ نقل incrementUsage بعد النجاح؛ توحيد الضريبة في دالة واحدة؛ تصحيح total_amount/final_amount؛ إغلاق مسارات الثيمات بـ Policy + فحص دور؛ حماية KdsController وtrackingLocation.
توحيد المصدر الواحد للثيمات: إزالة --primary لصالح css_var، حذف مسار theme_setting() المكرر لصالح RestaurantThemeSetting، إصلاح اسم العلاقة، وإضافة css_var لكل المتغيرات.
تصحيح Schema/Models: حذف أو استكمال Driver، توحيد Order::driver()، إصلاح addreas.
إغلاق فجوة الاختبارات: اختبار مفرد لـ cart→checkout→order→invoice بقيم رقمية معروفة، واختبارات صلاحيات (زائر/مطعم A/مطعم B/مدير) لكل مورد.
البناء للواجهة: حذف Tailwind CDN وfocal CDN لصالح بناء Vite، وتقسيم ملفات Blade العملاقة، وإكمال/إزالة Appearance والـ Stubs والـ cd الميتة (theme-customizer, HandlesImageUrl).
الواتساب (إن كان مطلوباً): لا شيء مبني اليوم؛ نقطة البدء الأقوى هي OrderCreated (يُضاف Listener يرسل عبر WhatsApp Cloud API أو Node/Baileys بـ Queue).
هل تريد أن أُحدّث PROJECT_GUIDE_AR.md ليطابق الواقع الفعلي الحالي (أرقام الاختبارات، حالة حماية مسارات الثيمات، وغياب الواتساب)، أو أن أبدأ فوراً في إصلاح أي مجموعة من مخاطر P0 المذكورة أعلاه؟