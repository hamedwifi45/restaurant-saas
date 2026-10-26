import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Restaurant, DeliveryProvider } from '../../types';
import {
  ShieldCheck,
  Building2,
  TrendingUp,
  DollarSign,
  Users,
  Layers,
  Truck,
  ExternalLink,
  Plus,
  CheckCircle,
  ToggleLeft,
  ToggleRight,
  Eye,
} from 'lucide-react';

interface SuperAdminPortalProps {
  onImpersonateRestaurant: (slug: string) => void;
}

export const SuperAdminPortal: React.FC<SuperAdminPortalProps> = ({
  onImpersonateRestaurant,
}) => {
  const { restaurants, orders, deliveryProviders, switchRestaurant, setCurrentRole } = useApp();

  const [activeTab, setActiveTab] = useState<'restaurants' | 'delivery' | 'themes'>('restaurants');

  // Platform aggregations
  const totalPlatformRevenue = orders
    .filter((o) => o.status !== 'cancelled')
    .reduce((sum, o) => sum + o.total, 0);

  const totalPlatformOrders = orders.length;
  const activeRestaurantsCount = restaurants.filter((r) => r.is_active).length;

  const handleImpersonate = (slug: string) => {
    switchRestaurant(slug);
    setCurrentRole('restaurant_admin');
    onImpersonateRestaurant(slug);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Super Admin Top Header */}
      <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 rounded-3xl border border-indigo-900/60 shadow-xl space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-indigo-500/30">
              <ShieldCheck className="w-7 h-7" />
            </div>
            <div>
              <div className="text-xs text-indigo-300 font-semibold uppercase tracking-wider">
                Sham SaaS Multi-Tenant Platform
              </div>
              <h1 className="text-xl sm:text-2xl font-black text-white">
                لوحة الإدارة المركزية العامة (Sham Admin)
              </h1>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span className="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-3 py-1 rounded-full text-xs font-bold">
              صلاحيات المدير العام (Super Admin)
            </span>
          </div>
        </div>

        {/* Global KPIs */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-indigo-900/50">
          <div className="bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10 space-y-1">
            <span className="text-indigo-200 text-xs font-medium">المطاعم المشتركة في المنصة</span>
            <div className="text-2xl font-black text-white">
              {restaurants.length} <span className="text-xs text-indigo-300">مطاعم ({activeRestaurantsCount} نشط)</span>
            </div>
          </div>

          <div className="bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10 space-y-1">
            <span className="text-indigo-200 text-xs font-medium">إجمالي المبيعات المجمعة للمنصة</span>
            <div className="text-2xl font-black text-emerald-400">
              {totalPlatformRevenue.toLocaleString()} <span className="text-xs text-emerald-300">ل.س</span>
            </div>
          </div>

          <div className="bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10 space-y-1">
            <span className="text-indigo-200 text-xs font-medium">إجمالي الطلبات المنفذة</span>
            <div className="text-2xl font-black text-amber-400">
              {totalPlatformOrders} <span className="text-xs text-amber-300">طلب</span>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs Selector */}
      <div className="flex items-center gap-2 border-b border-gray-200 pb-3">
        <button
          onClick={() => setActiveTab('restaurants')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
            activeTab === 'restaurants'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          }`}
        >
          دليل المطاعم ({restaurants.length})
        </button>
        <button
          onClick={() => setActiveTab('delivery')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
            activeTab === 'delivery'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          }`}
        >
          مزودو خدمات التوصيل ({deliveryProviders.length})
        </button>
        <button
          onClick={() => setActiveTab('themes')}
          className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
            activeTab === 'themes'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
          }`}
        >
          مستودع قوالب وثيمات الواجهة (Themes)
        </button>
      </div>

      {/* Tab 1: Restaurants Directory */}
      {activeTab === 'restaurants' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {restaurants.map((rest) => {
              const restOrdersCount = orders.filter((o) => o.restaurant_id === rest.id).length;
              const restRevenue = orders
                .filter((o) => o.restaurant_id === rest.id && o.status !== 'cancelled')
                .reduce((sum, o) => sum + o.total, 0);

              return (
                <div
                  key={rest.id}
                  className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-indigo-400 transition-all"
                >
                  <div className="space-y-3">
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <img
                          src={rest.logo}
                          alt={rest.name_ar}
                          className="w-12 h-12 rounded-xl object-cover ring-2 ring-indigo-100"
                        />
                        <div>
                          <h3 className="font-bold text-sm text-gray-900">{rest.name_ar}</h3>
                          <div className="text-[11px] text-gray-400 font-mono">
                            {rest.slug}.restaurant-saas.com
                          </div>
                        </div>
                      </div>

                      <span
                        className={`text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase ${
                          rest.plan === 'pro'
                            ? 'bg-indigo-100 text-indigo-800'
                            : 'bg-emerald-100 text-emerald-800'
                        }`}
                      >
                        خطة {rest.plan}
                      </span>
                    </div>

                    <p className="text-xs text-gray-600 line-clamp-2">{rest.description_ar}</p>

                    <div className="bg-gray-50 rounded-xl p-3 space-y-1.5 text-xs text-gray-600 border border-gray-100">
                      <div className="flex justify-between">
                        <span>المدينة / العنوان:</span>
                        <span className="font-bold text-gray-800">{rest.city}</span>
                      </div>
                      <div className="flex justify-between">
                        <span>عدد الطلبات:</span>
                        <span className="font-bold text-gray-800">{restOrdersCount} طلب</span>
                      </div>
                      <div className="flex justify-between">
                        <span>حجم المبيعات:</span>
                        <span className="font-bold text-emerald-600">
                          {restRevenue.toLocaleString()} ل.س
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="pt-2 border-t border-gray-100 flex items-center justify-between">
                    <button
                      onClick={() => handleImpersonate(rest.slug)}
                      className="bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                    >
                      <ExternalLink className="w-3.5 h-3.5" />
                      <span>تقمص إدارة المطعم (Impersonate)</span>
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Tab 2: Delivery Providers */}
      {activeTab === 'delivery' && (
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
          <div className="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 className="font-bold text-sm text-gray-900">شركات التوصيل وشركاء الخدمات اللوجستية</h3>
          </div>
          <table className="w-full text-xs text-right">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold">
                <th className="py-3 px-4">اسم المزود</th>
                <th className="py-3 px-4">نوع الخدمة</th>
                <th className="py-3 px-4">رقم التواصل</th>
                <th className="py-3 px-4">الطلبات المسندة</th>
                <th className="py-3 px-4">الحالة</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {deliveryProviders.map((dp) => (
                <tr key={dp.id} className="hover:bg-gray-50/80">
                  <td className="py-3 px-4 font-bold text-gray-900">{dp.name}</td>
                  <td className="py-3 px-4 text-gray-600">{dp.service_type}</td>
                  <td className="py-3 px-4 font-mono text-gray-600" dir="ltr">{dp.contact_phone}</td>
                  <td className="py-3 px-4 font-bold text-amber-600">{dp.active_deliveries} شحنة</td>
                  <td className="py-3 px-4">
                    <span
                      className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                        dp.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'
                      }`}
                    >
                      {dp.is_active ? 'مفعل وجاهز' : 'معطل'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Tab 3: Themes Gallery */}
      {activeTab === 'themes' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-3">
            <div className="flex items-center justify-between">
              <h4 className="font-bold text-sm text-gray-900">قالب برجر الباشا (AlBasha Burger Theme)</h4>
              <span className="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                القالب الافتراضي v2.0
              </span>
            </div>
            <p className="text-xs text-gray-500">
              مخصص لمطاعم البرجر والوجبات السريعة، يدعم الألوان الحارة، البطاقات البارزة، والبانر الديناميكي.
            </p>
            <div className="text-[11px] text-gray-400 font-mono">
              المسار: /resources/views/themes/albasha-burger/
            </div>
          </div>

          <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-3">
            <div className="flex items-center justify-between">
              <h4 className="font-bold text-sm text-gray-900">قالب المأكولات الشامية (Oriental Damascene Theme)</h4>
              <span className="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                مفعل لقصر الشام v1.2
              </span>
            </div>
            <p className="text-xs text-gray-500">
              مخصص للمطاعم الشرقية، المشاوي والشاورما، يدعم خط Cairo، والزخارف الشامية، وأقسام المقالي والمقبلات.
            </p>
            <div className="text-[11px] text-gray-400 font-mono">
              المسار: /resources/views/themes/albasha-burger-example/
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
