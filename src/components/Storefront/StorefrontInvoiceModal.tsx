import React from 'react';
import { useApp } from '../../context/AppContext';
import { Order } from '../../types';
import { X, Printer, QrCode } from 'lucide-react';

interface StorefrontInvoiceModalProps {
  order: Order | null;
  onClose: () => void;
}

export const StorefrontInvoiceModal: React.FC<StorefrontInvoiceModalProps> = ({
  order,
  onClose,
}) => {
  const { currentRestaurant } = useApp();

  if (!order) return null;

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-xl w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[92vh]">
        {/* Top Control Bar (Hidden on print) */}
        <div className="no-print p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="font-bold text-sm text-gray-800">فاتورة طلب رسمية</span>
            <span className="font-mono text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold">
              {order.tracking_code}
            </span>
          </div>

          <div className="flex items-center gap-2">
            <button
              onClick={handlePrint}
              className="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>طباعة الفاتورة</span>
            </button>
            <button
              onClick={onClose}
              className="p-1 rounded-full text-gray-500 hover:bg-gray-200 cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Invoice Printable Sheet */}
        <div className="printable-receipt p-6 sm:p-8 overflow-y-auto space-y-6 text-gray-900 bg-white">
          {/* Header */}
          <div className="text-center space-y-1.5 border-b border-dashed border-gray-300 pb-5">
            <h1 className="text-2xl font-black text-gray-900">{currentRestaurant.name_ar}</h1>
            <p className="text-xs text-gray-500">{currentRestaurant.description_ar}</p>
            <div className="text-[11px] text-gray-600 flex items-center justify-center gap-3 pt-1">
              <span>{currentRestaurant.address}</span>
              <span>•</span>
              <span dir="ltr">{currentRestaurant.phone}</span>
            </div>
            <div className="inline-block border border-gray-800 px-3 py-0.5 text-xs font-bold uppercase tracking-wider rounded mt-2">
              فاتورة مبيعات ضريبية مبسطة
            </div>
          </div>

          {/* Invoice Meta Grid */}
          <div className="grid grid-cols-2 gap-3 text-xs bg-gray-50 p-3.5 rounded-xl border border-gray-100">
            <div>
              <span className="text-gray-400 block text-[10px]">رقم الفاتورة:</span>
              <span className="font-mono font-bold text-gray-800">INV-{order.tracking_code}</span>
            </div>
            <div>
              <span className="text-gray-400 block text-[10px]">تاريخ ووقت الإصدار:</span>
              <span className="font-medium text-gray-800">
                {new Date(order.created_at).toLocaleString('ar-SY')}
              </span>
            </div>
            <div>
              <span className="text-gray-400 block text-[10px]">اسم العميل:</span>
              <span className="font-bold text-gray-800">{order.customer_name}</span>
            </div>
            <div>
              <span className="text-gray-400 block text-[10px]">نوع الطلب:</span>
              <span className="font-bold text-gray-800">
                {order.delivery_type === 'delivery'
                  ? 'توصيل منزلي'
                  : order.delivery_type === 'dine_in'
                  ? `طاولة محلي (${order.table_number || 'صالة'})`
                  : 'استلام سفري'}
              </span>
            </div>
            {order.customer_address && (
              <div className="col-span-2">
                <span className="text-gray-400 block text-[10px]">عنوان التوصيل:</span>
                <span className="text-gray-700">{order.customer_address}</span>
              </div>
            )}
          </div>

          {/* Items Table */}
          <div>
            <table className="w-full text-xs text-right border-collapse">
              <thead>
                <tr className="border-b-2 border-gray-900 font-bold text-gray-800">
                  <th className="py-2">الصنف</th>
                  <th className="py-2 text-center">الكمية</th>
                  <th className="py-2 text-left">السعر الإفرادي</th>
                  <th className="py-2 text-left">الإجمالي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {order.items.map((item, idx) => (
                  <tr key={idx}>
                    <td className="py-2.5">
                      <div className="font-bold text-gray-900">{item.product_name_ar}</div>
                      {item.variant_name && (
                        <div className="text-[10px] text-gray-500 font-semibold">
                          ({item.variant_name})
                        </div>
                      )}
                    </td>
                    <td className="py-2.5 text-center font-bold">{item.quantity}</td>
                    <td className="py-2.5 text-left font-mono">{item.unit_price.toLocaleString()}</td>
                    <td className="py-2.5 text-left font-mono font-bold">
                      {item.total_price.toLocaleString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Totals Section */}
          <div className="border-t-2 border-gray-900 pt-3 space-y-1.5 text-xs">
            <div className="flex justify-between text-gray-600">
              <span>المجموع الفرعي الخاضع للضريبة:</span>
              <span className="font-mono font-bold">{order.subtotal.toLocaleString()} ل.س</span>
            </div>
            {order.delivery_fee > 0 && (
              <div className="flex justify-between text-gray-600">
                <span>أجور التوصيل والنقل:</span>
                <span className="font-mono font-bold">{order.delivery_fee.toLocaleString()} ل.س</span>
              </div>
            )}
            {order.discount > 0 && (
              <div className="flex justify-between text-emerald-700 font-bold">
                <span>الخصم المطبق ({order.coupon_code || 'عرض'}):</span>
                <span className="font-mono">-{order.discount.toLocaleString()} ل.س</span>
              </div>
            )}
            <div className="flex justify-between text-base font-black text-gray-900 pt-2 border-t border-gray-300">
              <span>المبلغ الإجمالي المستحق:</span>
              <span className="font-mono text-amber-600 font-black">
                {order.total.toLocaleString()} ل.س
              </span>
            </div>
            <div className="flex justify-between text-[11px] text-gray-500 pt-1">
              <span>طريقة الدفع:</span>
              <span className="font-bold text-gray-700">
                {order.payment_method === 'cash_on_delivery'
                  ? 'نقداً عند الاستلام'
                  : order.payment_method === 'card'
                  ? 'بطاقة ائتمان / دفع إلكتروني'
                  : 'تحويل بنكي مباشر'}
              </span>
            </div>
          </div>

          {/* Footer with QR representation */}
          <div className="pt-4 border-t border-dashed border-gray-300 text-center space-y-2">
            <div className="flex items-center justify-center gap-2 text-gray-700 font-mono text-[10px]">
              <QrCode className="w-10 h-10 text-gray-800" />
            </div>
            <p className="text-[11px] font-bold text-gray-800">
              شكراً لاختياركم {currentRestaurant.name_ar}!
            </p>
            <p className="text-[10px] text-gray-400">
              تم إصدار هذه الفاتورة إلكترونياً وهي معتمدة لجميع الأغراض المحاسبية.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};
