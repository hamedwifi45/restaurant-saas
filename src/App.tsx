import React, { useState } from 'react';
import { useApp } from './context/AppContext';
import { Navbar } from './components/Navbar';
import { StorefrontHome } from './components/Storefront/StorefrontHome';
import { StorefrontCartModal } from './components/Storefront/StorefrontCartModal';
import { StorefrontTrackOrder } from './components/Storefront/StorefrontTrackOrder';
import { StorefrontInvoiceModal } from './components/Storefront/StorefrontInvoiceModal';
import { TableQRModal } from './components/Storefront/TableQRModal';
import { AdminDashboard } from './components/Admin/AdminDashboard';
import { MenuManagement } from './components/Admin/MenuManagement';
import { OrdersManagement } from './components/Admin/OrdersManagement';
import { PosView } from './components/Admin/PosView';
import { KdsView } from './components/Admin/KdsView';
import { TablesManagement } from './components/Admin/TablesManagement';
import { FleetManagement } from './components/Admin/FleetManagement';
import { ExpensesManagement } from './components/Admin/ExpensesManagement';
import { ThemeCustomizer } from './components/Admin/ThemeCustomizer';
import { CouponsManagement } from './components/Admin/CouponsManagement';
import { DriverPortal } from './components/Driver/DriverPortal';
import { SuperAdminPortal } from './components/SuperAdmin/SuperAdminPortal';
import { Order } from './types';
import {
  LayoutDashboard,
  UtensilsCrossed,
  ChefHat,
  ShoppingBag,
  Layers,
  Users,
  Bike,
  DollarSign,
  Palette,
  Tag,
  ArrowRight,
} from 'lucide-react';

