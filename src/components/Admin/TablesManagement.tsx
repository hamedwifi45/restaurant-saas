import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { RestaurantTable, TableStatus } from '../../types';
import {
  UtensilsCrossed,
  Plus,
  QrCode,
  Users,
  CheckCircle2,
  Clock,
  Printer,
  X,
  AlertCircle,
} from 'lucide-react';

export const TablesManagement: React.FC = () => {
  const { currentRestaurant, tables, saveTable, updateTableStatus } = useApp();

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [selectedQRTable, setSelectedQRTable] = useState<RestaurantTable | null>(null);

  const [tableNumber, setTableNumber] = useState('');
  const [seats, setSeats] = useState(4);

  const restTables = tables.filter((t) => t.restaurant_id === currentRestaurant.id);

  const handleAddTable = (e: React.FormEvent) => {
    e.preventDefault();
    if (!tableNumber.trim()) return;

    saveTable({
      table_number: tableNumber,
      seats,
      restaurant_id: currentRestaurant.id,
    });

    setTableNumber('');
    setSeats(4);
    setIsAddModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Top Bar */}
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-black text-gray-900">إدارة طاولات الصالة ورموز QR</h2>
          <p className="text-xs text-gray-500">
            تحكم في طاولات {currentRestaurant.name_ar}، وقم بتوليد رموز QR قابلة للطباعة لطلب الزبائن من الصالة.
          </p>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>إضافة طاولة جديدة</span>
        </button>
      </div>

      {/* Tables Grid Layout */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {restTables.map((tbl) => (
          <div
            key={tbl.id}
            className={`bg-white rounded-2xl border-2 p-4 shadow-xs flex flex-col justify-between space-y-4 transition-all ${
              tbl.status === 'occupied'
                ? 'border-rose-400 bg-rose-50/20'
                : tbl.status === 'reserved'
                ? 'border-amber-400 bg-amber-50/20'
                : 'border-emerald-400 bg-emerald-50/20'
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-3">
                <div
                  className={`w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm ${
                    tbl.status === 'occupied'
                      ? 'bg-rose-100 text-rose-700'
                      : tbl.status === 'reserved'
                      ? 'bg-amber-100 text-amber-700'
                      : 'bg-emerald-100 text-emerald-700'
                  }`}
                >
                  <UtensilsCrossed className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-bold text-sm text-gray-900">{tbl.table_number}</h3>
                  <div className="text-[11px] text-gray-500 flex items-center gap-1">
                    <Users className="w-3 h-3" /> {tbl.seats} مقاعد
                  </div>
                </div>
              </div>

              <span
                className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                  tbl.status === 'occupied'
                    ? 'bg-rose-100 text-rose-800'
                    : tbl.status === 'reserved'
                    ? 'bg-amber-100 text-amber-800'
                    : 'bg-emerald-100 text-emerald-800'
                }`}
              >
                {tbl.status === 'available' ? 'متاحة' : tbl.status === 'occupied' ? 'مشغولة' : 'محجوزة'}
              </span>
            </div>

            {/* Status Switcher & QR Button */}
            <div className="pt-2 border-t border-gray-100 space-y-2">
              <div className="flex items-center justify-between text-xs">
                <span className="text-gray-500 text-[11px]">تغيير الحالة:</span>
                <select
                  value={tbl.status}
                  onChange={(e) => updateTableStatus(tbl.id, e.target.value as TableStatus)}
                  className="bg-white border border-gray-200 rounded-lg p-1 text-[11px] font-bold"
                >
                  <option value="available">متاحة</option>
                  <option value="occupied">مشغولة حالياً</option>
                  <option value="reserved">محجوزة</option>
                </select>
              </div>

              <button
                onClick={() => setSelectedQRTable(tbl)}
                className="w-full bg-gray-50 hover:bg-amber-50 text-gray-700 hover:text-amber-700 border border-gray-200 hover:border-amber-300 py-1.5 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
              >
                <QrCode className="w-4 h-4 text-amber-600" />
                <span>عرض وطباعة كود QR</span>
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Add Table Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm text-gray-900">إضافة طاولة جديدة</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="p-1 rounded-full text-gray-400 hover:bg-gray-100">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddTable} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-gray-700 block mb-1">اسم أو رقم الطاولة:</label>
                <input
                  type="text"
                  required
                  value={tableNumber}
                  onChange={(e) => setTableNumber(e.target.value)}
                  placeholder="مثال: طاولة 6 أو VIP 2"
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">عدد المقاعد:</label>
                <input
                  type="number"
                  min="1"
                  max="20"
                  required
                  value={seats}
                  onChange={(e) => setSeats(Number(e.target.value))}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
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
                  حفظ الطاولة
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Printable QR Code Modal */}
      {selectedQRTable && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col text-center">
            <div className="no-print p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
              <span className="font-bold text-xs text-gray-700">ستاند طاولة QR</span>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => window.print()}
                  className="bg-amber-600 text-white px-3 py-1 rounded-lg text-xs font-bold flex items-center gap-1 cursor-pointer"
                >
                  <Printer className="w-3.5 h-3.5" /> طباعة
                </button>
                <button
                  onClick={() => setSelectedQRTable(null)}
                  className="p-1 rounded-full text-gray-500 hover:bg-gray-200 cursor-pointer"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            <div className="printable-receipt p-8 space-y-4 bg-white text-gray-900">
              <div className="space-y-1">
                <div className="font-extrabold text-lg text-gray-900">{currentRestaurant.name_ar}</div>
                <div className="text-xs text-amber-600 font-bold">
                  {selectedQRTable.table_number}
                </div>
              </div>

              <div className="w-44 h-44 mx-auto border-4 border-gray-900 rounded-2xl p-3 flex flex-col items-center justify-center bg-gray-50">
                <QrCode className="w-32 h-32 text-gray-900" />
              </div>

              <div className="space-y-1">
                <p className="text-xs font-black text-gray-800">امسح الكود لطلب طعامك مباشرة</p>
                <p className="text-[10px] text-gray-500">
                  تصفح المنيو، اختر أطباقك المفضلة وسنحضرها فوراً إلى طاولتك.
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
