import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { DeliveryType, PaymentMethod, Order } from '../../types';
import {
  X,
  Trash2,
  Plus,
  Minus,
  Bike,
  ShoppingBag,
  UtensilsCrossed,
  Tag,
  CreditCard,
  Banknote,
  Building,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
} from 'lucide-react';
import confetti from 'canvas-confetti';

interface StorefrontCartModalProps {
  isOpen: boolean;
  onClose: () => void;
  onOrderCreated: (order: Order) => void;
}

export const StorefrontCartModal: React.FC<StorefrontCartModalProps> = ({
  isOpen,
  onClose,
  onOrderCreated,
}) => {
  const {
    currentRestaurant,
    cart,
    updateCartQuantity,
    removeFromCart,
    clearCart,
    appliedCoupon,
    applyCoupon,
    removeCoupon,
    createOrder,
    tables,
    scannedTable,
  } = useApp();

  const [deliveryType, setDeliveryType] = useState<DeliveryType>(
    scannedTable ? 'dine_in' : 'delivery'
  );
  const [selectedTableId, setSelectedTableId] = useState<string>(
    scannedTable ? scannedTable.id : (tables.find((t) => t.restaurant_id === currentRestaurant.id)?.id || '')
  );
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('cash_on_delivery');
  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('');
  const [customerAddress, setCustomerAddress] = useState('');
  const [orderNotes, setOrderNotes] = useState('');

  const [couponCodeInput, setCouponCodeInput] = useState('');
  const [couponMsg, setCouponMsg] = useState<{ text: string; isError: boolean } | null>(null);

  if (!isOpen) return null;

  // Pricing calculations
  const subtotal = cart.reduce((acc, item) => {
    const price = item.variant ? item.variant.price : item.product.price;
    return acc + price * item.quantity;
  }, 0);

  const deliveryFee = deliveryType === 'delivery' ? currentRestaurant.delivery_fee : 0;

  let discount = 0;
  if (appliedCoupon) {
    if (appliedCoupon.discount_type === 'percent') {
      discount = (subtotal * appliedCoupon.discount_value) / 100;
      if (appliedCoupon.max_discount && discount > appliedCoupon.max_discount) {
        discount = appliedCoupon.max_discount;
      }
    } else {
      discount = appliedCoupon.discount_value;
    }
  }

  const total = Math.max(0, subtotal + deliveryFee - discount);

  const handleApplyCoupon = (e: React.FormEvent) => {
    e.preventDefault();
    if (!couponCodeInput.trim()) return;
    const res = applyCoupon(couponCodeInput.trim(), subtotal);
    setCouponMsg({ text: res.message, isError: !res.success });
  };

  const handleSubmitOrder = (e: React.FormEvent) => {
    e.preventDefault();
    if (cart.length === 0) return;

    if (!customerName.trim()) {
      alert('الرجاء إدخال اسم العميل');
      return;
    }
    if (!customerPhone.trim()) {
      alert('الرجاء إدخال رقم الهاتف');
      return;
    }
    if (deliveryType === 'delivery' && !customerAddress.trim()) {
      alert('الرجاء إدخال عنوان التوصيل بالتفصيل');
      return;
    }

    const currentTable = tables.find((t) => t.id === selectedTableId);

    const orderItems = cart.map((item, idx) => ({
      id: `item-${Date.now()}-${idx}`,
      product_id: item.product.id,
      product_name: item.product.name,
      product_name_ar: item.product.name_ar,
      variant_name: item.variant?.name_ar || item.variant?.name,
      quantity: item.quantity,
      unit_price: item.variant ? item.variant.price : item.product.price,
      total_price: (item.variant ? item.variant.price : item.product.price) * item.quantity,
      notes: item.notes,
    }));

    const order = createOrder({
      customer_name: customerName,
      customer_phone: customerPhone,
      customer_address: deliveryType === 'delivery' ? customerAddress : undefined,
      delivery_type: deliveryType,
      restaurant_table_id: deliveryType === 'dine_in' ? selectedTableId : undefined,
      table_number: deliveryType === 'dine_in' ? currentTable?.table_number : undefined,
      payment_method: paymentMethod,
      subtotal,
      delivery_fee: deliveryFee,
      discount,
      total,
      coupon_code: appliedCoupon ? appliedCoupon.code : undefined,
      notes: orderNotes,
      items: orderItems,
    });

    try {
      confetti({
        particleCount: 80,
        spread: 60,
        origin: { y: 0.6 },
      });
    } catch (err) {
      // ignore
    }

    onClose();
    onOrderCreated(order);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-end bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white h-full w-full max-w-xl shadow-2xl flex flex-col justify-between overflow-hidden">
        {/* Header */}
        <div className="p-4 sm:p-5 border-b border-gray-200 flex items-center justify-between bg-gray-50/80">
          <div className="flex items-center gap-2">
            <ShoppingBag className="w-5 h-5 text-amber-600" />
            <h2 className="font-bold text-lg text-gray-900">سلة الطلبات ({cart.length} أصناف)</h2>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-full hover:bg-gray-200 text-gray-600 cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Content */}
        <div className="grow overflow-y-auto p-4 sm:p-6 space-y-6">
          {cart.length === 0 ? (
            <div className="py-16 text-center space-y-3">
              <ShoppingBag className="w-12 h-12 text-gray-300 mx-auto" />
              <p className="font-bold text-gray-700">سلة التسوق فارغة حالياً</p>
              <p className="text-xs text-gray-400">تصفح القائمة وأضف وجباتك المفضلة لبدء الطلب.</p>
              <button
                onClick={onClose}
                className="mt-3 inline-block bg-amber-600 text-white px-5 py-2 rounded-xl text-xs font-bold cursor-pointer hover:bg-amber-700"
              >
                تصفح القائمة
              </button>
            </div>
          ) : (
            <>
              {/* Cart Items List */}
              <div className="space-y-3">
                <div className="flex items-center justify-between text-xs font-bold text-gray-500 pb-1 border-b border-gray-100">
                  <span>الأصناف المختارة</span>
                  <button
                    onClick={clearCart}
                    className="text-rose-500 hover:text-rose-700 flex items-center gap-1 cursor-pointer"
                  >
                    <Trash2 className="w-3.5 h-3.5" /> إفراغ السلة
                  </button>
                </div>

                {cart.map((item, idx) => {
                  const unitPrice = item.variant ? item.variant.price : item.product.price;
                  return (
                    <div
                      key={idx}
                      className="bg-gray-50 rounded-xl p-3 border border-gray-100 flex items-center justify-between gap-3"
                    >
                      <img
                        src={item.product.image}
                        alt={item.product.name_ar}
                        className="w-14 h-14 object-cover rounded-lg shrink-0"
                      />
                      <div className="grow">
                        <div className="font-bold text-xs text-gray-900">{item.product.name_ar}</div>
                        {item.variant && (
                          <div className="text-[11px] text-amber-600 font-semibold">
                            {item.variant.name_ar || item.variant.name}
                          </div>
                        )}
                        {item.notes && (
                          <div className="text-[10px] text-gray-400 italic">ملاحظة: {item.notes}</div>
                        )}
                        <div className="text-xs font-bold text-gray-700 mt-1">
                          {(unitPrice * item.quantity).toLocaleString()} ل.س
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
                        <div className="flex items-center border border-gray-200 bg-white rounded-lg shadow-2xs">
                          <button
                            onClick={() => updateCartQuantity(idx, item.quantity - 1)}
                            className="p-1 text-gray-600 hover:text-gray-900 cursor-pointer"
                          >
                            <Minus className="w-3.5 h-3.5" />
                          </button>
                          <span className="px-2 text-xs font-bold min-w-5 text-center">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateCartQuantity(idx, item.quantity + 1)}
                            className="p-1 text-gray-600 hover:text-gray-900 cursor-pointer"
                          >
                            <Plus className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        <button
                          onClick={() => removeFromCart(idx)}
                          className="text-gray-400 hover:text-rose-500 p-1 cursor-pointer"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Delivery Type Option */}
              <div className="space-y-2 pt-2 border-t border-gray-100">
                <label className="text-xs font-bold text-gray-800 block">طريقة الاستلام:</label>
                <div className="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    onClick={() => setDeliveryType('delivery')}
                    className={`p-3 rounded-xl border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
                      deliveryType === 'delivery'
                        ? 'border-amber-500 bg-amber-50/80 text-amber-800 font-bold'
                        : 'border-gray-200 hover:border-gray-300 text-gray-700'
                    }`}
                  >
                    <Bike className="w-5 h-5 text-amber-600" />
                    <span className="text-xs">توصيل منزلي</span>
                    <span className="text-[10px] text-gray-500">
                      +{currentRestaurant.delivery_fee.toLocaleString()} ل.س
                    </span>
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeliveryType('takeaway')}
                    className={`p-3 rounded-xl border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
                      deliveryType === 'takeaway'
                        ? 'border-amber-500 bg-amber-50/80 text-amber-800 font-bold'
                        : 'border-gray-200 hover:border-gray-300 text-gray-700'
                    }`}
                  >
                    <ShoppingBag className="w-5 h-5 text-amber-600" />
                    <span className="text-xs">استلام (سفري)</span>
                    <span className="text-[10px] text-emerald-600 font-semibold">بدون رسوم</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeliveryType('dine_in')}
                    className={`p-3 rounded-xl border text-center flex flex-col items-center gap-1.5 transition-all cursor-pointer ${
                      deliveryType === 'dine_in'
                        ? 'border-amber-500 bg-amber-50/80 text-amber-800 font-bold'
                        : 'border-gray-200 hover:border-gray-300 text-gray-700'
                    }`}
                  >
                    <UtensilsCrossed className="w-5 h-5 text-amber-600" />
                    <span className="text-xs">تناول بالمطعم</span>
                    <span className="text-[10px] text-emerald-600 font-semibold">طاولة محددة</span>
                  </button>
                </div>
              </div>

              {/* Dine-in Table Selection */}
              {deliveryType === 'dine_in' && (
                <div className="bg-amber-50/60 border border-amber-200 rounded-xl p-3.5 space-y-2">
                  <label className="text-xs font-bold text-amber-900 block">
                    حدد الطاولة في الصالة:
                  </label>
                  <select
                    value={selectedTableId}
                    onChange={(e) => setSelectedTableId(e.target.value)}
                    className="w-full bg-white border border-amber-300 rounded-lg p-2 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-amber-500"
                  >
                    {tables
                      .filter((t) => t.restaurant_id === currentRestaurant.id)
                      .map((t) => (
                        <option key={t.id} value={t.id}>
                          {t.table_number} ({t.seats} مقاعد - حالة:{' '}
                          {t.status === 'available'
                            ? 'متاحة'
                            : t.status === 'occupied'
                            ? 'مشغولة حالياً'
                            : 'محجوزة'}
                          )
                        </option>
                      ))}
                  </select>
                </div>
              )}

              {/* Customer Contact & Details */}
              <div className="space-y-3 pt-2 border-t border-gray-100">
                <div className="text-xs font-bold text-gray-800">بيانات العميل:</div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <div>
                    <label className="text-[11px] text-gray-500 block mb-1">الاسم الكامل:</label>
                    <input
                      type="text"
                      required
                      value={customerName}
                      onChange={(e) => setCustomerName(e.target.value)}
                      placeholder="مثال: أحمد الحلبي"
                      className="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                  <div>
                    <label className="text-[11px] text-gray-500 block mb-1">رقم الموبايل:</label>
                    <input
                      type="tel"
                      required
                      value={customerPhone}
                      onChange={(e) => setCustomerPhone(e.target.value)}
                      placeholder="09xxxxxxxx"
                      className="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                </div>

                {deliveryType === 'delivery' && (
                  <div>
                    <label className="text-[11px] text-gray-500 block mb-1">عنوان التوصيل بالتفصيل:</label>
                    <input
                      type="text"
                      required
                      value={customerAddress}
                      onChange={(e) => setCustomerAddress(e.target.value)}
                      placeholder="المنطقة، الشارع، البناء، رقم الطابق"
                      className="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500"
                    />
                  </div>
                )}

                <div>
                  <label className="text-[11px] text-gray-500 block mb-1">ملاحظات على الطلب:</label>
                  <input
                    type="text"
                    value={orderNotes}
                    onChange={(e) => setOrderNotes(e.target.value)}
                    placeholder="كاتشب زيادة، كولا بارد جداً، الاتصال قبل الصعود..."
                    className="w-full border border-gray-200 rounded-xl p-2 text-xs focus:ring-2 focus:ring-amber-500"
                  />
                </div>
              </div>

              {/* Payment Method */}
              <div className="space-y-2 pt-2 border-t border-gray-100">
                <label className="text-xs font-bold text-gray-800 block">طريقة الدفع:</label>
                <div className="grid grid-cols-3 gap-2">
                  <label
                    onClick={() => setPaymentMethod('cash_on_delivery')}
                    className={`p-2.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all ${
                      paymentMethod === 'cash_on_delivery'
                        ? 'border-amber-500 bg-amber-50/70 font-bold text-amber-900'
                        : 'border-gray-200 text-gray-600'
                    }`}
                  >
                    <Banknote className="w-4 h-4 text-emerald-600" />
                    <span className="text-[11px]">كاش عند الاستلام</span>
                  </label>

                  <label
                    onClick={() => setPaymentMethod('card')}
                    className={`p-2.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all ${
                      paymentMethod === 'card'
                        ? 'border-amber-500 bg-amber-50/70 font-bold text-amber-900'
                        : 'border-gray-200 text-gray-600'
                    }`}
                  >
                    <CreditCard className="w-4 h-4 text-blue-600" />
                    <span className="text-[11px]">بطاقة / POS</span>
                  </label>

                  <label
                    onClick={() => setPaymentMethod('bank_transfer')}
                    className={`p-2.5 rounded-xl border text-center flex flex-col items-center gap-1 cursor-pointer transition-all ${
                      paymentMethod === 'bank_transfer'
                        ? 'border-amber-500 bg-amber-50/70 font-bold text-amber-900'
                        : 'border-gray-200 text-gray-600'
                    }`}
                  >
                    <Building className="w-4 h-4 text-purple-600" />
                    <span className="text-[11px]">تحويل بنكي</span>
                  </label>
                </div>

                {paymentMethod === 'bank_transfer' && (
                  <div className="p-3 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-900 space-y-1">
                    <span className="font-bold">بيانات الحساب البنكي للمطعم:</span>
                    <p className="font-mono text-[11px]">{currentRestaurant.bank_details}</p>
                    <p className="text-[10px] text-purple-700">
                      سيتم مراجعة إشعار التحويل البنكي وتأكيد الطلب فوراً.
                    </p>
                  </div>
                )}
              </div>

              {/* Coupon Box */}
              <div className="space-y-2 pt-2 border-t border-gray-100">
                <label className="text-xs font-bold text-gray-800 flex items-center gap-1">
                  <Tag className="w-3.5 h-3.5 text-amber-600" /> كود الخصم (كوبون):
                </label>
                {appliedCoupon ? (
                  <div className="flex items-center justify-between p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <div className="flex items-center gap-2 text-xs font-bold text-emerald-800">
                      <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                      <span>الكوبون المفعل: {appliedCoupon.code}</span>
                      <span className="text-[10px] bg-emerald-100 px-1.5 py-0.5 rounded">
                        -{discount.toLocaleString()} ل.س
                      </span>
                    </div>
                    <button
                      onClick={removeCoupon}
                      className="text-rose-500 hover:text-rose-700 text-xs font-medium cursor-pointer"
                    >
                      إلغاء
                    </button>
                  </div>
                ) : (
                  <form onSubmit={handleApplyCoupon} className="flex gap-2">
                    <input
                      type="text"
                      value={couponCodeInput}
                      onChange={(e) => setCouponCodeInput(e.target.value)}
                      placeholder="أدخل الكود (مثل: BASHA20)"
                      className="grow uppercase border border-gray-200 rounded-xl p-2 text-xs focus:ring-2 focus:ring-amber-500 font-mono"
                    />
                    <button
                      type="submit"
                      className="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-colors"
                    >
                      تطبيق
                    </button>
                  </form>
                )}

                {couponMsg && (
                  <div
                    className={`text-[11px] flex items-center gap-1 font-medium ${
                      couponMsg.isError ? 'text-rose-600' : 'text-emerald-600'
                    }`}
                  >
                    {couponMsg.isError ? (
                      <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                    ) : (
                      <CheckCircle2 className="w-3.5 h-3.5 shrink-0" />
                    )}
                    {couponMsg.text}
                  </div>
                )}
              </div>
            </>
          )}
        </div>

        {/* Footer with Price Breakdown & Action */}
        {cart.length > 0 && (
          <div className="p-4 sm:p-5 bg-gray-50 border-t border-gray-200 space-y-3">
            <div className="space-y-1.5 text-xs">
              <div className="flex items-center justify-between text-gray-600">
                <span>المجموع الفرعي للأصناف:</span>
                <span className="font-bold">{subtotal.toLocaleString()} ل.س</span>
              </div>
              {deliveryType === 'delivery' && (
                <div className="flex items-center justify-between text-gray-600">
                  <span>رسوم التوصيل:</span>
                  <span className="font-bold">{deliveryFee.toLocaleString()} ل.س</span>
                </div>
              )}
              {discount > 0 && (
                <div className="flex items-center justify-between text-emerald-600 font-bold">
                  <span>قيمة الخصم:</span>
                  <span>-{discount.toLocaleString()} ل.س</span>
                </div>
              )}
              <div className="flex items-center justify-between text-base font-black text-gray-900 pt-2 border-t border-gray-200">
                <span>المجموع الكلي:</span>
                <span className="text-amber-600 font-extrabold">{total.toLocaleString()} ل.س</span>
              </div>
            </div>

            <button
              onClick={handleSubmitOrder}
              className="w-full bg-amber-600 hover:bg-amber-500 text-white font-extrabold py-3.5 px-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer text-sm active:scale-98"
            >
              <span>تأكيد وإنشاء الطلب الآن</span>
              <ArrowRight className="w-4 h-4 rotate-180" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
