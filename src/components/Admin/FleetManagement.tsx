import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Driver } from '../../types';
import { Bike, Phone, Star, Plus, CheckCircle, XCircle, MapPin, X } from 'lucide-react';

export const FleetManagement: React.FC = () => {
  const { currentRestaurant, drivers, saveDriver, toggleDriverStatus } = useApp();

  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [driverName, setDriverName] = useState('');
  const [driverPhone, setDriverPhone] = useState('');
  const [vehicleType, setVehicleType] = useState('دراجة نارية');
  const [plateNumber, setPlateNumber] = useState('');

  const restDrivers = drivers.filter((d) => d.restaurant_id === currentRestaurant.id);

  const handleAddDriver = (e: React.FormEvent) => {
    e.preventDefault();
    if (!driverName.trim() || !driverPhone.trim()) return;

    saveDriver({
      restaurant_id: currentRestaurant.id,
      name: driverName,
      phone: driverPhone,
      vehicle_type: vehicleType,
      plate_number: plateNumber || 'دمشق 0000',
    });

    setDriverName('');
    setDriverPhone('');
    setPlateNumber('');
    setIsAddModalOpen(false);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-black text-gray-900">أسطول ومندوبو التوصيل</h2>
          <p className="text-xs text-gray-500">
            متابعة مناديب التوصيل التابعين لـ {currentRestaurant.name_ar} وتقييماتهم والطلبات النشطة.
          </p>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
        >
          <Plus className="w-4 h-4" />
          <span>إضافة مندوب توصيل جديد</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {restDrivers.map((drv) => (
          <div
            key={drv.id}
            className="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-amber-300 transition-all"
          >
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black text-lg shadow-xs">
                  {drv.name.charAt(0)}
                </div>
                <div>
                  <h3 className="font-bold text-sm text-gray-900">{drv.name}</h3>
                  <div className="text-[11px] text-gray-500 flex items-center gap-1 font-mono" dir="ltr">
                    <Phone className="w-3 h-3" /> {drv.phone}
                  </div>
                </div>
              </div>

              <button
                onClick={() => toggleDriverStatus(drv.id)}
                className={`text-[10px] font-bold px-2.5 py-0.5 rounded-full cursor-pointer transition-colors ${
                  drv.is_active
                    ? 'bg-emerald-100 text-emerald-800'
                    : 'bg-gray-100 text-gray-600'
                }`}
              >
                {drv.is_active ? 'نشط ومتاح' : 'في استراحة'}
              </button>
            </div>

            <div className="bg-gray-50 p-3 rounded-xl space-y-2 text-xs border border-gray-100">
              <div className="flex justify-between text-gray-600">
                <span>وسيلة النقل:</span>
                <span className="font-bold text-gray-800">{drv.vehicle_type}</span>
              </div>
              <div className="flex justify-between text-gray-600">
                <span>رقم اللوحة:</span>
                <span className="font-mono font-bold text-gray-800">{drv.plate_number}</span>
              </div>
              <div className="flex justify-between text-gray-600">
                <span>الطلبات المسندة حالياً:</span>
                <span className="font-bold text-amber-600">{drv.active_orders_count} طلبات</span>
              </div>
            </div>

            <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
              <div className="flex items-center gap-1 text-amber-500 font-bold">
                <Star className="w-4 h-4 fill-amber-400" />
                <span>{drv.rating} / 5</span>
              </div>
              <a
                href={`tel:${drv.phone}`}
                className="text-amber-600 hover:text-amber-700 font-bold flex items-center gap-1 text-xs"
              >
                <Phone className="w-3 h-3" /> اتصال مباشر
              </a>
            </div>
          </div>
        ))}
      </div>

      {/* Add Driver Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm text-gray-900">إضافة مندوب توصيل جديد</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="p-1 rounded-full text-gray-400 hover:bg-gray-100">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddDriver} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-gray-700 block mb-1">اسم المندوب:</label>
                <input
                  type="text"
                  required
                  value={driverName}
                  onChange={(e) => setDriverName(e.target.value)}
                  placeholder="مثال: يامن الكردي"
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">رقم هاتف المندوب:</label>
                <input
                  type="tel"
                  required
                  value={driverPhone}
                  onChange={(e) => setDriverPhone(e.target.value)}
                  placeholder="09xxxxxxxx"
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">نوع المركبة:</label>
                <select
                  value={vehicleType}
                  onChange={(e) => setVehicleType(e.target.value)}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 bg-white"
                >
                  <option value="دراجة نارية">دراجة نارية</option>
                  <option value="سيارة صغيرة">سيارة صغيرة</option>
                  <option value="فان نقل طعام">فان نقل طعام</option>
                  <option value="سكوتر كهربائي">سكوتر كهربائي</option>
                </select>
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">رقم اللوحة:</label>
                <input
                  type="text"
                  value={plateNumber}
                  onChange={(e) => setPlateNumber(e.target.value)}
                  placeholder="مثال: دمشق 5412"
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
                  حفظ المندوب
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
