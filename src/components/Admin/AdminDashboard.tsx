import React from 'react';
import { useApp } from '../../context/AppContext';
import {
  DollarSign,
  ShoppingBag,
  TrendingUp,
  Users,
  Bike,
  Clock,
  ArrowUpRight,
  UtensilsCrossed,
  CheckCircle2,
  AlertCircle,
  FileText,
} from 'lucide-react';

interface AdminDashboardProps {
  onNavigateTab: (tab: string) => void;
}

export const AdminDashboard: React.FC<AdminDashboardProps> = ({ onNavigateTab }) => {
  const { currentRestaurant, orders, products, tables, drivers, expenses } = useApp();

  const restOrders = orders.filter((o) => o.restaurant_id === currentRestaurant.id);
  const restTables = tables.filter((t) => t.restaurant_id === currentRestaurant.id);
  const restDrivers = drivers.filter((d) => d.restaurant_id === currentRestaurant.id);
  const restExpenses = expenses.filter((e) => e.restaurant_id === currentRestaurant.id);

  // Revenue metrics
  const totalRevenue = restOrders
    .filter((o) => o.status !== 'cancelled')
    .reduce((sum, o) => sum + o.total, 0);

  const totalExpenseAmount = restExpenses.reduce((sum, e) => sum + e.amount, 0);
  const netProfit = totalRevenue - totalExpenseAmount;

  const completedOrdersCount = restOrders.filter((o) => o.status === 'delivered').length;
  const activeOrders = restOrders.filter(
    (o) => o.status === 'pending' || o.status === 'preparing' || o.status === 'ready' || o.status === 'on_way'
  );

  const avgTicket = restOrders.length > 0 ? Math.round(totalRevenue / restOrders.length) : 0;

  // Order types breakdown
  const deliveryOrdersCount = restOrders.filter((o) => o.delivery_type === 'delivery').length;
  const dineInOrdersCount = restOrders.filter((o) => o.delivery_type === 'dine_in').length;
  const takeawayOrdersCount = restOrders.filter((o) => o.delivery_type === 'takeaway').length;

  // Tables occupancy
  const occupiedTables = restTables.filter((t) => t.status === 'occupied').length;
  const tableOccupancyPercent = restTables.length > 0 ? Math.round((occupiedTables / restTables.length) * 100) : 0;

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Top Welcome & Quick Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl sm:text-2xl font-black text-gray-900">
              لوحة تحكم: {currentRestaurant.name_ar}
            </h1>
            <span className="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-0.5 rounded-full uppercase">
              خطة {currentRestaurant.plan}
            </span>
          </div>
          <p className="text-xs text-gray-500 mt-1">
            نظرة عامة فورية على المبيعات، كفاءة التشغيل، حركة التوصيل وإشغال الصالة.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => onNavigateTab('pos')}
            className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
          >
            <UtensilsCrossed className="w-4 h-4" />
            <span>فتح شاشة الكاشير (POS)</span>
          </button>
          <button
            onClick={() => onNavigateTab('kds')}
            className="bg-gray-800 hover:bg-gray-900 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
          >
            <Clock className="w-4 h-4 text-amber-400" />
            <span>شاشة المطبخ (KDS)</span>
          </button>
        </div>
      </div>

      {/* KPI Stats Grid */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Revenue */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">إجمالي المبيعات</span>
            <div className="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <DollarSign className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-gray-900">
            {totalRevenue.toLocaleString()}{' '}
            <span className="text-xs font-normal text-gray-500">ل.س</span>
          </div>
          <div className="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
            <TrendingUp className="w-3 h-3" />
            <span>صافي الأرباح: {netProfit.toLocaleString()} ل.س</span>
          </div>
        </div>

        {/* Active Orders */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">الطلبات النشطة الآن</span>
            <div className="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
              <ShoppingBag className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-amber-600">
            {activeOrders.length}{' '}
            <span className="text-xs font-normal text-gray-500">طلبات جارية</span>
          </div>
          <div className="text-[11px] text-gray-500">
            إجمالي الطلبات المنجزة: {completedOrdersCount}
          </div>
        </div>

        {/* Avg Order Ticket */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">متوسط الفاتورة</span>
            <div className="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-gray-900">
            {avgTicket.toLocaleString()}{' '}
            <span className="text-xs font-normal text-gray-500">ل.س</span>
          </div>
          <div className="text-[11px] text-gray-500">محسوب من مجمل الطلبات</div>
        </div>

        {/* Dine-in Table Occupancy */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200 shadow-xs space-y-2">
          <div className="flex items-center justify-between text-gray-500">
            <span className="text-xs font-bold">إشغال الصالة</span>
            <div className="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
              <Users className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-gray-900">
            {tableOccupancyPercent}%{' '}
            <span className="text-xs font-normal text-gray-500">
              ({occupiedTables} من {restTables.length} طاولات)
            </span>
          </div>
          <div className="text-[11px] text-purple-700 font-semibold">
            جاهزية الصالة لاستقبال الزبائن
          </div>
        </div>
      </div>

      {/* Middle Row: Order Types & Top Products */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Order Types Distribution */}
        <div className="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
          <h3 className="font-bold text-sm text-gray-900">توزيع قنوات الطلب</h3>
          <div className="space-y-3">
            <div>
              <div className="flex justify-between text-xs font-bold text-gray-700 mb-1">
                <span className="flex items-center gap-1.5">
                  <Bike className="w-3.5 h-3.5 text-amber-600" /> توصيل منزلي
                </span>
                <span>{deliveryOrdersCount} طلب ({restOrders.length > 0 ? Math.round((deliveryOrdersCount / restOrders.length) * 100) : 0}%)</span>
              </div>
              <div className="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div
                  className="bg-amber-500 h-full rounded-full"
                  style={{
                    width: `${restOrders.length > 0 ? (deliveryOrdersCount / restOrders.length) * 100 : 0}%`,
                  }}
                ></div>
              </div>
            </div>

            <div>
              <div className="flex justify-between text-xs font-bold text-gray-700 mb-1">
                <span className="flex items-center gap-1.5">
                  <UtensilsCrossed className="w-3.5 h-3.5 text-purple-600" /> صالة محلي (طاولات)
                </span>
                <span>{dineInOrdersCount} طلب ({restOrders.length > 0 ? Math.round((dineInOrdersCount / restOrders.length) * 100) : 0}%)</span>
              </div>
              <div className="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div
                  className="bg-purple-500 h-full rounded-full"
                  style={{
                    width: `${restOrders.length > 0 ? (dineInOrdersCount / restOrders.length) * 100 : 0}%`,
                  }}
                ></div>
              </div>
            </div>

            <div>
              <div className="flex justify-between text-xs font-bold text-gray-700 mb-1">
                <span className="flex items-center gap-1.5">
                  <ShoppingBag className="w-3.5 h-3.5 text-blue-600" /> استلام سفري
                </span>
                <span>{takeawayOrdersCount} طلب ({restOrders.length > 0 ? Math.round((takeawayOrdersCount / restOrders.length) * 100) : 0}%)</span>
              </div>
              <div className="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div
                  className="bg-blue-500 h-full rounded-full"
                  style={{
                    width: `${restOrders.length > 0 ? (takeawayOrdersCount / restOrders.length) * 100 : 0}%`,
                  }}
                ></div>
              </div>
            </div>
          </div>
        </div>

        {/* Top Selling Products */}
        <div className="lg:col-span-2 bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-sm text-gray-900">الأصناف الأكثر طلباً ومبيعاً</h3>
            <button
              onClick={() => onNavigateTab('menu')}
              className="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1 cursor-pointer"
            >
              <span>إدارة المنيو</span>
              <ArrowUpRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="divide-y divide-gray-100">
            {products
              .filter((p) => p.restaurant_id === currentRestaurant.id)
              .slice(0, 4)
              .map((prod) => (
                <div key={prod.id} className="py-2.5 flex items-center justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <img
                      src={prod.image}
                      alt={prod.name_ar}
                      className="w-10 h-10 rounded-lg object-cover"
                    />
                    <div>
                      <div className="font-bold text-xs text-gray-900">{prod.name_ar}</div>
                      <div className="text-[10px] text-gray-500 font-medium">
                        {prod.calories ? `${prod.calories} سعرة حرارية` : 'وجبة مميزة'}
                      </div>
                    </div>
                  </div>
                  <div className="text-left">
                    <div className="font-bold text-xs text-amber-600">
                      {prod.price.toLocaleString()} ل.س
                    </div>
                    <span className="text-[10px] text-emerald-600 bg-emerald-50 px-1.5 py-0.2 rounded font-semibold">
                      متوفر في المنيو
                    </span>
                  </div>
                </div>
              ))}
          </div>
        </div>
      </div>

      {/* Recent Live Orders Feed */}
      <div className="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></div>
            <h3 className="font-bold text-sm text-gray-900">سجل الطلبات الحية الأحدث</h3>
          </div>
          <button
            onClick={() => onNavigateTab('orders')}
            className="text-xs font-bold text-amber-600 hover:text-amber-700 cursor-pointer"
          >
            عرض كافة الطلبات ({restOrders.length})
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-xs text-right">
            <thead>
              <tr className="border-b border-gray-200 text-gray-500 font-bold">
                <th className="py-2.5 px-3">رمز التتبع</th>
                <th className="py-2.5 px-3">العميل</th>
                <th className="py-2.5 px-3">النوع</th>
                <th className="py-2.5 px-3">الأصناف</th>
                <th className="py-2.5 px-3">الإجمالي</th>
                <th className="py-2.5 px-3">الحالة</th>
                <th className="py-2.5 px-3">الدفع</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {restOrders.slice(0, 5).map((order) => (
                <tr key={order.id} className="hover:bg-gray-50/80 transition-colors">
                  <td className="py-3 px-3 font-mono font-bold text-amber-600">
                    {order.tracking_code}
                  </td>
                  <td className="py-3 px-3">
                    <div className="font-bold text-gray-900">{order.customer_name}</div>
                    <div className="text-[10px] text-gray-400">{order.customer_phone}</div>
                  </td>
                  <td className="py-3 px-3">
                    <span className="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md font-medium text-[11px]">
                      {order.delivery_type === 'delivery'
                        ? 'توصيل'
                        : order.delivery_type === 'dine_in'
                        ? `طاولة (${order.table_number})`
                        : 'استلام'}
                    </span>
                  </td>
                  <td className="py-3 px-3 text-gray-600">
                    {order.items.length} أصناف
                  </td>
                  <td className="py-3 px-3 font-bold text-gray-900">
                    {order.total.toLocaleString()} ل.س
                  </td>
                  <td className="py-3 px-3">
                    <span
                      className={`px-2 py-0.5 rounded-full font-bold text-[10px] ${
                        order.status === 'delivered'
                          ? 'bg-emerald-100 text-emerald-800'
                          : order.status === 'on_way'
                          ? 'bg-blue-100 text-blue-800'
                          : order.status === 'ready'
                          ? 'bg-purple-100 text-purple-800'
                          : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {order.status === 'pending'
                        ? 'جديد'
                        : order.status === 'preparing'
                        ? 'قيد التحضير'
                        : order.status === 'ready'
                        ? 'جاهز'
                        : order.status === 'on_way'
                        ? 'في الطريق'
                        : order.status === 'delivered'
                        ? 'تم التسليم'
                        : 'ملغى'}
                    </span>
                  </td>
                  <td className="py-3 px-3">
                    <span
                      className={`text-[10px] font-semibold ${
                        order.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'
                      }`}
                    >
                      {order.payment_status === 'paid' ? 'مدفوع' : 'معلق'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
