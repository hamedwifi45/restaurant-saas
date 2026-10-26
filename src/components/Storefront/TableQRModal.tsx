import React from 'react';
import { useApp } from '../../context/AppContext';
import { RestaurantTable } from '../../types';
import { X, QrCode, Check, UtensilsCrossed, Users } from 'lucide-react';

interface TableQRModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSelectTable: (table: RestaurantTable) => void;
}

export const TableQRModal: React.FC<TableQRModalProps> = ({
  isOpen,
  onClose,
  onSelectTable,
}) => {
  const { currentRestaurant, tables, scannedTable, setScannedTable } = useApp();

  if (!isOpen) return null;

  const restaurantTables = tables.filter((t) => t.restaurant_id === currentRestaurant.id);

  const handleChoose = (table: RestaurantTable) => {
    setScannedTable(table);
    onSelectTable(table);
    onClose();
  };

  const handleClear = () => {
    setScannedTable(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col">
        {/* Header */}
        <div className="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
          <div className="flex items-center gap-2">
            <QrCode className="w-5 h-5 text-amber-600" />
            <h3 className="font-bold text-sm text-gray-900">محاكاة مسح رمز QR الطاولة</h3>
          </div>
          <button onClick={onClose} className="p-1 rounded-full text-gray-500 hover:bg-gray-200 cursor-pointer">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-5 space-y-4">
          <p className="text-xs text-gray-600 leading-relaxed">
            في الصالة، يقوم العميل بمسح كود QR المثبت على طاولته لفتح المنيو وربط طلبه بالطاولة تلقائياً.
            اختر طاولة لمحاكاة تجربة الجلسة المحلية:
          </p>

          {scannedTable && (
            <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs">
              <div className="flex items-center gap-2 text-emerald-800 font-bold">
                <Check className="w-4 h-4 text-emerald-600" />
                <span>أنت مرتبط حالياً بـ: {scannedTable.table_number}</span>
              </div>
              <button
                onClick={handleClear}
                className="text-rose-600 hover:text-rose-800 font-semibold cursor-pointer text-xs"
              >
                مغادرة الطاولة
              </button>
            </div>
          )}

          <div className="space-y-2 max-h-60 overflow-y-auto">
            {restaurantTables.map((table) => {
              const isSelected = scannedTable?.id === table.id;
              return (
                <div
                  key={table.id}
                  onClick={() => handleChoose(table)}
                  className={`p-3 rounded-xl border flex items-center justify-between cursor-pointer transition-all ${
                    isSelected
                      ? 'border-amber-500 bg-amber-50/80 font-bold'
                      : 'border-gray-200 hover:border-amber-300 hover:bg-gray-50'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs">
                      <UtensilsCrossed className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="text-xs font-bold text-gray-900">{table.table_number}</div>
                      <div className="text-[10px] text-gray-500 flex items-center gap-1">
                        <Users className="w-3 h-3" /> {table.seats} مقاعد
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <span
                      className={`text-[10px] px-2 py-0.5 rounded-full font-bold ${
                        table.status === 'available'
                          ? 'bg-emerald-100 text-emerald-800'
                          : table.status === 'occupied'
                          ? 'bg-rose-100 text-rose-800'
                          : 'bg-amber-100 text-amber-800'
                      }`}
                    >
                      {table.status === 'available' ? 'متاحة' : table.status === 'occupied' ? 'مشغولة' : 'محجوزة'}
                    </span>
                    <button className="bg-amber-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-lg">
                      مسح والطلب
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
};
