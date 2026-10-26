import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Coupon } from '../../types';
import { Tag, Plus, Trash2, CheckCircle2, Clock, X } from 'lucide-react';

export const CouponsManagement: React.FC = () => {
  const { currentRestaurant, coupons, saveCoupon, deleteCoupon } = useApp();

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [code, setCode] = useState('');
  const [discountType, setDiscountType] = useState<'percent' | 'fixed'>('percent');
  const [discountValue, setDiscountValue] = useState<number>(15);
  const [minOrder, setMinOrder] = useState<number>(40000);
  const [expiresAt, setExpiresAt] = useState('2026-12-31');

  const restCoupons = coupons.filter((c) => c.restaurant_id === currentRestaurant.id);

  const handleAddCoupon = (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim() || discountValue <= 0) return;

    saveCoupon({
      restaurant_id: currentRestaurant.id,
      code: code.trim().toUpperCase(),
      discount_type: discountType,
      discount_value: discountValue,
      min_order: minOrder,
      expires_at: expiresAt,
    });

    setCode('');
    setDiscountValue(15);
    setIsAddModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Tag className="w-5 h-5 text-amber-600" />
            <h2 className="text-lg font-black text-gray-900">إدارة الكوبونات ورموز الخصم</h2>
          </div>
          <p className="text-xs text-gray-500 mt-1">
            إنشاء وإدارة أكواد الخصم الترويجية لزيادة المبيعات في {currentRestaurant.name_ar}.
          </p>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>إنشاء كوبون جديد</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {restCoupons.map((cp) => (
          <div
            key={cp.id}
            className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-amber-400 transition-all"
          >
            <div className="flex items-start justify-between">
              <div className="space-y-1">
                <span className="font-mono font-black text-base tracking-wider bg-amber-50 text-amber-800 border border-amber-200 px-3 py-1 rounded-lg inline-block">
                  {cp.code}
                </span>
                <div className="text-xs text-gray-500 font-medium">
                  {cp.discount_type === 'percent'
                    ? `خصم ${cp.discount_value}% من قيمة الطلب`
                    : `خصم نقدي مباشر ${cp.discount_value.toLocaleString()} ل.س`}
                </div>
              </div>

              <button
                onClick={() => {
                  if (window.confirm(`هل أنت متأكد من حذف الكوبون ${cp.code}؟`)) {
                    deleteCoupon(cp.id);
                  }
                }}
                className="text-gray-400 hover:text-rose-600 p-1 cursor-pointer"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>

            <div className="bg-gray-50 p-3 rounded-xl space-y-1.5 text-xs text-gray-600 border border-gray-100">
              <div className="flex justify-between">
                <span>الحد الأدنى للطلب:</span>
                <span className="font-bold text-gray-800">{cp.min_order.toLocaleString()} ل.س</span>
              </div>
              <div className="flex justify-between">
                <span>تاريخ الانتهاء:</span>
                <span className="font-mono text-gray-800">{cp.expires_at}</span>
              </div>
              <div className="flex justify-between">
                <span>مرات الاستخدام:</span>
                <span className="font-bold text-amber-600">{cp.usage_count} مرة</span>
              </div>
            </div>

            <div className="flex items-center gap-1.5 text-emerald-600 text-xs font-semibold">
              <CheckCircle2 className="w-3.5 h-3.5" /> الكوبون نشط ومفعل في المتجر
            </div>
          </div>
        ))}
      </div>

      {/* Add Coupon Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm text-gray-900">إنشاء كوبون خصم جديد</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="p-1 rounded-full text-gray-400 hover:bg-gray-100">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddCoupon} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-gray-700 block mb-1">كود الخصم (بالأحرف الإنجليزية):</label>
                <input
                  type="text"
                  required
                  value={code}
                  onChange={(e) => setCode(e.target.value.toUpperCase())}
                  placeholder="مثال: SUMMER25"
                  className="w-full border border-gray-200 rounded-xl p-2.5 font-mono uppercase focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="font-bold text-gray-700 block mb-1">نوع الخصم:</label>
                  <select
                    value={discountType}
                    onChange={(e) => setDiscountType(e.target.value as any)}
                    className="w-full bg-white border border-gray-200 rounded-xl p-2.5"
                  >
                    <option value="percent">نسبة مئوية (%)</option>
                    <option value="fixed">مبلغ ثابت (ل.س)</option>
                  </select>
                </div>

                <div>
                  <label className="font-bold text-gray-700 block mb-1">قيمة الخصم:</label>
                  <input
                    type="number"
                    required
                    min="1"
                    value={discountValue}
                    onChange={(e) => setDiscountValue(Number(e.target.value))}
                    className="w-full border border-gray-200 rounded-xl p-2.5"
                  />
                </div>
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">الحد الأدنى لقيمة الطلب (ل.س):</label>
                <input
                  type="number"
                  required
                  value={minOrder}
                  onChange={(e) => setMinOrder(Number(e.target.value))}
                  className="w-full border border-gray-200 rounded-xl p-2.5"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">تاريخ انتهاء الصلاحية:</label>
                <input
                  type="date"
                  required
                  value={expiresAt}
                  onChange={(e) => setExpiresAt(e.target.value)}
                  className="w-full border border-gray-200 rounded-xl p-2.5"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-100"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  className="bg-amber-600 text-white font-bold px-4 py-1.5 rounded-lg cursor-pointer"
                >
                  حفظ الكوبون
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
