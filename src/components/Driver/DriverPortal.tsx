import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Order, Driver, OrderStatus } from '../../types';
import {
  Bike,
  Phone,
  MapPin,
  CheckCircle,
  Clock,
  Navigation,
  CheckCircle2,
  DollarSign,
  Star,
  ArrowRight,
} from 'lucide-react';
import confetti from 'canvas-confetti';

export const DriverPortal: React.FC = () => {
  const { currentRestaurant, orders, drivers, updateOrderStatus, updateDriverLocation } = useApp();

  const restDrivers = drivers.filter((d) => d.restaurant_id === currentRestaurant.id);
  const [activeDriverId, setActiveDriverId] = useState<string>(restDrivers[0]?.id || '');

  const currentDriver = restDrivers.find((d) => d.id === activeDriverId) || restDrivers[0];

  // Orders assigned to this driver or available for pickup
  const assignedOrders = orders.filter(
    (o) =>
      o.restaurant_id === currentRestaurant.id &&
      o.delivery_type === 'delivery' &&
      o.driver_id === currentDriver?.id &&
      o.status !== 'delivered' &&
      o.status !== 'cancelled'
  );

  const deliveredOrders = orders.filter(
    (o) =>
      o.restaurant_id === currentRestaurant.id &&
      o.delivery_type === 'delivery' &&
      o.driver_id === currentDriver?.id &&
      o.status === 'delivered'
  );

  const availablePickupOrders = orders.filter(
    (o) =>
      o.restaurant_id === currentRestaurant.id &&
      o.delivery_type === 'delivery' &&
      (!o.driver_id || o.driver_id === '') &&
      (o.status === 'ready' || o.status === 'preparing')
  );

  const handleUpdateStatus = (orderId: string, newStatus: OrderStatus) => {
    updateOrderStatus(orderId, newStatus);
    if (newStatus === 'delivered') {
      try {
        confetti({ particleCount: 60, spread: 60, origin: { y: 0.6 } });
      } catch (e) {
        // ignore
      }
    }
  };

  const handleSimulateGPSMove = () => {
    if (!currentDriver) return;
    const newLat = currentDriver.current_location.lat + (Math.random() - 0.5) * 0.005;
    const newLng = currentDriver.current_location.lng + (Math.random() - 0.5) * 0.005;
    updateDriverLocation(currentDriver.id, { lat: newLat, lng: newLng });
    alert('تم تحديث إحداثيات GPS للمندوب وإرسالها لتتبع العميل المباشر!');
  };

  return (
    <div className="max-w-3xl mx-auto px-4 py-6 space-y-6 animate-in fade-in duration-200">
      {/* Driver Header Card */}
      <div className="bg-gray-900 text-white p-5 sm:p-6 rounded-2xl border border-gray-800 shadow-lg space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-amber-500 text-gray-950 flex items-center justify-center font-black text-xl shadow-md">
              <Bike className="w-6 h-6" />
            </div>
            <div>
              <div className="text-xs text-amber-400 font-semibold">بوابة المندوب الذكية</div>
              <h1 className="text-lg font-black text-white">{currentDriver?.name || 'مندوب التوصيل'}</h1>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-xs text-gray-400">تغيير المندوب:</span>
            <select
              value={activeDriverId}
              onChange={(e) => setActiveDriverId(e.target.value)}
              className="bg-gray-800 text-amber-400 font-bold border border-gray-700 rounded-lg px-2.5 py-1 text-xs"
            >
              {restDrivers.map((d) => (
                <option key={d.id} value={d.id}>
                  {d.name} ({d.vehicle_type})
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-3 gap-2.5 pt-3 border-t border-gray-800 text-center text-xs">
          <div className="bg-gray-800/80 p-2.5 rounded-xl">
            <span className="text-gray-400 text-[10px] block">الطلبات الجارية</span>
            <span className="text-base font-black text-amber-400">{assignedOrders.length}</span>
          </div>
          <div className="bg-gray-800/80 p-2.5 rounded-xl">
            <span className="text-gray-400 text-[10px] block">طلبات تم تسليمها</span>
            <span className="text-base font-black text-emerald-400">{deliveredOrders.length}</span>
          </div>
          <div className="bg-gray-800/80 p-2.5 rounded-xl">
            <span className="text-gray-400 text-[10px] block">التقييم العام</span>
            <span className="text-base font-black text-white flex items-center justify-center gap-1">
              <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
              {currentDriver?.rating || 4.9}
            </span>
          </div>
        </div>

        <div className="pt-1 flex items-center justify-between text-xs text-gray-400">
          <span>المركبة: {currentDriver?.vehicle_type} ({currentDriver?.plate_number})</span>
          <button
            onClick={handleSimulateGPSMove}
            className="text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1 cursor-pointer"
          >
            <Navigation className="w-3.5 h-3.5" /> محاكاة حركة GPS
          </button>
        </div>
      </div>

      {/* Active Deliveries List */}
      <div className="space-y-4">
        <h2 className="font-extrabold text-base text-gray-900 flex items-center gap-2">
          <span>طلباتك النشطة للتوصيل ({assignedOrders.length})</span>
        </h2>

        {assignedOrders.length === 0 ? (
          <div className="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-400 space-y-2">
            <CheckCircle2 className="w-10 h-10 text-emerald-500 mx-auto" />
            <p className="font-bold text-gray-800">لا توجد طلبات توصيل جارية حالياً</p>
            <p className="text-xs text-gray-500">أنت جاهز لاستلام طلبات جديدة عند جهوزيتها في المطبخ.</p>
          </div>
        ) : (
          assignedOrders.map((ord) => (
            <div
              key={ord.id}
              className="bg-white rounded-2xl border-2 border-amber-300 p-5 shadow-md space-y-4 animate-in fade-in"
            >
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono font-black text-base text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">
                      {ord.tracking_code}
                    </span>
                    <span className="text-xs px-2.5 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800">
                      {ord.status === 'ready'
                        ? 'جاهز بالمطعم'
                        : ord.status === 'on_way'
                        ? 'في الطريق للعميل'
                        : 'قيد التحضير'}
                    </span>
                  </div>
                  <div className="text-sm font-black text-gray-900 mt-1">{ord.customer_name}</div>
                </div>

                <div className="text-left font-black text-base text-gray-900">
                  {ord.total.toLocaleString()} ل.س
                  <div className="text-[10px] text-gray-400 font-normal">
                    {ord.payment_method === 'cash_on_delivery' ? 'كاش (تحصيل عند التسليم)' : 'مدفوع إلكترونياً'}
                  </div>
                </div>
              </div>

              {/* Customer Contact & Navigation Address */}
              <div className="bg-gray-50 p-3.5 rounded-xl space-y-2 border border-gray-200 text-xs">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-1.5 text-gray-700">
                    <Phone className="w-3.5 h-3.5 text-gray-400" />
                    <span className="font-mono font-bold" dir="ltr">{ord.customer_phone}</span>
                  </div>
                  <a
                    href={`tel:${ord.customer_phone}`}
                    className="bg-emerald-600 text-white px-3 py-1 rounded-lg font-bold flex items-center gap-1 text-[11px]"
                  >
                    <Phone className="w-3 h-3" /> اتصال بالعميل
                  </a>
                </div>

                {ord.customer_address && (
                  <div className="flex items-start gap-1.5 text-gray-800 pt-1 border-t border-gray-200">
                    <MapPin className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                      <span className="font-bold">العنوان: </span>
                      <span>{ord.customer_address}</span>
                    </div>
                  </div>
                )}

                {ord.notes && (
                  <div className="text-rose-600 bg-rose-50 p-2 rounded-lg text-[11px] font-semibold">
                    ملاحظة العميل: {ord.notes}
                  </div>
                )}
              </div>

              {/* Items summary */}
              <div className="text-xs text-gray-600 space-y-1">
                <span className="font-bold text-gray-800">محتويات الوجبة:</span>
                <div className="flex flex-wrap gap-1.5">
                  {ord.items.map((it, idx) => (
                    <span key={idx} className="bg-gray-100 text-gray-800 px-2 py-0.5 rounded text-[11px]">
                      {it.quantity}x {it.product_name_ar} {it.variant_name ? `(${it.variant_name})` : ''}
                    </span>
                  ))}
                </div>
              </div>

              {/* Driver Action Workflow Buttons */}
              <div className="pt-2 border-t border-gray-100 flex flex-wrap gap-2">
                {ord.status !== 'on_way' && (
                  <button
                    onClick={() => handleUpdateStatus(ord.id, 'on_way')}
                    className="grow bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
                  >
                    <Bike className="w-4 h-4" />
                    <span>استلمت الوجبة وبدأت التوصيل (في الطريق)</span>
                  </button>
                )}

                {ord.status === 'on_way' && (
                  <button
                    onClick={() => handleUpdateStatus(ord.id, 'delivered')}
                    className="grow bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-md"
                  >
                    <CheckCircle className="w-4 h-4" />
                    <span>تم تسليم الوجبة للعميل واستلام الحساب بنجاح</span>
                  </button>
                )}
              </div>
            </div>
          ))
        )}
      </div>

      {/* Completed Orders Today */}
      {deliveredOrders.length > 0 && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-3">
          <h3 className="font-bold text-sm text-gray-900">طلبات أنجزتها اليوم ({deliveredOrders.length})</h3>
          <div className="divide-y divide-gray-100">
            {deliveredOrders.slice(0, 4).map((ord) => (
              <div key={ord.id} className="py-2.5 flex items-center justify-between text-xs">
                <div>
                  <span className="font-mono font-bold text-amber-600">{ord.tracking_code}</span>
                  <span className="text-gray-700 mr-2">{ord.customer_name}</span>
                </div>
                <div className="text-emerald-700 font-bold flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                  <span>{ord.total.toLocaleString()} ل.س</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};
