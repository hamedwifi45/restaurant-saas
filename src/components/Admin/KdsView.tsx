import React, { useState, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { Order, OrderStatus } from '../../types';
import {
  ChefHat,
  Clock,
  CheckCircle2,
  AlertTriangle,
  Play,
  Check,
  XCircle,
  Bell,
  UtensilsCrossed,
  Bike,
  Volume2,
} from 'lucide-react';

export const KdsView: React.FC = () => {
  const { currentRestaurant, orders, updateOrderStatus } = useApp();
  const [checkedItems, setCheckedItems] = useState<{ [itemId: string]: boolean }>({});
  const [nowTime, setNowTime] = useState<number>(Date.now());

  // Update clock every 30s to recalculate elapsed time
  useEffect(() => {
    const timer = setInterval(() => setNowTime(Date.now()), 30000);
    return () => clearInterval(timer);
  }, []);

  const restOrders = orders.filter((o) => o.restaurant_id === currentRestaurant.id);

  // Filter KDS active orders
  const pendingOrders = restOrders.filter((o) => o.status === 'pending');
  const preparingOrders = restOrders.filter((o) => o.status === 'preparing');
  const readyOrders = restOrders.filter((o) => o.status === 'ready');

  const toggleItemCheck = (itemId: string) => {
    setCheckedItems((prev) => ({ ...prev, [itemId]: !prev[itemId] }));
  };

  const getElapsedMinutes = (createdAt: string) => {
    const diffMs = nowTime - new Date(createdAt).getTime();
    return Math.max(0, Math.floor(diffMs / (1000 * 60)));
  };

  const renderOrderCard = (order: Order, stage: 'pending' | 'preparing' | 'ready') => {
    const elapsedMinutes = getElapsedMinutes(order.created_at);
    const isUrgent = elapsedMinutes >= 15;
    const isWarning = elapsedMinutes >= 8 && elapsedMinutes < 15;

    return (
      <div
        key={order.id}
        className={`bg-white rounded-2xl border-2 overflow-hidden shadow-md flex flex-col justify-between transition-all ${
          isUrgent
            ? 'border-rose-500 shadow-rose-100 ring-2 ring-rose-200'
            : isWarning
            ? 'border-amber-400'
            : 'border-gray-200'
        }`}
      >
        {/* Card Header */}
        <div
          className={`p-3 text-white flex items-center justify-between ${
            isUrgent ? 'bg-rose-700' : isWarning ? 'bg-amber-600' : 'bg-gray-900'
          }`}
        >
          <div className="flex items-center gap-2">
            <span className="font-mono font-black text-sm tracking-wider">
              {order.tracking_code}
            </span>
            <span className="text-[11px] bg-white/20 px-2 py-0.5 rounded-full font-bold">
              {order.delivery_type === 'dine_in'
                ? `🍽️ ${order.table_number || 'طاولة'}`
                : order.delivery_type === 'delivery'
                ? '🛵 توصيل'
                : '🥡 سفري'}
            </span>
          </div>

          <div className="flex items-center gap-1 font-mono text-xs font-bold">
            <Clock className="w-3.5 h-3.5" />
            <span>{elapsedMinutes} د</span>
          </div>
        </div>

        {/* Customer & Notes */}
        <div className="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between text-xs">
          <span className="font-bold text-gray-800">{order.customer_name}</span>
          {order.notes && (
            <span className="text-[11px] font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
              ملاحظة: {order.notes}
            </span>
          )}
        </div>

        {/* Order Items Checklist */}
        <div className="p-4 space-y-2 grow divide-y divide-gray-100 text-xs">
          {order.items.map((item) => {
            const isChecked = !!checkedItems[item.id];
            return (
              <div
                key={item.id}
                onClick={() => toggleItemCheck(item.id)}
                className={`pt-2 flex items-start gap-2.5 cursor-pointer select-none transition-all ${
                  isChecked ? 'opacity-40 line-through' : ''
                }`}
              >
                <div
                  className={`w-4 h-4 rounded border mt-0.5 flex items-center justify-center shrink-0 transition-colors ${
                    isChecked
                      ? 'bg-emerald-600 border-emerald-600 text-white'
                      : 'border-gray-300 bg-white'
                  }`}
                >
                  {isChecked && <Check className="w-3 h-3" />}
                </div>

                <div className="grow">
                  <div className="font-extrabold text-sm text-gray-900 flex items-center gap-1.5">
                    <span className="text-amber-600 font-mono">[{item.quantity}x]</span>
                    <span>{item.product_name_ar}</span>
                  </div>
                  {item.variant_name && (
                    <div className="text-[11px] text-amber-700 font-bold">
                      {item.variant_name}
                    </div>
                  )}
                  {item.notes && (
                    <div className="text-[10px] text-rose-500 italic font-semibold">
                      تنبيه: {item.notes}
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>

        {/* Stage Transition Action Bar */}
        <div className="p-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-2">
          {stage === 'pending' && (
            <>
              <button
                onClick={() => updateOrderStatus(order.id, 'cancelled')}
                className="p-2 rounded-xl text-rose-600 hover:bg-rose-50 font-bold text-xs flex items-center gap-1 cursor-pointer"
                title="رفض الطلب"
              >
                <XCircle className="w-4 h-4" /> رفض
              </button>
              <button
                onClick={() => updateOrderStatus(order.id, 'preparing')}
                className="grow bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
              >
                <Play className="w-3.5 h-3.5 fill-white" />
                <span>بدء التحضير والطهي</span>
              </button>
            </>
          )}

          {stage === 'preparing' && (
            <button
              onClick={() => updateOrderStatus(order.id, 'ready')}
              className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>اكتمل الطهي - الطلب جاهز للتسليم</span>
            </button>
          )}

          {stage === 'ready' && (
            <div className="w-full flex items-center justify-between text-xs">
              <span className="text-emerald-700 font-bold flex items-center gap-1">
                <Check className="w-4 h-4" /> جاهز للتسليم
              </span>
              <button
                onClick={() =>
                  updateOrderStatus(order.id, order.delivery_type === 'delivery' ? 'on_way' : 'delivered')
                }
                className="bg-gray-800 hover:bg-gray-900 text-white px-3 py-1.5 rounded-lg font-bold cursor-pointer"
              >
                {order.delivery_type === 'delivery' ? 'تسليم للمندوب' : 'تم التسليم للزبون'}
              </button>
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* KDS Header */}
      <div className="bg-gray-950 text-white p-4 sm:p-5 rounded-2xl flex flex-wrap items-center justify-between gap-4 border border-gray-800 shadow-lg">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-amber-500 text-gray-950 flex items-center justify-center font-black">
            <ChefHat className="w-6 h-6" />
          </div>
          <div>
            <h1 className="text-lg font-black text-white">شاشة المطبخ الذكية (KDS)</h1>
            <p className="text-xs text-gray-400">
              إدارة تذاكر الطهي الحية لطاقم مطبخ {currentRestaurant.name_ar}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-3 text-xs">
          <div className="flex items-center gap-1.5 bg-gray-900 border border-gray-800 px-3 py-1.5 rounded-xl font-mono text-amber-400 font-bold">
            <Clock className="w-4 h-4" />
            <span>{new Date(nowTime).toLocaleTimeString('ar-SY')}</span>
          </div>

          <div className="hidden sm:flex items-center gap-3 text-gray-400 text-[11px]">
            <span className="flex items-center gap-1">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> طبيعي &lt; 8د
            </span>
            <span className="flex items-center gap-1">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-500"></span> تحذير 8-15د
            </span>
            <span className="flex items-center gap-1">
              <span className="w-2.5 h-2.5 rounded-full bg-rose-500"></span> حرج &gt; 15د
            </span>
          </div>
        </div>
      </div>

      {/* 3 Columns Board */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        {/* Column 1: New / Pending Orders */}
        <div className="bg-gray-100/80 rounded-2xl p-4 border border-gray-200/80 space-y-4 flex flex-col min-h-[600px]">
          <div className="flex items-center justify-between pb-2 border-b border-gray-300">
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 rounded-full bg-blue-500"></div>
              <h3 className="font-extrabold text-sm text-gray-900">طلبات جديدة واردة</h3>
            </div>
            <span className="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
              {pendingOrders.length}
            </span>
          </div>

          <div className="space-y-4 grow overflow-y-auto">
            {pendingOrders.length === 0 ? (
              <div className="py-20 text-center text-gray-400 text-xs">
                لا توجد طلبات جديدة معلقة
              </div>
            ) : (
              pendingOrders.map((ord) => renderOrderCard(ord, 'pending'))
            )}
          </div>
        </div>

        {/* Column 2: In Preparation / Cooking */}
        <div className="bg-amber-50/60 rounded-2xl p-4 border border-amber-200/80 space-y-4 flex flex-col min-h-[600px]">
          <div className="flex items-center justify-between pb-2 border-b border-amber-300">
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></div>
              <h3 className="font-extrabold text-sm text-amber-950">قيد الطهي والتحضير</h3>
            </div>
            <span className="bg-amber-200 text-amber-900 text-xs font-bold px-2.5 py-0.5 rounded-full">
              {preparingOrders.length}
            </span>
          </div>

          <div className="space-y-4 grow overflow-y-auto">
            {preparingOrders.length === 0 ? (
              <div className="py-20 text-center text-gray-400 text-xs">
                المطبخ في وضع الانتظار
              </div>
            ) : (
              preparingOrders.map((ord) => renderOrderCard(ord, 'preparing'))
            )}
          </div>
        </div>

        {/* Column 3: Ready for Delivery or Pickup */}
        <div className="bg-emerald-50/60 rounded-2xl p-4 border border-emerald-200/80 space-y-4 flex flex-col min-h-[600px]">
          <div className="flex items-center justify-between pb-2 border-b border-emerald-300">
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
              <h3 className="font-extrabold text-sm text-emerald-950">جاهزة للتسليم / المندوب</h3>
            </div>
            <span className="bg-emerald-200 text-emerald-900 text-xs font-bold px-2.5 py-0.5 rounded-full">
              {readyOrders.length}
            </span>
          </div>

          <div className="space-y-4 grow overflow-y-auto">
            {readyOrders.length === 0 ? (
              <div className="py-20 text-center text-gray-400 text-xs">
                لا توجد طلبات جاهزة بانتظار التسليم
              </div>
            ) : (
              readyOrders.map((ord) => renderOrderCard(ord, 'ready'))
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
