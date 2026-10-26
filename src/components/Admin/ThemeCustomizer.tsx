import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { RestaurantThemeSettings } from '../../types';
import { Palette, Check, Sparkles, Layout, Eye, RotateCcw } from 'lucide-react';

export const ThemeCustomizer: React.FC = () => {
  const { currentRestaurant, updateThemeSettings } = useApp();
  const currentSettings = currentRestaurant.theme_settings;

  const [formData, setFormData] = useState<RestaurantThemeSettings>({ ...currentSettings });
  const [savedSuccess, setSavedSuccess] = useState(false);

  const handleChange = (key: keyof RestaurantThemeSettings, value: any) => {
    const updated = { ...formData, [key]: value };
    setFormData(updated);
    // Live update context and CSS variables immediately!
    updateThemeSettings({ [key]: value });
  };

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    updateThemeSettings(formData);
    setSavedSuccess(true);
    setTimeout(() => setSavedSuccess(false), 2000);
  };

  const handleResetDefaults = () => {
    const defaults: RestaurantThemeSettings = {
      primary_color: '#ff6b35',
      secondary_color: '#ffffff',
      background_color: '#1a1a1a',
      font_family: 'Tajawal',
      header_style: 'sticky',
      show_hero_banner: true,
      hero_title: 'برجر الباشا.. طعم يغير مقاييسك!',
      hero_subtitle: 'مشوي ع اللهب 100% لحم بقري بلدي طازج يومياً مع أشهى الصلصات',
      border_radius: '12px',
      card_style: 'elevated',
    };
    setFormData(defaults);
    updateThemeSettings(defaults);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Palette className="w-5 h-5 text-amber-600" />
            <h2 className="text-lg font-black text-gray-900">مخصص مظهر وثيم المطعم</h2>
          </div>
          <p className="text-xs text-gray-500 mt-1">
            خصص هوية {currentRestaurant.name_ar} البصرية، الألوان، الخطوط، البانر، وشكل البطاقات في المتجر العام.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={handleResetDefaults}
            className="text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-xl flex items-center gap-1 cursor-pointer transition-colors"
          >
            <RotateCcw className="w-3.5 h-3.5" /> استعادة الإعدادات الافتراضية
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Settings Form (7 cols) */}
        <div className="lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-6">
          <form onSubmit={handleSave} className="space-y-6 text-xs">
            {/* Color Palette Section */}
            <div className="space-y-3">
              <h3 className="font-extrabold text-sm text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <span>🎨 لوحة الألوان الأساسية</span>
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="space-y-1.5">
                  <label className="font-bold text-gray-700 block">اللون الرئيسي:</label>
                  <div className="flex items-center gap-2 border border-gray-200 p-1.5 rounded-xl">
                    <input
                      type="color"
                      value={formData.primary_color}
                      onChange={(e) => handleChange('primary_color', e.target.value)}
                      className="w-8 h-8 rounded-lg cursor-pointer border-0"
                    />
                    <span className="font-mono text-xs font-bold">{formData.primary_color}</span>
                  </div>
                </div>

                <div className="space-y-1.5">
                  <label className="font-bold text-gray-700 block">اللون الثانوي:</label>
                  <div className="flex items-center gap-2 border border-gray-200 p-1.5 rounded-xl">
                    <input
                      type="color"
                      value={formData.secondary_color}
                      onChange={(e) => handleChange('secondary_color', e.target.value)}
                      className="w-8 h-8 rounded-lg cursor-pointer border-0"
                    />
                    <span className="font-mono text-xs font-bold">{formData.secondary_color}</span>
                  </div>
                </div>

                <div className="space-y-1.5">
                  <label className="font-bold text-gray-700 block">لون خلفية الهيدر:</label>
                  <div className="flex items-center gap-2 border border-gray-200 p-1.5 rounded-xl">
                    <input
                      type="color"
                      value={formData.background_color}
                      onChange={(e) => handleChange('background_color', e.target.value)}
                      className="w-8 h-8 rounded-lg cursor-pointer border-0"
                    />
                    <span className="font-mono text-xs font-bold">{formData.background_color}</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Typography Section */}
            <div className="space-y-3 pt-2">
              <h3 className="font-extrabold text-sm text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <span>🔤 الخطوط وتنسيق النصوص</span>
              </h3>

              <div className="space-y-1.5">
                <label className="font-bold text-gray-700 block">نوع الخط العربي:</label>
                <div className="grid grid-cols-3 gap-2">
                  {['Tajawal', 'Cairo', 'Inter'].map((font) => (
                    <button
                      type="button"
                      key={font}
                      onClick={() => handleChange('font_family', font)}
                      className={`p-2.5 rounded-xl border text-center font-bold cursor-pointer transition-all ${
                        formData.font_family === font
                          ? 'border-amber-500 bg-amber-50 text-amber-900 shadow-2xs'
                          : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                      }`}
                      style={{ fontFamily: font }}
                    >
                      {font}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            {/* Banner Content Section */}
            <div className="space-y-3 pt-2">
              <h3 className="font-extrabold text-sm text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <span>🖼️ البانر الرئيسي والعناوين</span>
              </h3>

              <div className="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200">
                <div>
                  <span className="font-bold text-gray-800 block">إظهار البانر الترويجي في الواجهة</span>
                  <span className="text-[11px] text-gray-500">يعرض صورة الغلاف والعنوان الرئيسي في الصفحة الرئيسية</span>
                </div>
                <input
                  type="checkbox"
                  checked={formData.show_hero_banner}
                  onChange={(e) => handleChange('show_hero_banner', e.target.checked)}
                  className="w-4 h-4 text-amber-600 rounded focus:ring-amber-500"
                />
              </div>

              <div className="space-y-2">
                <div>
                  <label className="font-bold text-gray-700 block mb-1">عنوان البانر الرئيسي:</label>
                  <input
                    type="text"
                    value={formData.hero_title}
                    onChange={(e) => handleChange('hero_title', e.target.value)}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  />
                </div>

                <div>
                  <label className="font-bold text-gray-700 block mb-1">العنوان الفرعي:</label>
                  <textarea
                    rows={2}
                    value={formData.hero_subtitle}
                    onChange={(e) => handleChange('hero_subtitle', e.target.value)}
                    className="w-full border border-gray-200 rounded-xl p-2.5 focus:ring-2 focus:ring-amber-500"
                  ></textarea>
                </div>
              </div>
            </div>

            {/* Cards Style Section */}
            <div className="space-y-3 pt-2">
              <h3 className="font-extrabold text-sm text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <span>🎴 شكل البطاقات وانحناء الحواف</span>
              </h3>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="font-bold text-gray-700 block">نمط البطاقات:</label>
                  <select
                    value={formData.card_style}
                    onChange={(e) => handleChange('card_style', e.target.value as any)}
                    className="w-full bg-white border border-gray-200 rounded-xl p-2.5"
                  >
                    <option value="elevated">بارزة بظل خفيف (Elevated)</option>
                    <option value="flat">مسطحة بإطار نظيف (Flat)</option>
                  </select>
                </div>

                <div className="space-y-1.5">
                  <label className="font-bold text-gray-700 block">انحناء الحواف (Border Radius):</label>
                  <select
                    value={formData.border_radius}
                    onChange={(e) => handleChange('border_radius', e.target.value)}
                    className="w-full bg-white border border-gray-200 rounded-xl p-2.5"
                  >
                    <option value="4px">خفيف (4px)</option>
                    <option value="8px">متوسط (8px)</option>
                    <option value="12px">دائري مميز (12px)</option>
                    <option value="16px">دائري كامل (16px)</option>
                  </select>
                </div>
              </div>
            </div>

            <div className="pt-4 flex items-center justify-between border-t border-gray-100">
              {savedSuccess ? (
                <span className="text-emerald-600 font-bold flex items-center gap-1.5">
                  <Check className="w-4 h-4" /> تم تطبيق الإعدادات وحفظها بنجاح!
                </span>
              ) : (
                <span className="text-gray-400 text-[11px]">
                  التغييرات تظهر في المعاينة الحية فوراً
                </span>
              )}

              <button
                type="submit"
                className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-6 py-2.5 rounded-xl cursor-pointer shadow-xs transition-colors"
              >
                تأكيد وحفظ الإعدادات
              </button>
            </div>
          </form>
        </div>

        {/* Live Preview Card (5 cols) */}
        <div className="lg:col-span-5 space-y-4">
          <div className="flex items-center gap-2 text-xs font-bold text-gray-500">
            <Eye className="w-4 h-4" />
            <span>معاينة حية لشكل المتجر والبطاقات:</span>
          </div>

          <div
            className="rounded-2xl border border-gray-300 overflow-hidden shadow-lg"
            style={{
              fontFamily: formData.font_family,
              backgroundColor: '#f8fafc',
            }}
          >
            {/* Mock Header */}
            <div
              className="p-4 text-white flex items-center justify-between"
              style={{ backgroundColor: formData.background_color }}
            >
              <div className="font-bold text-sm">{currentRestaurant.name_ar}</div>
              <div
                className="px-2.5 py-1 text-[11px] font-bold rounded-lg"
                style={{ backgroundColor: formData.primary_color, color: formData.secondary_color }}
              >
                السلة (2)
              </div>
            </div>

            {/* Mock Banner */}
            {formData.show_hero_banner && (
              <div className="p-6 text-white text-center bg-gray-900 relative">
                <div className="relative z-10 space-y-1.5">
                  <h4 className="font-black text-lg">{formData.hero_title}</h4>
                  <p className="text-xs text-gray-300">{formData.hero_subtitle}</p>
                </div>
              </div>
            )}

            {/* Mock Product Card */}
            <div className="p-4 space-y-3">
              <div className="text-xs font-bold text-gray-800">عينة من بطاقات الأصناف:</div>
              <div
                className={`bg-white p-3 border border-gray-200 transition-all ${
                  formData.card_style === 'elevated' ? 'shadow-md' : 'shadow-none'
                }`}
                style={{ borderRadius: formData.border_radius }}
              >
                <div className="flex items-center gap-3">
                  <img
                    src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&auto=format&fit=crop&q=80"
                    alt="Sample"
                    className="w-16 h-16 object-cover rounded-lg"
                  />
                  <div className="grow space-y-1">
                    <div className="font-bold text-xs text-gray-900">برجر الباشا سوبريم</div>
                    <div className="text-[10px] text-gray-500">لحم أنغوس طازج مع الشيدر الذائب</div>
                    <div className="flex items-center justify-between pt-1">
                      <span className="font-black text-xs text-gray-900">48,000 ل.س</span>
                      <button
                        className="px-2.5 py-1 text-[10px] font-bold rounded-md"
                        style={{
                          backgroundColor: formData.primary_color,
                          color: formData.secondary_color,
                          borderRadius: formData.border_radius,
                        }}
                      >
                        إضافة للسلة
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
