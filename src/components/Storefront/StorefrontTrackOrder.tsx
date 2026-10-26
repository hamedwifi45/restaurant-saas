import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Order, OrderStatus } from '../../types';
import {
  Search,
  Package,
  ChefHat,
  CheckCircle2,
  Bike,
  Clock,
  Phone,
  FileText,
  Star,
  MapPin,
  Calendar,
  AlertCircle,
} from 'lucide-react';

interface StorefrontTrackOrderProps {
  initialCode?: string;
  onOpenInvoice: (order: Order) => void;
  onBackToMenu: () => void;
}

export const StorefrontTrackOrder: React.FC<StorefrontTrackOrderProps> = ({
  initialCode = '',
  onOpenInvoice,
  onBackToMenu,
}) => {
  const { orders, currentRestaurant, rateDriver } = useApp();
  const [searchCode, setSearchCode] = useState(initialCode);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(() => {
    if (initialCode) {
      return orders.find((o) => o.tracking_code.toLowerCase() === initialCode.toLowerCase()) || null;
    }
    // Default to the most recent order for current restaurant
    const restOrders = orders.filter((o) => o.restaurant_id === currentRestaurant.id);
    return restOrders.length > 0 ? restOrders[0] : null;
  });

  const [ratingVal, setRatingVal] = useState(5);
  const [ratingComment, setRatingComment] = useState('');
  const [ratedSuccessfully, setRatedSuccessfully] = useState(false);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchCode.trim()) return;
    const found = orders.find(
      (o) => o.tracking_code.toLowerCase() === searchCode.trim().toLowerCase()
    );
    if (found) {
      setSelectedOrder(found);
    } else {
      alert(`لم يتم العثور على طلب برمز التتبع: ${searchCode}`);
    }
  };

  const handleRateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedOrder) return;
    rateDriver(selectedOrder.id, ratingVal, ratingComment);
    setRatedSuccessfully(true);
  };

  const getStepStatus = (step: OrderStatus, currentStatus: OrderStatus) => {
    const sequence: OrderStatus[] = ['pending', 'preparing', 'ready', 'on_way', 'delivered'];
    const currentIndex = sequence.indexOf(currentStatus);
    const stepIndex = sequence.indexOf(step);

    if (currentStatus === 'cancelled') return 'cancelled';
    if (stepIndex < currentIndex) return 'completed';
    if (stepIndex === currentIndex) return 'active';
    return 'upcoming';
  };

  const trackingSteps: { status: OrderStatus; label: string; icon: React.ReactNode; desc: string }[] = [
    { status: 'pending', label: 'تم استلام الطلب', icon: <Package className="w-5 h-5" />, desc: 'تم إرسال الطلب بنجاح وهو بانتظار التأكيد' },
    { status: 'preparing', label: 'قيد التحضير والطهي', icon: <ChefHat className="w-5 h-5" />, desc: 'يقوم طاقم المطبخ بإعداد وجبتك الطازجة' },
    { status: 'ready', label: 'الطلب جاهز', icon: <CheckCircle2 className="w-5 h-5" />, desc: 'تم تغليف الوجبة وهي بانتظار الانطلاق' },
    { status: 'on_way', label: 'خرج مع المندوب', icon: <Bike className="w-5 h-5" />, desc: 'المندوب في طريقه إلى عنوانك حالياً' },
    { status: 'delivered', label: 'تم التسليم بنجاح', icon: <CheckCircle2 className="w-5 h-5" />, desc: 'بالهناء والشفاء! نتمنى لك وجبة شهية' },
  ];

  return (
    <div className="max-w-4xl mx-auto px-4 py-8 space-y-6">
      {/* Search Header */}
      <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs">
        <h2 className="text-xl font-black text-gray-900 mb-2">تتبع حالة طلبك المباشرة</h2>
        <p className="text-xs text-gray-500 mb-4">
          أدخل رمز تتبع الطلب الخاص بك (مثل: BSH-7821) لمتابعة تقدم التحضير والتوصيل خطوة بخطوة.
        </p>

        <form onSubmit={handleSearch} className="flex gap-2">
          <div className="relative grow">
            <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchCode}
              onChange={(e) => setSearchCode(e.target.value)}
              placeholder="أدخل رمز التتبع (مثال: BSH-7821)..."
              className="w-full pr-9 pl-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono uppercase focus:ring-2 focus:ring-amber-500"
            />
          </div>
          <button
            type="submit"
            className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs cursor-pointer transition-colors"
          >
            بحث
          </button>
        </form>
      </div>

      {selectedOrder ? (
        <div className="space-y-6 animate-in fade-in duration-300">
          {/* Order Summary Top Card */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <span className="text-xs text-gray-400 font-medium">رمز التتبع:</span>
                <span className="font-mono font-black text-amber-600 text-lg bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">
                  {selectedOrder.tracking_code}
                </span>
                <span
                  className={`text-xs px-2.5 py-0.5 rounded-full font-bold ${
                    selectedOrder.status === 'delivered'
                      ? 'bg-emerald-100 text-emerald-800'
                      : selectedOrder.status === 'on_way'
                      ? 'bg-blue-100 text-blue-800'
                      : selectedOrder.status === 'ready'
                      ? 'bg-purple-100 text-purple-800'
                      : 'bg-amber-100 text-amber-800'
                  }`}
                >
                  {selectedOrder.status === 'pending'
                    ? 'قيد المراجعة'
                    : selectedOrder.status === 'preparing'
                    ? 'قيد التحضير'
                    : selectedOrder.status === 'ready'
                    ? 'جاهز للتسليم'
                    : selectedOrder.status === 'on_way'
                    ? 'في الطريق مع المندوب'
                    : selectedOrder.status === 'delivered'
                    ? 'تم التسليم'
                    : 'ملغى'}
                </span>
              </div>
              <div className="text-xs text-gray-500 flex items-center gap-4">
                <span className="flex items-center gap-1">
                  <Calendar className="w-3.5 h-3.5 text-gray-400" />
                  {new Date(selectedOrder.created_at).toLocaleTimeString('ar-SY', {
                    hour: '2-digit',
                    minute: '2-digit',
                  })}
                </span>
                <span>•</span>
                <span>النوع: {selectedOrder.delivery_type === 'delivery' ? 'توصيل منزلي' : selectedOrder.delivery_type === 'dine_in' ? `طاولة محلي (${selectedOrder.table_number})` : 'استلام سفري'}</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={() => onOpenInvoice(selectedOrder)}
                className="bg-gray-100 hover:bg-gray-200 text-gray-800 px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer border border-gray-200"
              >
                <FileText className="w-4 h-4 text-gray-600" />
                <span>عرض الفاتورة الرسمية</span>
              </button>
            </div>
          </div>

          {/* Timeline Pipeline */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 shadow-xs space-y-6">
            <h3 className="font-bold text-sm text-gray-900">مراحل الطلب المباشرة</h3>

            <div className="relative">
              {/* Connecting Line */}
              <div className="absolute right-4 top-4 bottom-4 w-0.5 bg-gray-200 -z-0"></div>

              <div className="space-y-6 relative z-10">
                {trackingSteps.map((step, idx) => {
                  const state = getStepStatus(step.status, selectedOrder.status);
                  return (
                    <div key={idx} className="flex items-start gap-4">
                      <div
                        className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 border-2 transition-all ${
                          state === 'completed'
                            ? 'bg-emerald-600 border-emerald-600 text-white'
                            : state === 'active'
                            ? 'bg-amber-600 border-amber-600 text-white ring-4 ring-amber-100 animate-pulse'
                            : 'bg-white border-gray-300 text-gray-400'
                        }`}
                      >
                        {step.icon}
                      </div>

                      <div className="space-y-0.5 pt-1">
                        <div
                          className={`text-sm font-bold ${
                            state === 'active'
                              ? 'text-amber-600'
                              : state === 'completed'
                              ? 'text-gray-900'
                              : 'text-gray-400'
                          }`}
                        >
                          {step.label}
                        </div>
                        <p className="text-xs text-gray-500">{step.desc}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Live Delivery Map & Driver Card (if on_way or delivered with driver) */}
          {selectedOrder.delivery_type === 'delivery' && selectedOrder.driver_name && (
            <div className="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 shadow-xs space-y-4">
              <h3 className="font-bold text-sm text-gray-900 flex items-center gap-2">
                <Bike className="w-4 h-4 text-amber-600" />
                <span>بيانات المندوب والتتبع الحي</span>
              </h3>

              {/* Driver info card */}
              <div className="bg-gray-50 rounded-xl p-4 border border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-base shadow-xs">
                    {selectedOrder.driver_name.charAt(0)}
                  </div>
                  <div>
                    <div className="font-bold text-sm text-gray-900">{selectedOrder.driver_name}</div>
                    <div className="text-xs text-gray-500">مندوب التوصيل المعتمد</div>
                  </div>
                </div>

                {selectedOrder.driver_phone && (
                  <a
                    href={`tel:${selectedOrder.driver_phone}`}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors shadow-xs"
                  >
                    <Phone className="w-3.5 h-3.5" />
                    <span>اتصال بالمندوب: {selectedOrder.driver_phone}</span>
                  </a>
                )}
              </div>

              {/* Simulated Map Visual */}
              <div className="relative h-48 w-full rounded-xl overflow-hidden border border-gray-200 bg-slate-900 flex flex-col items-center justify-center text-center p-4">
                {/* SVG Map Grid Background */}
                <div className="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>

                {/* Animated delivery route simulation */}
                <div className="relative z-10 flex items-center gap-8 text-white text-xs">
                  <div className="flex flex-col items-center gap-1">
                    <div className="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center shadow-md">
                      <ChefHat className="w-4 h-4 text-gray-900" />
                    </div>
                    <span className="font-bold">{currentRestaurant.name_ar}</span>
                  </div>

                  <div className="relative flex items-center">
                    <div className="w-32 h-1 bg-amber-500/40 rounded-full"></div>
                    <div
                      className={`absolute w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center shadow-lg ${
                        selectedOrder.status === 'on_way'
                          ? 'animate-bounce translate-x-12'
                          : selectedOrder.status === 'delivered'
                          ? 'translate-x-28 bg-emerald-500'
                          : 'translate-x-0'
                      }`}
                    >
                      <Bike className="w-3.5 h-3.5 text-white" />
                    </div>
                  </div>

                  <div className="flex flex-col items-center gap-1">
                    <div className="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center shadow-md">
                      <MapPin className="w-4 h-4 text-white" />
                    </div>
                    <span className="font-bold">عنوانك</span>
                  </div>
                </div>

                <div className="relative z-10 mt-4 text-[11px] text-gray-300 bg-black/60 px-3 py-1 rounded-full backdrop-blur-md">
                  {selectedOrder.status === 'on_way'
                    ? 'المندوب يتحرك بالدراجة على الخريطة باتجاهك'
                    : selectedOrder.status === 'delivered'
                    ? 'وصل المندوب وتم تسليم الطلب'
                    : 'بانتظار انطلاق المندوب من المطعم'}
                </div>
              </div>
            </div>
          )}

          {/* Rate Driver Section if Delivered */}
          {selectedOrder.status === 'delivered' && (
            <div className="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 shadow-xs space-y-3">
              <h3 className="font-bold text-sm text-gray-900 flex items-center gap-1.5">
                <Star className="w-4 h-4 text-amber-500 fill-amber-500" />
                <span>تقييم تجربة التوصيل والمندوب</span>
              </h3>

              {ratedSuccessfully || selectedOrder.driver_rating ? (
                <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-xs font-medium space-y-1">
                  <div className="font-bold flex items-center gap-1">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600" /> شكراً لك! تم استلام تقييمك بنجاح.
                  </div>
                  <div>التقييم المسجل: {selectedOrder.driver_rating || ratingVal} نجوم</div>
                  {selectedOrder.driver_comment && (
                    <div className="text-gray-600 italic">"{selectedOrder.driver_comment}"</div>
                  )}
                </div>
              ) : (
                <form onSubmit={handleRateSubmit} className="space-y-3">
                  <p className="text-xs text-gray-500">
                    كيف كانت سرعة التوصيل وتعامل المندوب معك؟
                  </p>
                  <div className="flex items-center gap-2">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <button
                        type="button"
                        key={star}
                        onClick={() => setRatingVal(star)}
                        className="cursor-pointer p-1"
                      >
                        <Star
                          className={`w-6 h-6 transition-all ${
                            star <= ratingVal
                              ? 'text-amber-400 fill-amber-400'
                              : 'text-gray-300'
                          }`}
                        />
                      </button>
                    ))}
                    <span className="text-xs font-bold text-gray-700 mr-2">
                      ({ratingVal} من 5 نجوم)
                    </span>
                  </div>

                  <textarea
                    rows={2}
                    value={ratingComment}
                    onChange={(e) => setRatingComment(e.target.value)}
                    placeholder="أضف تعليقاً حول تجربة التوصيل (اختياري)..."
                    className="w-full text-xs p-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-amber-500"
                  ></textarea>

                  <button
                    type="submit"
                    className="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2 rounded-xl cursor-pointer"
                  >
                    إرسال التقييم
                  </button>
                </form>
              )}
            </div>
          )}

          {/* Ordered Items Breakdown */}
          <div className="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 shadow-xs space-y-3">
            <h3 className="font-bold text-sm text-gray-900">تفاصيل أصناف الطلب</h3>
            <div className="divide-y divide-gray-100">
              {selectedOrder.items.map((item, i) => (
                <div key={i} className="py-2.5 flex items-center justify-between text-xs">
                  <div>
                    <span className="font-bold text-gray-800">{item.quantity}x </span>
                    <span className="text-gray-900 font-medium">{item.product_name_ar}</span>
                    {item.variant_name && (
                      <span className="text-[11px] text-amber-600 mr-1.5 font-semibold">
                        ({item.variant_name})
                      </span>
                    )}
                    {item.notes && (
                      <span className="text-[10px] text-gray-400 block">ملاحظة: {item.notes}</span>
                    )}
                  </div>
                  <span className="font-bold text-gray-800">
                    {item.total_price.toLocaleString()} ل.س
                  </span>
                </div>
              ))}
            </div>

            <div className="pt-3 border-t border-gray-200 space-y-1 text-xs">
              <div className="flex justify-between text-gray-500">
                <span>المجموع الفرعي:</span>
                <span>{selectedOrder.subtotal.toLocaleString()} ل.س</span>
              </div>
              {selectedOrder.delivery_fee > 0 && (
                <div className="flex justify-between text-gray-500">
                  <span>رسوم التوصيل:</span>
                  <span>{selectedOrder.delivery_fee.toLocaleString()} ل.س</span>
                </div>
              )}
              {selectedOrder.discount > 0 && (
                <div className="flex justify-between text-emerald-600 font-bold">
                  <span>خصم الكوبون ({selectedOrder.coupon_code}):</span>
                  <span>-{selectedOrder.discount.toLocaleString()} ل.س</span>
                </div>
              )}
              <div className="flex justify-between text-sm font-black text-gray-900 pt-1 border-t border-gray-100">
                <span>الإجمالي النهائي:</span>
                <span className="text-amber-600">{selectedOrder.total.toLocaleString()} ل.س</span>
              </div>
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-white rounded-2xl border border-gray-200 p-12 text-center space-y-3">
          <AlertCircle className="w-10 h-10 text-gray-300 mx-auto" />
          <p className="font-bold text-gray-700">لا يوجد طلب محدد حالياً</p>
          <p className="text-xs text-gray-500">
            أدخل رمز التتبع في المربع أعلاه لعرض التفاصيل المباشرة.
          </p>
          <button
            onClick={onBackToMenu}
            className="mt-2 bg-amber-600 text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-amber-700 cursor-pointer"
          >
            الرجوع إلى القائمة
          </button>
        </div>
      )}
    </div>
  );
};