export const App: React.FC = () => {
  const { currentRestaurant, currentRole, setCurrentRole } = useApp();

  const [activeView, setActiveView] = useState<string>('storefront');
  const [adminTab, setAdminTab] = useState<string>('dashboard');
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isTableQRModalOpen, setIsTableQRModalOpen] = useState(false);
  const [invoiceOrder, setInvoiceOrder] = useState<Order | null>(null);
  const [trackingCodeToView, setTrackingCodeToView] = useState<string>('');

  const handleOrderCreated = (order: Order) => {
    setTrackingCodeToView(order.tracking_code);
    setActiveView('storefront-tracking');
  };

  const adminNavItems = [
    { id: 'dashboard', label: 'لوحة المؤشرات', icon: <LayoutDashboard className="w-4 h-4" /> },
    { id: 'orders', label: 'إدارة الطلبات', icon: <ShoppingBag className="w-4 h-4" /> },
    { id: 'menu', label: 'المنيو والأصناف', icon: <Layers className="w-4 h-4" /> },
    { id: 'pos', label: 'الكاشير (POS)', icon: <UtensilsCrossed className="w-4 h-4" /> },
    { id: 'kds', label: 'شاشة المطبخ (KDS)', icon: <ChefHat className="w-4 h-4" /> },
    { id: 'tables', label: 'طاولات الصالة و QR', icon: <Users className="w-4 h-4" /> },
    { id: 'fleet', label: 'أسطول التوصيل', icon: <Bike className="w-4 h-4" /> },
    { id: 'coupons', label: 'الكوبونات والعروض', icon: <Tag className="w-4 h-4" /> },
    { id: 'expenses', label: 'المصاريف والأرباح', icon: <DollarSign className="w-4 h-4" /> },
    { id: 'theme', label: 'تخصيص الثيم', icon: <Palette className="w-4 h-4" /> },
  ];

  return (
    <div className="min-h-screen bg-gray-50 text-gray-900 flex flex-col">
      {/* Universal Top Navigation */}
      <Navbar
        activeView={activeView}
        setActiveView={(v) => {
          setActiveView(v);
          if (v.startsWith('admin-')) {
            setAdminTab(v.replace('admin-', ''));
          }
        }}
        openCart={() => setIsCartOpen(true)}
        openTableQRModal={() => setIsTableQRModalOpen(true)}
      />

      {/* Admin Subheader Bar (Visible in Restaurant Admin mode) */}
      {(activeView.startsWith('admin') || activeView === 'pos' || activeView === 'kds') && (
        <div className="bg-white border-b border-gray-200 sticky top-16 z-30 shadow-2xs">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-1 overflow-x-auto py-2 scrollbar-none">
              {adminNavItems.map((item) => {
                const isActive =
                  adminTab === item.id ||
                  (activeView === 'pos' && item.id === 'pos') ||
                  (activeView === 'kds' && item.id === 'kds');

                return (
                  <button
                    key={item.id}
                    onClick={() => {
                      setAdminTab(item.id);
                      if (item.id === 'pos') {
                        setActiveView('pos');
                      } else if (item.id === 'kds') {
                        setActiveView('kds');
                      } else {
                        setActiveView(`admin-${item.id}`);
                      }
                    }}
                    className={`px-3 py-2 rounded-xl text-xs font-bold shrink-0 flex items-center gap-1.5 transition-all cursor-pointer ${
                      isActive
                        ? 'bg-amber-600 text-white shadow-xs'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                    }`}
                  >
                    {item.icon}
                    <span>{item.label}</span>
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* Main Container Views */}
      <main className="grow">
        {/* Customer Storefront Views */}
        {activeView === 'storefront' && (
          <StorefrontHome
            onOpenCart={() => setIsCartOpen(true)}
            onOpenTableQRModal={() => setIsTableQRModalOpen(true)}
            onNavigateToTracking={() => setActiveView('storefront-tracking')}
          />
        )}

        {activeView === 'storefront-tracking' && (
          <div className="max-w-7xl mx-auto px-4 py-4">
            <div className="mb-4">
              <button
                onClick={() => setActiveView('storefront')}
                className="text-xs font-bold text-gray-600 hover:text-amber-600 flex items-center gap-1 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-gray-200"
              >
                <ArrowRight className="w-3.5 h-3.5" />
                <span>العودة إلى متجر {currentRestaurant.name_ar}</span>
              </button>
            </div>
            <StorefrontTrackOrder
              initialCode={trackingCodeToView}
              onOpenInvoice={(ord) => setInvoiceOrder(ord)}
              onBackToMenu={() => setActiveView('storefront')}
            />
          </div>
        )}

        {/* Restaurant Management Admin Views */}
        {(activeView.startsWith('admin') || activeView === 'pos' || activeView === 'kds') && (
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {(adminTab === 'dashboard' || activeView === 'admin-dashboard') && (
              <AdminDashboard
                onNavigateTab={(tab) => {
                  setAdminTab(tab);
                  if (tab === 'pos') setActiveView('pos');
                  else if (tab === 'kds') setActiveView('kds');
                  else setActiveView(`admin-${tab}`);
                }}
              />
            )}
            {(adminTab === 'orders' || activeView === 'admin-orders') && (
              <OrdersManagement onOpenInvoice={(ord) => setInvoiceOrder(ord)} />
            )}
            {(adminTab === 'menu' || activeView === 'admin-menu') && <MenuManagement />}
            {(adminTab === 'pos' || activeView === 'pos') && (
              <PosView onOpenReceipt={(ord) => setInvoiceOrder(ord)} />
            )}
            {(adminTab === 'kds' || activeView === 'kds') && <KdsView />}
            {(adminTab === 'tables' || activeView === 'admin-tables') && <TablesManagement />}
            {(adminTab === 'fleet' || activeView === 'admin-fleet') && <FleetManagement />}
            {(adminTab === 'coupons' || activeView === 'admin-coupons') && <CouponsManagement />}
            {(adminTab === 'expenses' || activeView === 'admin-expenses') && <ExpensesManagement />}
            {(adminTab === 'theme' || activeView === 'admin-theme') && <ThemeCustomizer />}
          </div>
        )}

        {/* Driver Portal View */}
        {activeView === 'driver' && <DriverPortal />}

        {/* Super Admin Platform View */}
        {activeView === 'super-admin' && (
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <SuperAdminPortal
              onImpersonateRestaurant={(slug) => {
                setActiveView('admin-dashboard');
                setAdminTab('dashboard');
              }}
            />
          </div>
        )}
      </main>

      {/* Cart & Checkout Modal */}
      <StorefrontCartModal
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        onOrderCreated={handleOrderCreated}
      />

      {/* Dine-in Table QR Simulation Modal */}
      <TableQRModal
        isOpen={isTableQRModalOpen}
        onClose={() => setIsTableQRModalOpen(false)}
        onSelectTable={(table) => {
          setActiveView('storefront');
        }}
      />

      {/* Formal Tax Invoice Printable Modal */}
      {invoiceOrder && (
        <StorefrontInvoiceModal
          order={invoiceOrder}
          onClose={() => setInvoiceOrder(null)}
        />
      )}

      {/* Footer */}
      <footer className="no-print bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <span className="font-extrabold text-gray-900">{currentRestaurant.name_ar}</span>
            <span>•</span>
            <span>منصة المطاعم السحابية (Restaurant SaaS Platform)</span>
          </div>
          <div className="text-gray-400">
            نظام متكامل لإدارة المتجر الإلكتروني، المنيو، الصالة، نقطة البيع KDS، أسطول التوصيل
          </div>
        </div>
      </footer>
    </div>
  );
};
