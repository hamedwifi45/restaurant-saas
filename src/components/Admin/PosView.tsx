import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Product, ProductVariant, Order, DeliveryType, PaymentMethod } from '../../types';
import {
  UtensilsCrossed,
  Search,
  Plus,
  Minus,
  Trash2,
  Printer,
  CheckCircle,
  CreditCard,
  Banknote,
  Users,
  ShoppingBag,
  Bike,
  Sparkles,
} from 'lucide-react';
import confetti from 'canvas-confetti';

interface PosViewProps {
  onOpenReceipt: (order: Order) => void;
}

interface PosCartItem {
  product: Product;
  variant?: ProductVariant;
  quantity: number;
  notes?: string;
}

export const PosView: React.FC<PosViewProps> = ({ onOpenReceipt }) => {
  const { currentRestaurant, categories, products, tables, createOrder } = useApp();

  const [selectedCatId, setSelectedCatId] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [posCart, setPosCart] = useState<PosCartItem[]>([]);
  const [posDeliveryType, setPosDeliveryType] = useState<DeliveryType>('dine_in');
  const [selectedTableId, setSelectedTableId] = useState<string>(
    tables.find((t) => t.restaurant_id === currentRestaurant.id)?.id || ''
  );
  const [customerName, setCustomerName] = useState('زبون مباشر (صالة)');
  const [posPaymentMethod, setPosPaymentMethod] = useState<PaymentMethod>('cash_on_delivery');
  const [discountAmount, setDiscountAmount] = useState<number>(0);
  const [lastCreatedOrder, setLastCreatedOrder] = useState<Order | null>(null);

  const restCategories = categories.filter((c) => c.restaurant_id === currentRestaurant.id);
  const restProducts = products.filter(
    (p) => p.restaurant_id === currentRestaurant.id && p.is_available
  );
  const restTables = tables.filter((t) => t.restaurant_id === currentRestaurant.id);

  const filteredProducts = restProducts.filter((p) => {
    const matchCat = selectedCatId === 'all' || p.category_id === selectedCatId;
    const matchSearch =
      searchQuery.trim() === '' ||
      p.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchCat && matchSearch;
  });

  const addToPosCart = (product: Product, variant?: ProductVariant) => {
    setPosCart((prev) => {
      const idx = prev.findIndex(
        (item) => item.product.id === product.id && item.variant?.name === variant?.name
      );
      if (idx > -1) {
        const next = [...prev];
        next[idx].quantity += 1;
        return next;
      }
      return [...prev, { product, variant, quantity: 1 }];
    });
  };

  const updateQuantity = (index: number, qty: number) => {
    setPosCart((prev) => {
      if (qty <= 0) return prev.filter((_, i) => i !== index);
      const next = [...prev];
      next[index].quantity = qty;
      return next;
    });
  };

  const subtotal = posCart.reduce((sum, item) => {
    const price = item.variant ? item.variant.price : item.product.price;
    return sum + price * item.quantity;
  }, 0);

  const deliveryFee = posDeliveryType === 'delivery' ? currentRestaurant.delivery_fee : 0;
  const total = Math.max(0, subtotal + deliveryFee - discountAmount);

  const handleCheckout = () => {
    if (posCart.length === 0) return;

    const currentTable = restTables.find((t) => t.id === selectedTableId);

    const items = posCart.map((item, i) => ({
      id: `pos-item-${Date.now()}-${i}`,
      product_id: item.product.id,
      product_name: item.product.name,
      product_name_ar: item.product.name_ar,
      variant_name: item.variant?.name_ar || item.variant?.name,
      quantity: item.quantity,
      unit_price: item.variant ? item.variant.price : item.product.price,
      total_price: (item.variant ? item.variant.price : item.product.price) * item.quantity,
    }));

    const order = createOrder({
      customer_name: customerName,
      customer_phone: '0900000000',
      delivery_type: posDeliveryType,
      restaurant_table_id: posDeliveryType === 'dine_in' ? selectedTableId : undefined,
      table_number: posDeliveryType === 'dine_in' ? currentTable?.table_number : undefined,
      status: 'preparing', // POS orders immediately go to kitchen
      payment_method: posPaymentMethod,
      payment_status: 'paid', // Cashier takes payment immediately
      subtotal,
      delivery_fee: deliveryFee,
      discount: discountAmount,
      total,
      items,
    });

    setLastCreatedOrder(order);
    setPosCart([]);
    setDiscountAmount(0);

    try {
      confetti({ particleCount: 50, spread: 50, origin: { y: 0.7 } });
    } catch (e) {
      // ignore
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-140px)] min-h-[600px] animate-in fade-in duration-200">
      {/* Left/Main Column: Categories & Fast Product Selection (7 cols) */}
      <div className="lg:col-span-7 flex flex-col bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
        {/* Category Buttons Bar */}
        <div className="p-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-2 overflow-x-auto">
          <div className="flex items-center gap-1.5 shrink-0">
            <button
              onClick={() => setSelectedCatId('all')}
              className={`px-3 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                selectedCatId === 'all'
                  ? 'bg-amber-600 text-white shadow-xs'
                  : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'
              }`}
            >
              الكل ({restProducts.length})
            </button>
            {restCategories.map((c) => (
              <button
                key={c.id}
                onClick={() => setSelectedCatId(c.id)}
                className={`px-3 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap ${
                  selectedCatId === c.id
                    ? 'bg-amber-600 text-white shadow-xs'
                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'
                }`}
              >
                {c.name_ar}
              </button>
            ))}
          </div>

          <div className="relative w-44 shrink-0">
            <Search className="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="بحث سريع..."
              className="w-full pr-8 pl-2 py-1.5 bg-white border border-gray-200 rounded-lg text-xs"
            />
          </div>
        </div>

        {/* Products Touch Grid */}
        <div className="p-4 grow overflow-y-auto grid grid-cols-2 sm:grid-cols-3 gap-3">
          {filteredProducts.map((prod) => (
            <div
              key={prod.id}
              className="bg-gray-50 hover:bg-amber-50/60 border border-gray-200 hover:border-amber-400 rounded-xl p-3 flex flex-col justify-between transition-all duration-150 cursor-pointer shadow-2xs group"
              onClick={() => {
                if (prod.variants && prod.variants.length > 0) {
                  addToPosCart(prod, prod.variants[0]);
                } else {
                  addToPosCart(prod);
                }
              }}
            >
              <div className="space-y-1.5">
                <img
                  src={prod.image}
                  alt={prod.name_ar}
                  className="w-full h-24 object-cover rounded-lg group-hover:scale-102 transition-transform"
                />
                <h4 className="font-bold text-xs text-gray-900 group-hover:text-amber-700 line-clamp-1">
                  {prod.name_ar}
                </h4>
              </div>

              <div className="pt-2 flex items-center justify-between border-t border-gray-200/60 mt-2">
                <span className="font-bold text-xs text-amber-600">
                  {prod.price.toLocaleString()} ل.س
                </span>
                <span className="bg-amber-600 text-white p-1 rounded-lg">
                  <Plus className="w-3.5 h-3.5" />
                </span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Right Column: POS Ticket / Register Bill (5 cols) */}
      <div className="lg:col-span-5 flex flex-col bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
        {/* Ticket Header */}
        <div className="p-3.5 bg-gray-900 text-white flex items-center justify-between">
          <div className="flex items-center gap-2">
            <UtensilsCrossed className="w-4 h-4 text-amber-400" />
            <span className="font-bold text-xs">تذكرة طلب الكاشير الحالية</span>
          </div>
          <span className="text-[11px] text-gray-400 font-mono">
            {new Date().toLocaleTimeString('ar-SY')}
          </span>
        </div>

        {/* Order Details & Table / Type selection */}
        <div className="p-3 bg-gray-50 border-b border-gray-200 space-y-2 text-xs">
          <div className="grid grid-cols-3 gap-1.5">
            <button
              onClick={() => setPosDeliveryType('dine_in')}
              className={`p-1.5 rounded-lg border text-center font-bold cursor-pointer text-[11px] flex items-center justify-center gap-1 ${
                posDeliveryType === 'dine_in'
                  ? 'bg-amber-600 text-white border-amber-600'
                  : 'bg-white text-gray-700 border-gray-200'
              }`}
            >
              <Users className="w-3 h-3" /> صالة محلي
            </button>
            <button
              onClick={() => setPosDeliveryType('takeaway')}
              className={`p-1.5 rounded-lg border text-center font-bold cursor-pointer text-[11px] flex items-center justify-center gap-1 ${
                posDeliveryType === 'takeaway'
                  ? 'bg-amber-600 text-white border-amber-600'
                  : 'bg-white text-gray-700 border-gray-200'
              }`}
            >
              <ShoppingBag className="w-3 h-3" /> سفري
            </button>
            <button
              onClick={() => setPosDeliveryType('delivery')}
              className={`p-1.5 rounded-lg border text-center font-bold cursor-pointer text-[11px] flex items-center justify-center gap-1 ${
                posDeliveryType === 'delivery'
                  ? 'bg-amber-600 text-white border-amber-600'
                  : 'bg-white text-gray-700 border-gray-200'
              }`}
            >
              <Bike className="w-3 h-3" /> دليفري
            </button>
          </div>

          <div className="flex items-center gap-2">
            {posDeliveryType === 'dine_in' ? (
              <select
                value={selectedTableId}
                onChange={(e) => setSelectedTableId(e.target.value)}
                className="w-full bg-white border border-gray-200 rounded-lg p-1.5 text-xs font-bold"
              >
                {restTables.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.table_number} ({t.seats} مقاعد) - {t.status === 'occupied' ? 'مشغولة' : 'متاحة'}
                  </option>
                ))}
              </select>
            ) : (
              <input
                type="text"
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                placeholder="اسم العميل أو رقم الهاتف..."
                className="w-full bg-white border border-gray-200 rounded-lg p-1.5 text-xs"
              />
            )}
          </div>
        </div>

        {/* Ticket Items Scroll Area */}
        <div className="p-3 grow overflow-y-auto divide-y divide-gray-100">
          {posCart.length === 0 ? (
            <div className="py-16 text-center text-gray-400 space-y-1">
              <UtensilsCrossed className="w-8 h-8 text-gray-300 mx-auto" />
              <p className="text-xs font-bold">التذكرة فارغة</p>
              <p className="text-[11px]">اضغط على أي وجبة لإضافتها فوراً</p>
            </div>
          ) : (
            posCart.map((item, idx) => {
              const unitPrice = item.variant ? item.variant.price : item.product.price;
              return (
                <div key={idx} className="py-2 flex items-center justify-between gap-2 text-xs">
                  <div className="grow">
                    <div className="font-bold text-gray-900">{item.product.name_ar}</div>
                    {item.variant && (
                      <div className="text-[10px] text-amber-600 font-semibold">
                        {item.variant.name_ar || item.variant.name}
                      </div>
                    )}
                    <div className="text-[11px] text-gray-500 font-mono">
                      {(unitPrice * item.quantity).toLocaleString()} ل.س
                    </div>
                  </div>

                  <div className="flex items-center gap-1.5">
                    <div className="flex items-center border border-gray-200 rounded-lg bg-gray-50">
                      <button
                        onClick={() => updateQuantity(idx, item.quantity - 1)}
                        className="p-1 hover:text-amber-600 cursor-pointer"
                      >
                        <Minus className="w-3 h-3" />
                      </button>
                      <span className="px-2 font-bold text-xs">{item.quantity}</span>
                      <button
                        onClick={() => updateQuantity(idx, item.quantity + 1)}
                        className="p-1 hover:text-amber-600 cursor-pointer"
                      >
                        <Plus className="w-3 h-3" />
                      </button>
                    </div>
                    <button
                      onClick={() => updateQuantity(idx, 0)}
                      className="text-gray-400 hover:text-rose-500 p-1 cursor-pointer"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Ticket Calculations & Checkout Footer */}
        <div className="p-3.5 bg-gray-50 border-t border-gray-200 space-y-2.5">
          {/* Payment Method Selector */}
          <div className="grid grid-cols-2 gap-2">
            <button
              onClick={() => setPosPaymentMethod('cash_on_delivery')}
              className={`p-1.5 rounded-lg border text-xs font-bold flex items-center justify-center gap-1 cursor-pointer ${
                posPaymentMethod === 'cash_on_delivery'
                  ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                  : 'bg-white border-gray-200 text-gray-600'
              }`}
            >
              <Banknote className="w-3.5 h-3.5 text-emerald-600" />
              <span>نقداً (كاش)</span>
            </button>
            <button
              onClick={() => setPosPaymentMethod('card')}
              className={`p-1.5 rounded-lg border text-xs font-bold flex items-center justify-center gap-1 cursor-pointer ${
                posPaymentMethod === 'card'
                  ? 'border-blue-600 bg-blue-50 text-blue-800'
                  : 'bg-white border-gray-200 text-gray-600'
              }`}
            >
              <CreditCard className="w-3.5 h-3.5 text-blue-600" />
              <span>بطاقة POS</span>
            </button>
          </div>

          <div className="flex items-center justify-between text-xs text-gray-600">
            <span>المجموع:</span>
            <span className="font-bold">{subtotal.toLocaleString()} ل.س</span>
          </div>

          <div className="flex items-center justify-between text-base font-black text-gray-900 border-t border-gray-200 pt-1.5">
            <span>المبلغ المستحق:</span>
            <span className="text-amber-600">{total.toLocaleString()} ل.س</span>
          </div>

          <div className="grid grid-cols-2 gap-2 pt-1">
            <button
              onClick={() => setPosCart([])}
              className="py-2.5 px-3 rounded-xl border border-gray-300 hover:bg-gray-200 text-gray-700 font-bold text-xs cursor-pointer"
            >
              إلغاء التذكرة
            </button>

            <button
              onClick={handleCheckout}
              disabled={posCart.length === 0}
              className="py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-extrabold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs"
            >
              <span>دفع وإرسال للمطبخ</span>
            </button>
          </div>

          {lastCreatedOrder && (
            <div className="p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between text-[11px] text-emerald-800">
              <span className="font-bold">تم إصدار الطلب: {lastCreatedOrder.tracking_code}</span>
              <button
                onClick={() => onOpenReceipt(lastCreatedOrder)}
                className="text-amber-700 underline font-bold cursor-pointer flex items-center gap-1"
              >
                <Printer className="w-3 h-3" /> طباعة الإيصال
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
