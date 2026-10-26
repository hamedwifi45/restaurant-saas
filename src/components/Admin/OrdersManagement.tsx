import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Order, OrderStatus } from '../../types';
import {
  Search,
  Filter,
  Bike,
  Clock,
  Printer,
  FileText,
  User,
  Phone,
  MapPin,
  Calendar,
  CheckCircle,
  XCircle,
  AlertCircle,
  ChevronDown,
} from 'lucide-react';

interface OrdersManagementProps {
  onOpenInvoice: (order: Order) => void;
}

export const OrdersManagement: React.FC<OrdersManagementProps> = ({ onOpenInvoice }) => {
  const { currentRestaurant, orders, updateOrderStatus, assignDriver, drivers } = useApp();

  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedOrderDetails, setSelectedOrderDetails] = useState<Order | null>(null);

  const restOrders = orders.filter((o) => o.restaurant_id === currentRestaurant.id);
  const restDrivers = drivers.filter((d) => d.restaurant_id === currentRestaurant.id);

  const filteredOrders = restOrders.filter((ord) => {
    const matchesStatus = statusFilter === 'all' || ord.status === statusFilter;
    const matchesSearch =
      searchQuery.trim() === '' ||
      ord.tracking_code.toLowerCase().includes(searchQuery.toLowerCase()) ||
      ord.customer_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      ord.customer_phone.includes(searchQuery);
    return matchesStatus && matchesSearch;
  });

  const statuses: { id: string; label: string; count: number }[] = [
    { id: 'all', label: 'كافة الطلبات', count: restOrders.length },
    { id: 'pending', label: 'قيد المراجعة', count: restOrders.filter((o) => o.status === 'pending').length },
    { id: 'preparing', label: 'قيد التحضير', count: restOrders.filter((o) => o.status === 'preparing').length },
    { id: 'ready', label: 'جاهزة للتسليم', count: restOrders.filter((o) => o.status === 'ready').length },
    { id: 'on_way', label: 'في الطريق مع المندوب', count: restOrders.filter((o) => o.status === 'on_way').length },
    { id: 'delivered', label: 'تم التسليم', count: restOrders.filter((o) => o.status === 'delivered').length },
    { id: 'cancelled', label: 'ملغاة', count: restOrders.filter((o) => o.status === 'cancelled').length },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Top Filter and Search Bar */}
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
            {statuses.map((st) => (
              <button
                key={st.id}
                onClick={() => setStatusFilter(st.id)}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer flex items-center gap-1.5 ${
                  statusFilter === st.id
                    ? 'bg-amber-600 text-white shadow-xs'
                    : 'bg-gray-50 border border-gray-200 text-gray-700 hover:bg-gray-100'
                }`}
              >
                <span>{st.label}</span>
                <span
                  className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                    statusFilter === st.id ? 'bg-amber-700 text-white' : 'bg-gray-200 text-gray-600'
                  }`}
                >
                  {st.count}
                </span>
              </button>
            ))}
          </div>

          <div className="relative w-full sm:w-64">
            <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="ابحث بالرمز أو اسم العميل..."
              className="w-full pr-9 pl-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500"
            />
          </div>
        </div>
      </div>

      {/* Orders List / Table */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
        {filteredOrders.length === 0 ? (
          <div className="py-16 text-center text-gray-400 space-y-2">
            <AlertCircle className="w-10 h-10 mx-auto text-gray-300" />
            <p className="font-bold text-gray-700">لا توجد طلبات مطابقة لهذا الفلتر</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-xs text-right">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold">
                  <th className="py-3 px-4">رمز التتبع</th>
                  <th className="py-3 px-4">العميل والتواصل</th>
                  <th className="py-3 px-4">نوع الطلب</th>
                  <th className="py-3 px-4">الأصناف والتكلفة</th>
                  <th className="py-3 px-4">مندوب التوصيل</th>
                  <th className="py-3 px-4">تغيير الحالة</th>
                  <th className="py-3 px-4 text-center">إجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredOrders.map((ord) => (
                  <tr key={ord.id} className="hover:bg-gray-50/80 transition-colors">
                    <td className="py-3 px-4">
                      <div className="font-mono font-bold text-amber-600 text-sm">
                        {ord.tracking_code}
                      </div>
                      <div className="text-[10px] text-gray-400">
                        {new Date(ord.created_at).toLocaleTimeString('ar-SY', {
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </div>
                    </td>

                    <td className="py-3 px-4">
                      <div className="font-bold text-gray-900">{ord.customer_name}</div>
                      <div className="text-[11px] text-gray-500 font-mono" dir="ltr">
                        {ord.customer_phone}
                      </div>
                      {ord.customer_address && (
                        <div className="text-[10px] text-gray-400 truncate max-w-xs">
                          {ord.customer_address}
                        </div>
                      )}
                    </td>

                    <td className="py-3 px-4">
                      <span className="bg-gray-100 text-gray-800 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                        {ord.delivery_type === 'delivery'
                          ? '🛵 توصيل منزلي'
                          : ord.delivery_type === 'dine_in'
                          ? `🍽️ طاولة (${ord.table_number || 'محلي'})`
                          : '🥡 سفري استلام'}
                      </span>
                    </td>

                    <td className="py-3 px-4">
                      <div className="font-bold text-gray-900">{ord.total.toLocaleString()} ل.س</div>
                      <div className="text-[11px] text-gray-500">{ord.items.length} أصناف</div>
                    </td>

                    <td className="py-3 px-4">
                      {ord.delivery_type === 'delivery' ? (
                        <div className="flex items-center gap-1.5">
                          <select
                            value={ord.driver_id || ''}
                            onChange={(e) => assignDriver(ord.id, e.target.value)}
                            className="bg-gray-50 border border-gray-200 rounded-lg p-1 text-[11px] font-medium text-gray-800"
                          >
                            <option value="">-- تعيين مندوب --</option>
                            {restDrivers.map((d) => (
                              <option key={d.id} value={d.id}>
                                {d.name} ({d.vehicle_type})
                              </option>
                            ))}
                          </select>
                        </div>
                      ) : (
                        <span className="text-[11px] text-gray-400">غير مطلوب</span>
                      )}
                    </td>

                    <td className="py-3 px-4">
                      <select
                        value={ord.status}
                        onChange={(e) => updateOrderStatus(ord.id, e.target.value as OrderStatus)}
                        className={`text-xs font-bold rounded-xl px-2.5 py-1 border transition-all cursor-pointer ${
                          ord.status === 'delivered'
                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                            : ord.status === 'on_way'
                            ? 'bg-blue-50 text-blue-800 border-blue-200'
                            : ord.status === 'ready'
                            ? 'bg-purple-50 text-purple-800 border-purple-200'
                            : ord.status === 'preparing'
                            ? 'bg-orange-50 text-orange-800 border-orange-200'
                            : ord.status === 'cancelled'
                            ? 'bg-rose-50 text-rose-800 border-rose-200'
                            : 'bg-amber-50 text-amber-800 border-amber-200'
                        }`}
                      >
                        <option value="pending">قيد المراجعة</option>
                        <option value="preparing">قيد التحضير بالمطبخ</option>
                        <option value="ready">الطلب جاهز</option>
                        <option value="on_way">في الطريق مع المندوب</option>
                        <option value="delivered">تم التسليم بنجاح</option>
                        <option value="cancelled">إلغاء الطلب</option>
                      </select>
                    </td>

                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          onClick={() => onOpenInvoice(ord)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-amber-600 hover:bg-amber-50 cursor-pointer"
                          title="عرض وطباعة الفاتورة"
                        >
                          <FileText className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};
