import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Product, Category, ProductVariant } from '../../types';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Check,
  X,
  Flame,
  Clock,
  Layers,
  Image as ImageIcon,
} from 'lucide-react';

export const MenuManagement: React.FC = () => {
  const {
    currentRestaurant,
    categories,
    products,
    saveProduct,
    deleteProduct,
    toggleProductAvailability,
    saveCategory,
    deleteCategory,
  } = useApp();

  const [activeTab, setActiveTab] = useState<'products' | 'categories'>('products');
  const [selectedCatFilter, setSelectedCatFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Product edit modal state
  const [editingProduct, setEditingProduct] = useState<Partial<Product> | null>(null);
  const [isProductModalOpen, setIsProductModalOpen] = useState(false);

  // Category edit modal state
  const [editingCategory, setEditingCategory] = useState<Partial<Category> | null>(null);
  const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);

  // Variants editing state inside product modal
  const [variantsList, setVariantsList] = useState<ProductVariant[]>([]);

  const restCategories = categories.filter((c) => c.restaurant_id === currentRestaurant.id);
  const restProducts = products.filter((p) => p.restaurant_id === currentRestaurant.id);

  const filteredProducts = restProducts.filter((p) => {
    const matchCat = selectedCatFilter === 'all' || p.category_id === selectedCatFilter;
    const matchSearch =
      searchQuery.trim() === '' ||
      p.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchCat && matchSearch;
  });

  const handleOpenNewProduct = () => {
    setEditingProduct({
      restaurant_id: currentRestaurant.id,
      category_id: restCategories[0]?.id || '',
      name: '',
      name_ar: '',
      description: '',
      description_ar: '',
      price: 35000,
      price_type: 'fixed',
      image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
      is_available: true,
      calories: 600,
      preparation_time_minutes: 15,
    });
    setVariantsList([]);
    setIsProductModalOpen(true);
  };

  const handleOpenEditProduct = (prod: Product) => {
    setEditingProduct({ ...prod });
    setVariantsList(prod.variants ? [...prod.variants] : []);
    setIsProductModalOpen(true);
  };

  const handleSaveProductForm = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingProduct) return;
    if (!editingProduct.name_ar?.trim()) {
      alert('الرجاء إدخال اسم الصنف بالعربية');
      return;
    }

    saveProduct({
      ...editingProduct,
      variants: editingProduct.price_type === 'variants' ? variantsList : undefined,
    });

    setIsProductModalOpen(false);
    setEditingProduct(null);
  };

  const handleAddVariantRow = () => {
    setVariantsList([...variantsList, { name: 'حجم جديد', name_ar: 'حجم جديد', price: 40000 }]);
  };

  const handleRemoveVariantRow = (index: number) => {
    setVariantsList(variantsList.filter((_, i) => i !== index));
  };

  const handleOpenNewCategory = () => {
    setEditingCategory({
      restaurant_id: currentRestaurant.id,
      name: '',
      name_ar: '',
      sort_order: restCategories.length + 1,
      is_active: true,
    });
    setIsCategoryModalOpen(true);
  };

  const handleSaveCategoryForm = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingCategory || !editingCategory.name_ar?.trim()) return;
    saveCategory(editingCategory);
    setIsCategoryModalOpen(false);
    setEditingCategory(null);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Top Bar with Tabs and Add Buttons */}
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setActiveTab('products')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'products'
                ? 'bg-amber-600 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            الأصناف والوجبات ({restProducts.length})
          </button>
          <button
            onClick={() => setActiveTab('categories')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
              activeTab === 'categories'
                ? 'bg-amber-600 text-white shadow-xs'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            التصنيفات ({restCategories.length})
          </button>
        </div>

        <div>
          {activeTab === 'products' ? (
            <button
              onClick={handleOpenNewProduct}
              className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
            >
              <Plus className="w-4 h-4" />
              <span>إضافة وجبة / صنف جديد</span>
            </button>
          ) : (
            <button
              onClick={handleOpenNewCategory}
              className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
            >
              <Plus className="w-4 h-4" />
              <span>إضافة تصنيف جديد</span>
            </button>
          )}
        </div>
      </div>

      {activeTab === 'products' ? (
        <div className="space-y-4">
          {/* Filters & Search */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
              <button
                onClick={() => setSelectedCatFilter('all')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 cursor-pointer ${
                  selectedCatFilter === 'all'
                    ? 'bg-gray-900 text-white'
                    : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
                }`}
              >
                كافة التصنيفات
              </button>
              {restCategories.map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => setSelectedCatFilter(cat.id)}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 cursor-pointer ${
                    selectedCatFilter === cat.id
                      ? 'bg-gray-900 text-white'
                      : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
                  }`}
                >
                  {cat.name_ar}
                </button>
              ))}
            </div>

            <div className="relative w-full sm:w-64">
              <Search className="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="ابحث بالاسم..."
                className="w-full pr-9 pl-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500"
              />
            </div>
          </div>

          {/* Products Table */}
          <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
            <table className="w-full text-xs text-right">
              <thead>
                <tr className="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold">
                  <th className="py-3 px-4">الصورة</th>
                  <th className="py-3 px-4">اسم الصنف</th>
                  <th className="py-3 px-4">التصنيف</th>
                  <th className="py-3 px-4">السعر</th>
                  <th className="py-3 px-4">نوع السعر</th>
                  <th className="py-3 px-4">الحالة</th>
                  <th className="py-3 px-4 text-center">إجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filteredProducts.map((prod) => {
                  const cat = restCategories.find((c) => c.id === prod.category_id);
                  return (
                    <tr key={prod.id} className="hover:bg-gray-50/80 transition-colors">
                      <td className="py-3 px-4">
                        <img
                          src={prod.image}
                          alt={prod.name_ar}
                          className="w-12 h-12 rounded-xl object-cover"
                        />
                      </td>
                      <td className="py-3 px-4">
                        <div className="font-bold text-gray-900 text-sm">{prod.name_ar}</div>
                        <div className="text-[11px] text-gray-400">{prod.name}</div>
                      </td>
                      <td className="py-3 px-4">
                        <span className="bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                          {cat?.name_ar || 'عام'}
                        </span>
                      </td>
                      <td className="py-3 px-4 font-bold text-gray-900 text-sm">
                        {prod.price.toLocaleString()} ل.س
                      </td>
                      <td className="py-3 px-4">
                        <span className="text-[11px] text-gray-500">
                          {prod.price_type === 'variants'
                            ? `خيارات متعددة (${prod.variants?.length || 0})`
                            : 'سعر ثابت'}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <button
                          onClick={() => toggleProductAvailability(prod.id)}
                          className={`px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition-all ${
                            prod.is_available
                              ? 'bg-emerald-100 text-emerald-800'
                              : 'bg-rose-100 text-rose-800'
                          }`}
                        >
                          {prod.is_available ? 'متاح للطلب' : 'معطل مؤقتاً'}
                        </button>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex items-center justify-center gap-1.5">
                          <button
                            onClick={() => handleOpenEditProduct(prod)}
                            className="p-1.5 rounded-lg text-gray-600 hover:text-amber-600 hover:bg-amber-50 cursor-pointer"
                            title="تعديل"
                          >
                            <Edit2 className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => {
                              if (window.confirm(`هل أنت متأكد من حذف ${prod.name_ar}؟`)) {
                                deleteProduct(prod.id);
                              }
                            }}
                            className="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 cursor-pointer"
                            title="حذف"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        /* Categories Table */
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
          <table className="w-full text-xs text-right">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold">
                <th className="py-3 px-4">الترتيب</th>
                <th className="py-3 px-4">اسم التصنيف (عربي)</th>
                <th className="py-3 px-4">الاسم بالإنجليزية</th>
                <th className="py-3 px-4">عدد الأصناف</th>
                <th className="py-3 px-4 text-center">إجراءات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {restCategories.map((cat, i) => {
                const count = restProducts.filter((p) => p.category_id === cat.id).length;
                return (
                  <tr key={cat.id} className="hover:bg-gray-50/80 transition-colors">
                    <td className="py-3 px-4 font-mono font-bold text-gray-500">{i + 1}</td>
                    <td className="py-3 px-4 font-bold text-gray-900 text-sm">{cat.name_ar}</td>
                    <td className="py-3 px-4 text-gray-500 font-mono">{cat.name}</td>
                    <td className="py-3 px-4 font-semibold text-amber-600">{count} أصناف</td>
                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          onClick={() => {
                            setEditingCategory({ ...cat });
                            setIsCategoryModalOpen(true);
                          }}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-amber-600 hover:bg-amber-50 cursor-pointer"
                        >
                          <Edit2 className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => {
                            if (window.confirm(`هل أنت متأكد من حذف تصنيف ${cat.name_ar}؟`)) {
                              deleteCategory(cat.id);
                            }
                          }}
                          className="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 cursor-pointer"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Product Edit/Create Modal */}
      {isProductModalOpen && editingProduct && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-xl w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
            <div className="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
              <h3 className="font-bold text-sm text-gray-900">
                {editingProduct.id ? 'تعديل بيانات الصنف' : 'إضافة صنف جديد للقائمة'}
              </h3>
              <button
                onClick={() => setIsProductModalOpen(false)}
                className="p-1 rounded-full text-gray-500 hover:bg-gray-200 cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSaveProductForm} className="p-5 overflow-y-auto space-y-4 grow text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="font-bold text-gray-700 block mb-1">الاسم بالعربية:</label>
                  <input
                    type="text"
                    required
                    value={editingProduct.name_ar || ''}
                    onChange={(e) => setEditingProduct({ ...editingProduct, name_ar: e.target.value })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>
                <div>
                  <label className="font-bold text-gray-700 block mb-1">الاسم بالإنجليزية:</label>
                  <input
                    type="text"
                    value={editingProduct.name || ''}
                    onChange={(e) => setEditingProduct({ ...editingProduct, name: e.target.value })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="font-bold text-gray-700 block mb-1">التصنيف:</label>
                  <select
                    value={editingProduct.category_id || ''}
                    onChange={(e) => setEditingProduct({ ...editingProduct, category_id: e.target.value })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 bg-white"
                  >
                    {restCategories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name_ar}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="font-bold text-gray-700 block mb-1">السعر الأساسي (ل.س):</label>
                  <input
                    type="number"
                    required
                    value={editingProduct.price || 0}
                    onChange={(e) => setEditingProduct({ ...editingProduct, price: Number(e.target.value) })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">رابط صورة الصنف (URL):</label>
                <input
                  type="url"
                  value={editingProduct.image || ''}
                  onChange={(e) => setEditingProduct({ ...editingProduct, image: e.target.value })}
                  placeholder="https://images.unsplash.com/..."
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500 text-[11px] font-mono"
                />
              </div>

              <div>
                <label className="font-bold text-gray-700 block mb-1">الوصف بالعربية:</label>
                <textarea
                  rows={2}
                  value={editingProduct.description_ar || ''}
                  onChange={(e) => setEditingProduct({ ...editingProduct, description_ar: e.target.value })}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                ></textarea>
              </div>

              {/* Price Type & Variants */}
              <div className="space-y-3 pt-2 border-t border-gray-100">
                <div className="flex items-center gap-4">
                  <label className="font-bold text-gray-800">نوع التسعير:</label>
                  <label className="flex items-center gap-1.5 cursor-pointer">
                    <input
                      type="radio"
                      name="price_type"
                      checked={editingProduct.price_type === 'fixed'}
                      onChange={() => setEditingProduct({ ...editingProduct, price_type: 'fixed' })}
                    />
                    <span>سعر ثابت</span>
                  </label>
                  <label className="flex items-center gap-1.5 cursor-pointer">
                    <input
                      type="radio"
                      name="price_type"
                      checked={editingProduct.price_type === 'variants'}
                      onChange={() => setEditingProduct({ ...editingProduct, price_type: 'variants' })}
                    />
                    <span>خيارات وأحجام متعددة</span>
                  </label>
                </div>

                {editingProduct.price_type === 'variants' && (
                  <div className="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="font-bold text-gray-700">قائمة الأحجام أو الوجبات:</span>
                      <button
                        type="button"
                        onClick={handleAddVariantRow}
                        className="text-amber-600 font-bold hover:text-amber-700 flex items-center gap-1 text-[11px] cursor-pointer"
                      >
                        <Plus className="w-3.5 h-3.5" /> إضافة خيار
                      </button>
                    </div>

                    <div className="space-y-2">
                      {variantsList.map((v, i) => (
                        <div key={i} className="flex items-center gap-2">
                          <input
                            type="text"
                            value={v.name_ar || v.name}
                            onChange={(e) => {
                              const updated = [...variantsList];
                              updated[i].name_ar = e.target.value;
                              updated[i].name = e.target.value;
                              setVariantsList(updated);
                            }}
                            placeholder="اسم الخيار (مثال: وجبة كاملة)"
                            className="grow bg-white border border-gray-200 rounded-lg p-1.5 text-xs"
                          />
                          <input
                            type="number"
                            value={v.price}
                            onChange={(e) => {
                              const updated = [...variantsList];
                              updated[i].price = Number(e.target.value);
                              setVariantsList(updated);
                            }}
                            placeholder="السعر"
                            className="w-24 bg-white border border-gray-200 rounded-lg p-1.5 text-xs font-mono"
                          />
                          <button
                            type="button"
                            onClick={() => handleRemoveVariantRow(i)}
                            className="text-gray-400 hover:text-rose-500 p-1 cursor-pointer"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Nutritional & Prep */}
              <div className="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                <div>
                  <label className="font-bold text-gray-700 block mb-1">السعرات الحرارية:</label>
                  <input
                    type="number"
                    value={editingProduct.calories || 0}
                    onChange={(e) => setEditingProduct({ ...editingProduct, calories: Number(e.target.value) })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>
                <div>
                  <label className="font-bold text-gray-700 block mb-1">وقت التحضير (دقائق):</label>
                  <input
                    type="number"
                    value={editingProduct.preparation_time_minutes || 0}
                    onChange={(e) => setEditingProduct({ ...editingProduct, preparation_time_minutes: Number(e.target.value) })}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>
              </div>

              <div className="pt-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsProductModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold cursor-pointer"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-xl cursor-pointer shadow-xs"
                >
                  حفظ الصنف
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Category Modal */}
      {isCategoryModalOpen && editingCategory && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-gray-100 space-y-4">
            <h3 className="font-bold text-sm text-gray-900">
              {editingCategory.id ? 'تعديل التصنيف' : 'إضافة تصنيف جديد'}
            </h3>
            <form onSubmit={handleSaveCategoryForm} className="space-y-3 text-xs">
              <div>
                <label className="font-bold text-gray-700 block mb-1">اسم التصنيف (عربي):</label>
                <input
                  type="text"
                  required
                  value={editingCategory.name_ar || ''}
                  onChange={(e) => setEditingCategory({ ...editingCategory, name_ar: e.target.value })}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>
              <div>
                <label className="font-bold text-gray-700 block mb-1">الاسم بالإنجليزية:</label>
                <input
                  type="text"
                  value={editingCategory.name || ''}
                  onChange={(e) => setEditingCategory({ ...editingCategory, name: e.target.value })}
                  className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsCategoryModalOpen(false)}
                  className="px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-100 cursor-pointer"
                >
                  إلغاء
                </button>
                <button
                  type="submit"
                  className="bg-amber-600 text-white font-bold px-4 py-1.5 rounded-lg cursor-pointer"
                >
                  حفظ التصنيف
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
