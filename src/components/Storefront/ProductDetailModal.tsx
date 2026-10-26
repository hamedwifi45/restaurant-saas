import React, { useState } from 'react';
import { Product, ProductVariant } from '../../types';
import { X, Flame, Clock, Plus, Minus, Check } from 'lucide-react';

interface ProductDetailModalProps {
  product: Product | null;
  onClose: () => void;
  onAddToCart: (
    product: Product,
    quantity: number,
    variant?: ProductVariant,
    notes?: string
  ) => void;
}

export const ProductDetailModal: React.FC<ProductDetailModalProps> = ({
  product,
  onClose,
  onAddToCart,
}) => {
  if (!product) return null;

  const [selectedVariant, setSelectedVariant] = useState<ProductVariant | undefined>(
    product.variants && product.variants.length > 0 ? product.variants[0] : undefined
  );
  const [quantity, setQuantity] = useState(1);
  const [notes, setNotes] = useState('');
  const [added, setAdded] = useState(false);

  const currentPrice = selectedVariant ? selectedVariant.price : product.price;
  const totalPrice = currentPrice * quantity;

  const handleAdd = () => {
    onAddToCart(product, quantity, selectedVariant, notes);
    setAdded(true);
    setTimeout(() => {
      setAdded(false);
      onClose();
    }, 600);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
        {/* Header Image */}
        <div className="relative h-56 sm:h-64 w-full bg-gray-100 shrink-0">
          <img
            src={product.image}
            alt={product.name_ar}
            className="w-full h-full object-cover"
          />
          <button
            onClick={onClose}
            className="absolute top-3 left-3 bg-white/80 hover:bg-white text-gray-700 p-2 rounded-full shadow-md transition-all cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
          {product.calories && (
            <span className="absolute bottom-3 right-3 bg-black/70 backdrop-blur-md text-amber-300 text-xs px-2.5 py-1 rounded-full flex items-center gap-1 font-medium">
              <Flame className="w-3.5 h-3.5" /> {product.calories} سعرة
            </span>
          )}
          {product.preparation_time_minutes && (
            <span className="absolute bottom-3 left-3 bg-black/70 backdrop-blur-md text-white text-xs px-2.5 py-1 rounded-full flex items-center gap-1 font-medium">
              <Clock className="w-3.5 h-3.5" /> {product.preparation_time_minutes} دقيقة تحضير
            </span>
          )}
        </div>

        {/* Modal Body */}
        <div className="p-5 overflow-y-auto space-y-4 grow">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h3 className="text-xl font-bold text-gray-900">{product.name_ar}</h3>
              <p className="text-xs text-gray-400 font-medium">{product.name}</p>
            </div>
            <div className="text-xl font-extrabold text-amber-600 shrink-0">
              {currentPrice.toLocaleString()} <span className="text-xs font-normal text-gray-500">ل.س</span>
            </div>
          </div>

          <p className="text-sm text-gray-600 leading-relaxed">
            {product.description_ar || product.description}
          </p>

          {/* Variants Selection */}
          {product.variants && product.variants.length > 0 && (
            <div className="space-y-2 pt-2 border-t border-gray-100">
              <label className="text-xs font-bold text-gray-800 block">
                اختر الحجم أو الوجبة:
              </label>
              <div className="grid gap-2">
                {product.variants.map((v, i) => (
                  <label
                    key={i}
                    onClick={() => setSelectedVariant(v)}
                    className={`flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all ${
                      selectedVariant?.name === v.name
                        ? 'border-amber-500 bg-amber-50/70 text-gray-900 font-bold'
                        : 'border-gray-200 hover:border-gray-300 text-gray-700'
                    }`}
                  >
                    <div className="flex items-center gap-2 text-sm">
                      <input
                        type="radio"
                        name="product-variant"
                        checked={selectedVariant?.name === v.name}
                        onChange={() => setSelectedVariant(v)}
                        className="text-amber-600 focus:ring-amber-500"
                      />
                      <span>{v.name_ar || v.name}</span>
                    </div>
                    <span className="text-xs font-bold text-amber-600">
                      {v.price.toLocaleString()} ل.س
                    </span>
                  </label>
                ))}
              </div>
            </div>
          )}

          {/* Special Notes */}
          <div className="space-y-1.5 pt-2 border-t border-gray-100">
            <label className="text-xs font-bold text-gray-700 block">
              ملاحظات إضافية (اختياري):
            </label>
            <input
              type="text"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="مثال: بدون بصل، صوص زيادة..."
              className="w-full text-xs p-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
            />
          </div>
        </div>

        {/* Footer Actions */}
        <div className="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0">
          <div className="flex items-center border border-gray-200 bg-white rounded-xl shadow-2xs">
            <button
              onClick={() => setQuantity((q) => Math.max(1, q - 1))}
              className="p-2 text-gray-600 hover:text-gray-900 cursor-pointer"
            >
              <Minus className="w-4 h-4" />
            </button>
            <span className="px-3 font-bold text-sm min-w-8 text-center">{quantity}</span>
            <button
              onClick={() => setQuantity((q) => q + 1)}
              className="p-2 text-gray-600 hover:text-gray-900 cursor-pointer"
            >
              <Plus className="w-4 h-4" />
            </button>
          </div>

          <button
            onClick={handleAdd}
            disabled={!product.is_available}
            className={`grow py-3 px-4 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-sm transition-all cursor-pointer ${
              added
                ? 'bg-emerald-600 text-white'
                : 'bg-amber-600 hover:bg-amber-700 text-white'
            }`}
          >
            {added ? (
              <>
                <Check className="w-4 h-4" /> تمت الإضافة للسلة!
              </>
            ) : (
              <>
                <span>إضافة إلى السلة</span>
                <span className="bg-amber-700/60 px-2 py-0.5 rounded text-xs">
                  {totalPrice.toLocaleString()} ل.س
                </span>
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};
