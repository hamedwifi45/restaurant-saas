import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Product } from '../../types';
import {
  Flame,
  Clock,
  Bike,
  Star,
  Search,
  Plus,
  Tag,
  Phone,
  MapPin,
  Calendar,
  Sparkles,
  QrCode,
} from 'lucide-react';
import { ProductDetailModal } from './ProductDetailModal';

interface StorefrontHomeProps {
  onOpenCart: () => void;
  onOpenTableQRModal: () => void;
  onNavigateToTracking: () => void;
}

export const StorefrontHome: React.FC<StorefrontHomeProps> = ({
  onOpenCart,
  onOpenTableQRModal,
  onNavigateToTracking,
}) => {
  const {
    currentRestaurant,
    categories,
    products,
    offers,
    reviews,
    addToCart,
    scannedTable,
    applyCoupon,
  } = useApp();

  const [selectedCategoryId, setSelectedCategoryId] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [activeModalProduct, setActiveModalProduct] = useState<Product | null>(null);
  const [couponCopied, setCouponCopied] = useState<string | null>(null);

  const restaurantCategories = categories.filter(
    (c) => c.restaurant_id === currentRestaurant.id && c.is_active
  );

  const restaurantProducts = products.filter(
    (p) => p.restaurant_id === currentRestaurant.id
  );

  const restaurantOffers = offers.filter(
    (o) => o.restaurant_id === currentRestaurant.id && o.is_active
  );

  const restaurantReviews = reviews.filter(
    (r) => r.restaurant_id === currentRestaurant.id
  );

  const filteredProducts = restaurantProducts.filter((p) => {
    const matchesCategory =
      selectedCategoryId === 'all' || p.category_id === selectedCategoryId;
    const matchesSearch =
      searchQuery.trim() === '' ||
      p.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.description_ar.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const themeSettings = currentRestaurant.theme_settings;

  return (
    <div className="min-h-screen pb-20">
      {/* Hero Banner Section */}
      {themeSettings.show_hero_banner && (
        <section className="relative overflow-hidden bg-gray-900 text-white py-12 md:py-20 px-4 sm:px-6 lg:px-8">
          <div className="absolute inset-0 z-0 opacity-30">
            <img
              src={currentRestaurant.banner}
              alt={currentRestaurant.name_ar}
              className="w-full h-full object-cover filter blur-xs scale-105"
            />
          </div>
          <div className="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-900/80 to-transparent z-1"></div>

          <div className="relative z-10 max-w-5xl mx-auto text-center space-y-5">
            {scannedTable && (
              <div className="inline-flex items-center gap-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md">
                <QrCode className="w-4 h-4" />
                أنت تطلب من داخل المطعم: {scannedTable.table_number} ({scannedTable.seats} مقاعد)
              </div>
            )}

            <div className="inline-flex items-center gap-1.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 px-3.5 py-1 rounded-full text-xs font-semibold backdrop-blur-md">
              <Sparkles className="w-3.5 h-3.5" />
              أفضل جودة ولحوم طازجة يومياً
            </div>

            <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
              {themeSettings.hero_title || currentRestaurant.name_ar}
            </h1>

            <p className="text-base sm:text-lg text-gray-300 max-w-2xl mx-auto leading-relaxed">
              {themeSettings.hero_subtitle || currentRestaurant.description_ar}
            </p>

            {/* Quick Badges */}
            <div className="pt-3 flex flex-wrap items-center justify-center gap-3 sm:gap-6 text-xs text-gray-200">
              <div className="flex items-center gap-1.5 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full">
                <Bike className="w-4 h-4 text-amber-400" />
                <span>توصيل: {currentRestaurant.delivery_fee.toLocaleString()} ل.س</span>
              </div>
              <div className="flex items-center gap-1.5 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full">
                <Clock className="w-4 h-4 text-amber-400" />
                <span>المدة التقريبية: {currentRestaurant.estimated_delivery_time} دقيقة</span>
              </div>
              <div className="flex items-center gap-1.5 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full">
                <Calendar className="w-4 h-4 text-amber-400" />
                <span>ساعات العمل: {currentRestaurant.operating_hours.open} - {currentRestaurant.operating_hours.close}</span>
              </div>
            </div>

            {/* Quick Action Buttons */}
            <div className="pt-2 flex flex-wrap items-center justify-center gap-3">
              <a
                href="#menu-section"
                className="bg-amber-600 hover:bg-amber-500 text-white font-bold px-6 py-3 rounded-xl shadow-lg transition-transform active:scale-95 text-sm"
              >
                تصفح قائمة الطعام
              </a>
              <button
                onClick={onNavigateToTracking}
                className="bg-white/10 hover:bg-white/20 text-white border border-white/20 font-medium px-5 py-3 rounded-xl transition-all text-sm cursor-pointer"
              >
                تتبع حالة طلبك
              </button>
            </div>
          </div>
        </section>
      )}

      {/* Main Content Area */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-12">
        {/* Special Offers Section */}
        {restaurantOffers.length > 0 && (
          <section className="space-y-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Tag className="w-5 h-5 text-amber-600" />
                <h2 className="text-xl font-black text-gray-900">عروض وتخفيضات خاصة</h2>
              </div>
              <span className="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                لفترة محدودة
              </span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {restaurantOffers.map((offer) => (
                <div
                  key={offer.id}
                  className="bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent border border-amber-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-center gap-4 hover:border-amber-400 transition-all shadow-xs"
                >
                  <img
                    src={offer.image}
                    alt={offer.title_ar}
                    className="w-full sm:w-28 h-28 object-cover rounded-xl shadow-xs shrink-0"
                  />
                  <div className="space-y-1.5 grow text-center sm:text-right">
                    <div className="inline-block bg-amber-600 text-white text-xs font-black px-2 py-0.5 rounded-md">
                      خصم {offer.discount_percent}%
                    </div>
                    <h3 className="font-bold text-gray-900 text-sm">{offer.title_ar}</h3>
                    <p className="text-xs text-gray-600 line-clamp-2">{offer.description_ar}</p>
                    <div className="pt-1 flex items-center justify-between">
                      <span className="text-[11px] text-gray-400">ساري حتى: {offer.valid_until}</span>
                      <a
                        href="#menu-section"
                        className="text-xs font-bold text-amber-700 hover:text-amber-800 underline"
                      >
                        اطلب الآن
                      </a>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Menu Section */}
        <section id="menu-section" className="space-y-6 scroll-mt-24">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 pb-4">
            <div>
              <h2 className="text-2xl font-black text-gray-900">قائمة الطعام</h2>
              <p className="text-xs text-gray-500">اختر من تشكيلتنا المميزة من الوجبات والسندويشات والمقبلات</p>
            </div>

            {/* Search Input */}
            <div className="relative w-full md:w-72">
              <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="ابحث عن وجبة أو برجر..."
                className="w-full pr-9 pl-4 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          {/* Categories Tab Navigation */}
          <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <button
              onClick={() => setSelectedCategoryId('all')}
              className={`px-4 py-2 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer ${
                selectedCategoryId === 'all'
                  ? 'bg-amber-600 text-white shadow-xs'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
              }`}
            >
              الكل ({restaurantProducts.length})
            </button>
            {restaurantCategories.map((cat) => {
              const count = restaurantProducts.filter((p) => p.category_id === cat.id).length;
              const isActive = selectedCategoryId === cat.id;
              return (
                <button
                  key={cat.id}
                  onClick={() => setSelectedCategoryId(cat.id)}
                  className={`px-4 py-2 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer flex items-center gap-1.5 ${
                    isActive
                      ? 'bg-amber-600 text-white shadow-xs'
                      : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
                  }`}
                >
                  <span>{cat.name_ar}</span>
                  <span
                    className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                      isActive ? 'bg-amber-700 text-white' : 'bg-gray-100 text-gray-500'
                    }`}
                  >
                    {count}
                  </span>
                </button>
              );
            })}
          </div>

          {/* Products Grid */}
          {filteredProducts.length === 0 ? (
            <div className="bg-white rounded-2xl border border-gray-200 p-12 text-center space-y-3">
              <Search className="w-10 h-10 text-gray-300 mx-auto" />
              <div className="font-bold text-gray-700">لا توجد نتائج مطابقة لبحثك</div>
              <p className="text-xs text-gray-500">جرب البحث بكلمات أخرى أو اختر تصنيفاً آخر.</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
              {filteredProducts.map((product) => {
                const hasVariants = product.variants && product.variants.length > 0;
                return (
                  <div
                    key={product.id}
                    className="group bg-white rounded-2xl border border-gray-200/90 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col"
                  >
                    {/* Product Image */}
                    <div className="relative h-48 w-full bg-gray-100 overflow-hidden cursor-pointer" onClick={() => setActiveModalProduct(product)}>
                      <img
                        src={product.image}
                        alt={product.name_ar}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                      {!product.is_available && (
                        <div className="absolute inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center text-white text-xs font-bold">
                          غير متوفر حالياً
                        </div>
                      )}
                      {product.calories && (
                        <span className="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-amber-300 text-[11px] px-2 py-0.5 rounded-full flex items-center gap-1 font-medium">
                          <Flame className="w-3 h-3" /> {product.calories} كالوري
                        </span>
                      )}
                      {product.rating && (
                        <span className="absolute bottom-2.5 right-2.5 bg-white/90 backdrop-blur-md text-gray-800 text-[11px] px-2 py-0.5 rounded-full flex items-center gap-1 font-bold shadow-xs">
                          <Star className="w-3 h-3 fill-amber-400 text-amber-400" /> {product.rating}
                        </span>
                      )}
                    </div>

                    {/* Product Info */}
                    <div className="p-4 flex flex-col justify-between grow space-y-3">
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <h3
                            onClick={() => setActiveModalProduct(product)}
                            className="font-bold text-gray-900 text-base group-hover:text-amber-600 transition-colors cursor-pointer"
                          >
                            {product.name_ar}
                          </h3>
                        </div>
                        <p className="text-xs text-gray-500 mt-1 line-clamp-2 leading-relaxed">
                          {product.description_ar || product.description}
                        </p>
                      </div>

                      <div className="pt-2 border-t border-gray-100 flex items-center justify-between">
                        <div>
                          <div className="text-base font-black text-amber-600">
                            {product.price.toLocaleString()}{' '}
                            <span className="text-[11px] font-normal text-gray-500">ل.س</span>
                          </div>
                          {hasVariants && (
                            <span className="text-[10px] text-gray-400 block font-medium">
                              يتوفر خيارات أحجام
                            </span>
                          )}
                        </div>

                        <button
                          onClick={() => {
                            if (hasVariants) {
                              setActiveModalProduct(product);
                            } else {
                              addToCart(product, 1);
                            }
                          }}
                          disabled={!product.is_available}
                          className="bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1 transition-all cursor-pointer border border-amber-200 hover:border-amber-600 shadow-2xs"
                        >
                          <Plus className="w-3.5 h-3.5" />
                          <span>{hasVariants ? 'اختر الوجبة' : 'إضافة'}</span>
                        </button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </section>

        {/* Customer Reviews Section */}
        {restaurantReviews.length > 0 && (
          <section className="bg-gray-50 rounded-3xl p-6 sm:p-8 border border-gray-200/80 space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <h3 className="text-xl font-black text-gray-900">آراء وتقييمات العملاء</h3>
                <p className="text-xs text-gray-500">تجارب حقيقية لزبائننا في {currentRestaurant.name_ar}</p>
              </div>
              <div className="flex items-center gap-1 text-amber-500 font-extrabold text-sm">
                <Star className="w-5 h-5 fill-amber-400" />
                <span>4.9 / 5</span>
                <span className="text-gray-400 font-normal text-xs">(أكثر من 300 تقييم)</span>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {restaurantReviews.map((rev) => (
                <div
                  key={rev.id}
                  className="bg-white rounded-2xl p-4 border border-gray-100 shadow-2xs space-y-2 flex flex-col justify-between"
                >
                  <div className="space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-xs text-gray-800">{rev.customer_name}</span>
                      <div className="flex items-center text-amber-400">
                        {Array.from({ length: rev.rating }).map((_, i) => (
                          <Star key={i} className="w-3.5 h-3.5 fill-amber-400" />
                        ))}
                      </div>
                    </div>
                    <p className="text-xs text-gray-600 leading-relaxed italic">
                      "{rev.comment}"
                    </p>
                  </div>
                  <div className="text-[10px] text-gray-400 pt-2 border-t border-gray-50">
                    {rev.date}
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Restaurant Contact & Dine-in Table banner */}
        <section className="bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-3xl p-6 sm:p-8 shadow-md">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="space-y-2">
              <h4 className="font-extrabold text-base text-amber-400">زورونا في المطعم</h4>
              <p className="text-xs text-gray-300 leading-relaxed">
                استمتع بتجربة طعام داخلية رائعة في صالتنا المكيفة مع خدمة وضيافة دمشقية فاخرة.
              </p>
              <button
                onClick={onOpenTableQRModal}
                className="mt-2 inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-bold px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
              >
                <QrCode className="w-3.5 h-3.5" /> مسح رمز QR الطاولة للطلب المباشر
              </button>
            </div>

            <div className="space-y-2 border-t md:border-t-0 md:border-r border-gray-700/60 pt-4 md:pt-0 md:pr-6">
              <h4 className="font-extrabold text-base text-amber-400">العنوان وأوقات الدوام</h4>
              <div className="text-xs text-gray-300 space-y-1.5">
                <div className="flex items-center gap-2">
                  <MapPin className="w-3.5 h-3.5 text-amber-400 shrink-0" />
                  <span>{currentRestaurant.address} ({currentRestaurant.city})</span>
                </div>
                <div className="flex items-center gap-2">
                  <Clock className="w-3.5 h-3.5 text-amber-400 shrink-0" />
                  <span>{currentRestaurant.operating_hours.open} إلى {currentRestaurant.operating_hours.close}</span>
                </div>
              </div>
            </div>

            <div className="space-y-2 border-t md:border-t-0 md:border-r border-gray-700/60 pt-4 md:pt-0 md:pr-6">
              <h4 className="font-extrabold text-base text-amber-400">خدمة التوصيل والاستفسار</h4>
              <p className="text-xs text-gray-300">
                للطلبات الكبيرة والحفلات أو للاستفسار عن أي صنف اتصل بنا مباشرة:
              </p>
              <div className="flex items-center gap-2 text-sm font-bold text-amber-300">
                <Phone className="w-4 h-4" />
                <span dir="ltr">{currentRestaurant.phone}</span>
              </div>
            </div>
          </div>
        </section>
      </div>

      {/* Product Detail Modal */}
      {activeModalProduct && (
        <ProductDetailModal
          product={activeModalProduct}
          onClose={() => setActiveModalProduct(null)}
          onAddToCart={addToCart}
        />
      )}
    </div>
  );
};
