import React, { useState } from 'react';
import { useApp } from '../context/AppContext';
import {
  ShoppingBag,
  Store,
  LayoutDashboard,
  UtensilsCrossed,
  ChefHat,
  Bike,
  ShieldCheck,
  QrCode,
  RotateCcw,
  Menu,
  X,
  MapPin,
  Clock,
  Phone,
} from 'lucide-react';
import { Role } from '../types';

interface NavbarProps {
  activeView: string;
  setActiveView: (view: string) => void;
  openCart: () => void;
  openTableQRModal: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({
  activeView,
  setActiveView,
  openCart,
  openTableQRModal,
}) => {
  const {
    currentRestaurant,
    restaurants,
    switchRestaurant,
    currentRole,
    setCurrentRole,
    cart,
    scannedTable,
    resetDemoData,
  } = useApp();

  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const cartItemsCount = cart.reduce((acc, item) => acc + item.quantity, 0);

  const navigationRoles: { role: Role; label: string; icon: React.ReactNode; view: string }[] = [
    { role: 'customer', label: 'المتجر والمنيو', icon: <Store className="w-4 h-4" />, view: 'storefront' },
    { role: 'restaurant_admin', label: 'لوحة المطعم', icon: <LayoutDashboard className="w-4 h-4" />, view: 'admin-dashboard' },
    { role: 'restaurant_admin', label: 'نقطة البيع (POS)', icon: <UtensilsCrossed className="w-4 h-4" />, view: 'pos' },
    { role: 'kitchen_staff', label: 'شاشة المطبخ (KDS)', icon: <ChefHat className="w-4 h-4" />, view: 'kds' },
    { role: 'driver', label: 'بوابة المندوب', icon: <Bike className="w-4 h-4" />, view: 'driver' },
    { role: 'super_admin', label: 'إدارة المنصة (Sham)', icon: <ShieldCheck className="w-4 h-4" />, view: 'super-admin' },
  ];

  return (
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-xs">
      {/* Top Banner with Restaurant Switcher & Status */}
      <div className="bg-gray-900 text-white text-xs py-1.5 px-4">
        <div className="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
          {/* Active restaurant quick selector */}
          <div className="flex items-center gap-2">
            <span className="text-gray-400 font-medium">المطعم النشط:</span>
            <select
              value={currentRestaurant.slug}
              onChange={(e) => switchRestaurant(e.target.value)}
              className="bg-gray-800 text-amber-400 font-bold border border-gray-700 rounded-md px-2.5 py-1 text-xs focus:ring-1 focus:ring-amber-500 cursor-pointer"
            >
              {restaurants.map((r) => (
                <option key={r.id} value={r.slug}>
                  {r.name_ar} ({r.city})
                </option>
              ))}
            </select>
            {scannedTable && (
              <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2 py-0.5 rounded text-[11px] font-medium flex items-center gap-1">
                <QrCode className="w-3 h-3" /> جلسة محلية: {scannedTable.table_number}
              </span>
            )}
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={openTableQRModal}
              className="hover:text-amber-300 text-gray-300 flex items-center gap-1 transition-colors cursor-pointer text-xs"
              title="محاكاة مسح كود QR للطاولة"
            >
              <QrCode className="w-3.5 h-3.5" /> مسح QR طاولة
            </button>
            <span className="text-gray-600">|</span>
            <button
              onClick={() => {
                if (window.confirm('هل تريد إعادة تعيين كافة البيانات التجريبية لحالتها الأولية؟')) {
                  resetDemoData();
                }
              }}
              className="text-gray-400 hover:text-rose-300 flex items-center gap-1 transition-colors cursor-pointer text-xs"
              title="إعادة تعيين البيانات الأولية"
            >
              <RotateCcw className="w-3 h-3" /> إعادة تعيين البيانات
            </button>
          </div>
        </div>
      </div>

      {/* Main Navbar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo & Name */}
          <div className="flex items-center gap-3">
            <button
              onClick={() => {
                setCurrentRole('customer');
                setActiveView('storefront');
              }}
              className="flex items-center gap-2.5 text-right cursor-pointer group"
            >
              <img
                src={currentRestaurant.logo}
                alt={currentRestaurant.name_ar}
                className="w-10 h-10 rounded-full object-cover ring-2 ring-amber-500/30 group-hover:ring-amber-500 transition-all shadow-xs"
              />
              <div>
                <div className="font-extrabold text-base text-gray-900 group-hover:text-amber-600 transition-colors">
                  {currentRestaurant.name_ar}
                </div>
                <div className="text-[11px] text-gray-500 flex items-center gap-1">
                  <MapPin className="w-2.5 h-2.5" /> {currentRestaurant.city}
                </div>
              </div>
            </button>
          </div>

          {/* Role Navigation Pills */}
          <nav className="hidden lg:flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
            {navigationRoles.map((item, idx) => {
              const isActive = activeView === item.view;
              return (
                <button
                  key={idx}
                  onClick={() => {
                    setCurrentRole(item.role);
                    setActiveView(item.view);
                  }}
                  className={`px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer ${
                    isActive
                      ? 'bg-white text-gray-900 shadow-xs font-bold'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/60'
                  }`}
                >
                  <span className={isActive ? 'text-amber-600' : 'text-gray-500'}>
                    {item.icon}
                  </span>
                  {item.label}
                </button>
              );
            })}
          </nav>

          {/* Storefront Action Buttons (Cart & Quick Actions) */}
          <div className="flex items-center gap-2">
            <button
              onClick={openCart}
              className="relative p-2 rounded-xl text-gray-700 hover:bg-amber-50 hover:text-amber-600 transition-colors flex items-center gap-2 font-medium text-xs cursor-pointer border border-gray-200"
            >
              <ShoppingBag className="w-5 h-5 text-amber-600" />
              <span className="hidden sm:inline">السلة</span>
              {cartItemsCount > 0 && (
                <span className="bg-amber-600 text-white text-[11px] font-bold px-1.5 py-0.5 rounded-full min-w-5 text-center shadow-xs">
                  {cartItemsCount}
                </span>
              )}
            </button>

            {/* Mobile menu button */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 cursor-pointer"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="lg:hidden border-t border-gray-200 bg-white px-4 pt-3 pb-5 space-y-2">
          <div className="text-xs font-bold text-gray-500 px-2 mb-1">الانتقال بين الأنظمة:</div>
          <div className="grid grid-cols-2 gap-1.5">
            {navigationRoles.map((item, idx) => (
              <button
                key={idx}
                onClick={() => {
                  setCurrentRole(item.role);
                  setActiveView(item.view);
                  setMobileMenuOpen(false);
                }}
                className={`p-2.5 rounded-lg text-xs font-medium flex items-center gap-2 transition-all cursor-pointer ${
                  activeView === item.view
                    ? 'bg-amber-500 text-white font-bold'
                    : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
                }`}
              >
                {item.icon}
                {item.label}
              </button>
            ))}
          </div>
        </div>
      )}
    </header>
  );
};
