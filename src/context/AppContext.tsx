import React, { createContext, useContext, useState, useEffect } from 'react';
import {
  Restaurant,
  Category,
  Product,
  Order,
  RestaurantTable,
  Driver,
  Coupon,
  Offer,
  Expense,
  Review,
  DeliveryProvider,
  Role,
  OrderStatus,
  TableStatus,
  RestaurantThemeSettings,
} from '../types';
import {
  INITIAL_RESTAURANTS,
  INITIAL_CATEGORIES,
  INITIAL_PRODUCTS,
  INITIAL_TABLES,
  INITIAL_DRIVERS,
  INITIAL_COUPONS,
  INITIAL_OFFERS,
  INITIAL_ORDERS,
  INITIAL_EXPENSES,
  INITIAL_REVIEWS,
  INITIAL_DELIVERY_PROVIDERS,
} from '../data/initialData';

export interface CartItem {
  product: Product;
  variant?: { name: string; name_ar?: string; price: number };
  quantity: number;
  notes?: string;
}

interface AppContextType {
  restaurants: Restaurant[];
  currentRestaurant: Restaurant;
  switchRestaurant: (slug: string) => void;
  currentRole: Role;
  setCurrentRole: (role: Role) => void;
  currentLanguage: 'ar' | 'en';
  setCurrentLanguage: (lang: 'ar' | 'en') => void;

  categories: Category[];
  products: Product[];
  orders: Order[];
  tables: RestaurantTable[];
  drivers: Driver[];
  coupons: Coupon[];
  offers: Offer[];
  expenses: Expense[];
  reviews: Review[];
  deliveryProviders: DeliveryProvider[];

  // Cart
  cart: CartItem[];
  addToCart: (product: Product, quantity?: number, variant?: { name: string; name_ar?: string; price: number }, notes?: string) => void;
  updateCartQuantity: (index: number, quantity: number) => void;
  removeFromCart: (index: number) => void;
  clearCart: () => void;
  appliedCoupon: Coupon | null;
  applyCoupon: (code: string, subtotal: number) => { success: boolean; message: string };
  removeCoupon: () => void;

  // Orders
  createOrder: (orderData: Partial<Order>) => Order;
  updateOrderStatus: (orderId: string, status: OrderStatus, extra?: Partial<Order>) => void;
  assignDriver: (orderId: string, driverId: string) => void;
  rateDriver: (orderId: string, rating: number, comment?: string) => void;
  updateDriverLocation: (driverId: string, location: { lat: number; lng: number }) => void;

  // Products & Categories
  saveProduct: (product: Partial<Product>) => void;
  deleteProduct: (productId: string) => void;
  toggleProductAvailability: (productId: string) => void;
  saveCategory: (category: Partial<Category>) => void;
  deleteCategory: (categoryId: string) => void;

  // Tables
  saveTable: (table: Partial<RestaurantTable>) => void;
  updateTableStatus: (tableId: string, status: TableStatus) => void;
  scannedTable: RestaurantTable | null;
  setScannedTable: (table: RestaurantTable | null) => void;

  // Drivers
  saveDriver: (driver: Partial<Driver>) => void;
  toggleDriverStatus: (driverId: string) => void;

  // Coupons
  saveCoupon: (coupon: Partial<Coupon>) => void;
  deleteCoupon: (id: string) => void;

  // Expenses
  addExpense: (expense: Omit<Expense, 'id' | 'restaurant_id'>) => void;
  deleteExpense: (id: string) => void;

  // Reviews
  addReview: (review: Omit<Review, 'id' | 'restaurant_id' | 'date'>) => void;

  // Settings
  updateRestaurantSettings: (updated: Partial<Restaurant>) => void;
  updateThemeSettings: (settings: Partial<RestaurantThemeSettings>) => void;

  // Reset to initial
  resetDemoData: () => void;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

function loadFromStorage<T>(key: string, defaultValue: T): T {
  try {
    const item = localStorage.getItem(`saas_${key}`);
    return item ? JSON.parse(item) : defaultValue;
  } catch (e) {
    console.error(`Failed to load ${key} from storage:`, e);
    return defaultValue;
  }
}

function saveToStorage<T>(key: string, value: T): void {
  try {
    localStorage.setItem(`saas_${key}`, JSON.stringify(value));
  } catch (e) {
    console.error(`Failed to save ${key} to storage:`, e);
  }
}

export const AppProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [restaurants, setRestaurants] = useState<Restaurant[]>(() =>
    loadFromStorage('restaurants', INITIAL_RESTAURANTS)
  );
  const [activeSlug, setActiveSlug] = useState<string>('albasha-burger');
  const [currentRole, setCurrentRole] = useState<Role>('customer');
  const [currentLanguage, setCurrentLanguage] = useState<'ar' | 'en'>('ar');

  const [categories, setCategories] = useState<Category[]>(() =>
    loadFromStorage('categories', INITIAL_CATEGORIES)
  );
  const [products, setProducts] = useState<Product[]>(() =>
    loadFromStorage('products', INITIAL_PRODUCTS)
  );
  const [orders, setOrders] = useState<Order[]>(() =>
    loadFromStorage('orders', INITIAL_ORDERS)
  );
  const [tables, setTables] = useState<RestaurantTable[]>(() =>
    loadFromStorage('tables', INITIAL_TABLES)
  );
  const [drivers, setDrivers] = useState<Driver[]>(() =>
    loadFromStorage('drivers', INITIAL_DRIVERS)
  );
  const [coupons, setCoupons] = useState<Coupon[]>(() =>
    loadFromStorage('coupons', INITIAL_COUPONS)
  );
  const [offers, setOffers] = useState<Offer[]>(() =>
    loadFromStorage('offers', INITIAL_OFFERS)
  );
  const [expenses, setExpenses] = useState<Expense[]>(() =>
    loadFromStorage('expenses', INITIAL_EXPENSES)
  );
  const [reviews, setReviews] = useState<Review[]>(() =>
    loadFromStorage('reviews', INITIAL_REVIEWS)
  );
  const [deliveryProviders, setDeliveryProviders] = useState<DeliveryProvider[]>(() =>
    loadFromStorage('deliveryProviders', INITIAL_DELIVERY_PROVIDERS)
  );

  const [cart, setCart] = useState<CartItem[]>(() =>
    loadFromStorage('cart', [])
  );
  const [appliedCoupon, setAppliedCoupon] = useState<Coupon | null>(null);
  const [scannedTable, setScannedTable] = useState<RestaurantTable | null>(null);

  // Sync state to localStorage
  useEffect(() => { saveToStorage('restaurants', restaurants); }, [restaurants]);
  useEffect(() => { saveToStorage('categories', categories); }, [categories]);
  useEffect(() => { saveToStorage('products', products); }, [products]);
  useEffect(() => { saveToStorage('orders', orders); }, [orders]);
  useEffect(() => { saveToStorage('tables', tables); }, [tables]);
  useEffect(() => { saveToStorage('drivers', drivers); }, [drivers]);
  useEffect(() => { saveToStorage('coupons', coupons); }, [coupons]);
  useEffect(() => { saveToStorage('offers', offers); }, [offers]);
  useEffect(() => { saveToStorage('expenses', expenses); }, [expenses]);
  useEffect(() => { saveToStorage('reviews', reviews); }, [reviews]);
  useEffect(() => { saveToStorage('deliveryProviders', deliveryProviders); }, [deliveryProviders]);
  useEffect(() => { saveToStorage('cart', cart); }, [cart]);

  const currentRestaurant = restaurants.find((r) => r.slug === activeSlug) || restaurants[0];

  // Apply theme styles to document root
  useEffect(() => {
    if (currentRestaurant?.theme_settings) {
      const ts = currentRestaurant.theme_settings;
      document.documentElement.style.setProperty('--primary-color', ts.primary_color);
      document.documentElement.style.setProperty('--secondary-color', ts.secondary_color);
      document.documentElement.style.setProperty('--bg-color', ts.background_color);
      document.documentElement.style.setProperty('--border-radius', ts.border_radius);
      document.documentElement.style.setProperty('--font-family', ts.font_family + ', Tajawal, Cairo, sans-serif');
    }
  }, [currentRestaurant]);

  const switchRestaurant = (slug: string) => {
    const found = restaurants.find((r) => r.slug === slug || r.id === slug);
    if (found) {
      setActiveSlug(found.slug);
      setCart([]);
      setAppliedCoupon(null);
    }
  };

  // Cart operations
  const addToCart = (
    product: Product,
    quantity = 1,
    variant?: { name: string; name_ar?: string; price: number },
    notes?: string
  ) => {
    setCart((prev) => {
      const existingIndex = prev.findIndex(
        (item) => item.product.id === product.id && item.variant?.name === variant?.name
      );
      if (existingIndex > -1) {
        const next = [...prev];
        next[existingIndex] = {
          ...next[existingIndex],
          quantity: next[existingIndex].quantity + quantity,
          notes: notes !== undefined ? notes : next[existingIndex].notes,
        };
        return next;
      }
      return [...prev, { product, quantity, variant, notes }];
    });
  };

  const updateCartQuantity = (index: number, quantity: number) => {
    setCart((prev) => {
      if (quantity <= 0) {
        return prev.filter((_, i) => i !== index);
      }
      const next = [...prev];
      next[index] = { ...next[index], quantity };
      return next;
    });
  };

  const removeFromCart = (index: number) => {
    setCart((prev) => prev.filter((_, i) => i !== index));
  };

  const clearCart = () => {
    setCart([]);
    setAppliedCoupon(null);
  };

  const applyCoupon = (code: string, subtotal: number): { success: boolean; message: string } => {
    const found = coupons.find(
      (c) =>
        c.restaurant_id === currentRestaurant.id &&
        c.code.toUpperCase() === code.trim().toUpperCase() &&
        c.is_active
    );

    if (!found) {
      return { success: false, message: 'كود الكوبون غير صالح أو غير مفعل لهذا المطعم.' };
    }

    if (subtotal < found.min_order) {
      return {
        success: false,
        message: `الحد الأدنى للطلب لتفعيل هذا الكوبون هو ${found.min_order.toLocaleString()} ل.س`,
      };
    }

    setAppliedCoupon(found);
    return { success: true, message: `تم تفعيل الكوبون بنجاح!` };
  };

  const removeCoupon = () => {
    setAppliedCoupon(null);
  };

  // Orders
  const createOrder = (orderData: Partial<Order>): Order => {
    const randomNum = Math.floor(1000 + Math.random() * 9000);
    const trackingCode = `BSH-${randomNum}`;
    const newOrder: Order = {
      id: `ord-${Date.now()}`,
      restaurant_id: currentRestaurant.id,
      tracking_code: trackingCode,
      customer_name: orderData.customer_name || 'عميل محترم',
      customer_phone: orderData.customer_phone || '0900000000',
      customer_address: orderData.customer_address,
      delivery_type: orderData.delivery_type || 'delivery',
      restaurant_table_id: orderData.restaurant_table_id,
      table_number: orderData.table_number,
      status: 'pending',
      payment_method: orderData.payment_method || 'cash_on_delivery',
      payment_status: orderData.payment_status || 'pending',
      subtotal: orderData.subtotal || 0,
      delivery_fee: orderData.delivery_fee || 0,
      discount: orderData.discount || 0,
      total: orderData.total || 0,
      coupon_code: orderData.coupon_code,
      notes: orderData.notes,
      items: orderData.items || [],
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    setOrders((prev) => [newOrder, ...prev]);

    // If dine-in, mark table as occupied
    if (newOrder.delivery_type === 'dine_in' && newOrder.restaurant_table_id) {
      updateTableStatus(newOrder.restaurant_table_id, 'occupied');
    }

    clearCart();
    return newOrder;
  };

  const updateOrderStatus = (orderId: string, status: OrderStatus, extra?: Partial<Order>) => {
    setOrders((prev) =>
      prev.map((ord) => {
        if (ord.id === orderId) {
          const now = new Date().toISOString();
          const updated: Order = {
            ...ord,
            ...extra,
            status,
            updated_at: now,
          };
          if (status === 'delivered') {
            updated.delivered_at = now;
            updated.payment_status = 'paid';
            if (updated.restaurant_table_id) {
              updateTableStatus(updated.restaurant_table_id, 'available');
            }
          }
          return updated;
        }
        return ord;
      })
    );
  };

  const assignDriver = (orderId: string, driverId: string) => {
    const driver = drivers.find((d) => d.id === driverId);
    if (!driver) return;

    setOrders((prev) =>
      prev.map((ord) =>
        ord.id === orderId
          ? {
              ...ord,
              driver_id: driver.id,
              driver_name: driver.name,
              driver_phone: driver.phone,
              status: ord.status === 'pending' || ord.status === 'preparing' ? 'ready' : ord.status,
              driver_location: {
                lat: driver.current_location.lat,
                lng: driver.current_location.lng,
                updated_at: new Date().toISOString(),
              },
            }
          : ord
      )
    );

    setDrivers((prev) =>
      prev.map((d) =>
        d.id === driverId ? { ...d, active_orders_count: d.active_orders_count + 1 } : d
      )
    );
  };

  const rateDriver = (orderId: string, rating: number, comment?: string) => {
    setOrders((prev) =>
      prev.map((ord) =>
        ord.id === orderId ? { ...ord, driver_rating: rating, driver_comment: comment } : ord
      )
    );
  };

  const updateDriverLocation = (driverId: string, location: { lat: number; lng: number }) => {
    setDrivers((prev) =>
      prev.map((d) => (d.id === driverId ? { ...d, current_location: location } : d))
    );
    setOrders((prev) =>
      prev.map((ord) =>
        ord.driver_id === driverId
          ? {
              ...ord,
              driver_location: {
                ...location,
                updated_at: new Date().toISOString(),
              },
            }
          : ord
      )
    );
  };

  // Products
  const saveProduct = (product: Partial<Product>) => {
    setProducts((prev) => {
      if (product.id) {
        return prev.map((p) => (p.id === product.id ? ({ ...p, ...product } as Product) : p));
      }
      const newProd: Product = {
        id: `prod-${Date.now()}`,
        restaurant_id: currentRestaurant.id,
        category_id: product.category_id || categories[0]?.id || 'cat-1',
        name: product.name || 'New Item',
        name_ar: product.name_ar || 'صنف جديد',
        description: product.description || '',
        description_ar: product.description_ar || '',
        price: product.price || 0,
        price_type: product.price_type || 'fixed',
        variants: product.variants,
        image: product.image || 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
        is_available: product.is_available !== undefined ? product.is_available : true,
        calories: product.calories || 500,
        preparation_time_minutes: product.preparation_time_minutes || 15,
        rating: 5.0,
        reviews_count: 0,
      };
      return [...prev, newProd];
    });
  };

  const deleteProduct = (productId: string) => {
    setProducts((prev) => prev.filter((p) => p.id !== productId));
  };

  const toggleProductAvailability = (productId: string) => {
    setProducts((prev) =>
      prev.map((p) => (p.id === productId ? { ...p, is_available: !p.is_available } : p))
    );
  };

  // Categories
  const saveCategory = (category: Partial<Category>) => {
    setCategories((prev) => {
      if (category.id) {
        return prev.map((c) => (c.id === category.id ? ({ ...c, ...category } as Category) : c));
      }
      const newCat: Category = {
        id: `cat-${Date.now()}`,
        restaurant_id: currentRestaurant.id,
        name: category.name || 'New Category',
        name_ar: category.name_ar || 'تصنيف جديد',
        icon: category.icon || 'Utensils',
        sort_order: prev.length + 1,
        is_active: true,
      };
      return [...prev, newCat];
    });
  };

  const deleteCategory = (categoryId: string) => {
    setCategories((prev) => prev.filter((c) => c.id !== categoryId));
  };

  // Tables
  const saveTable = (table: Partial<RestaurantTable>) => {
    setTables((prev) => {
      if (table.id) {
        return prev.map((t) => (t.id === table.id ? ({ ...t, ...table } as RestaurantTable) : t));
      }
      const newTbl: RestaurantTable = {
        id: `tbl-${Date.now()}`,
        restaurant_id: currentRestaurant.id,
        table_number: table.table_number || `طاولة ${prev.length + 1}`,
        seats: table.seats || 4,
        status: 'available',
        qr_token: `token-${Date.now()}`,
      };
      return [...prev, newTbl];
    });
  };

  const updateTableStatus = (tableId: string, status: TableStatus) => {
    setTables((prev) =>
      prev.map((t) => (t.id === tableId ? { ...t, status } : t))
    );
  };

  // Drivers
  const saveDriver = (driver: Partial<Driver>) => {
    setDrivers((prev) => {
      if (driver.id) {
        return prev.map((d) => (d.id === driver.id ? ({ ...d, ...driver } as Driver) : d));
      }
      const newDrv: Driver = {
        id: `drv-${Date.now()}`,
        restaurant_id: currentRestaurant.id,
        name: driver.name || 'سائق جديد',
        phone: driver.phone || '0900000000',
        vehicle_type: driver.vehicle_type || 'دراجة نارية',
        plate_number: driver.plate_number || 'دمشق 0000',
        rating: 5.0,
        is_active: true,
        current_location: { lat: 33.5138, lng: 36.2765 },
        active_orders_count: 0,
      };
      return [...prev, newDrv];
    });
  };

  const toggleDriverStatus = (driverId: string) => {
    setDrivers((prev) =>
      prev.map((d) => (d.id === driverId ? { ...d, is_active: !d.is_active } : d))
    );
  };

  // Coupons
  const saveCoupon = (coupon: Partial<Coupon>) => {
    setCoupons((prev) => {
      if (coupon.id) {
        return prev.map((c) => (c.id === coupon.id ? ({ ...c, ...coupon } as Coupon) : c));
      }
      const newC: Coupon = {
        id: `cp-${Date.now()}`,
        restaurant_id: currentRestaurant.id,
        code: (coupon.code || 'SALE').toUpperCase(),
        discount_type: coupon.discount_type || 'percent',
        discount_value: coupon.discount_value || 10,
        min_order: coupon.min_order || 0,
        max_discount: coupon.max_discount,
        expires_at: coupon.expires_at || '2026-12-31',
        is_active: true,
        usage_count: 0,
      };
      return [...prev, newC];
    });
  };

  const deleteCoupon = (id: string) => {
    setCoupons((prev) => prev.filter((c) => c.id !== id));
  };

  // Expenses
  const addExpense = (expense: Omit<Expense, 'id' | 'restaurant_id'>) => {
    const newExp: Expense = {
      ...expense,
      id: `exp-${Date.now()}`,
      restaurant_id: currentRestaurant.id,
    };
    setExpenses((prev) => [newExp, ...prev]);
  };

  const deleteExpense = (id: string) => {
    setExpenses((prev) => prev.filter((e) => e.id !== id));
  };

  // Reviews
  const addReview = (review: Omit<Review, 'id' | 'restaurant_id' | 'date'>) => {
    const newRev: Review = {
      ...review,
      id: `rev-${Date.now()}`,
      restaurant_id: currentRestaurant.id,
      date: new Date().toISOString().split('T')[0],
    };
    setReviews((prev) => [newRev, ...prev]);
  };

  // Settings
  const updateRestaurantSettings = (updated: Partial<Restaurant>) => {
    setRestaurants((prev) =>
      prev.map((r) => (r.id === currentRestaurant.id ? { ...r, ...updated } : r))
    );
  };

  const updateThemeSettings = (settings: Partial<RestaurantThemeSettings>) => {
    setRestaurants((prev) =>
      prev.map((r) =>
        r.id === currentRestaurant.id
          ? {
              ...r,
              theme_settings: {
                ...r.theme_settings,
                ...settings,
              },
            }
          : r
      )
    );
  };

  const resetDemoData = () => {
    localStorage.clear();
    setRestaurants(INITIAL_RESTAURANTS);
    setCategories(INITIAL_CATEGORIES);
    setProducts(INITIAL_PRODUCTS);
    setOrders(INITIAL_ORDERS);
    setTables(INITIAL_TABLES);
    setDrivers(INITIAL_DRIVERS);
    setCoupons(INITIAL_COUPONS);
    setOffers(INITIAL_OFFERS);
    setExpenses(INITIAL_EXPENSES);
    setReviews(INITIAL_REVIEWS);
    setDeliveryProviders(INITIAL_DELIVERY_PROVIDERS);
    setCart([]);
    setAppliedCoupon(null);
  };

  return (
    <AppContext.Provider
      value={{
        restaurants,
        currentRestaurant,
        switchRestaurant,
        currentRole,
        setCurrentRole,
        currentLanguage,
        setCurrentLanguage,
        categories,
        products,
        orders,
        tables,
        drivers,
        coupons,
        offers,
        expenses,
        reviews,
        deliveryProviders,
        cart,
        addToCart,
        updateCartQuantity,
        removeFromCart,
        clearCart,
        appliedCoupon,
        applyCoupon,
        removeCoupon,
        createOrder,
        updateOrderStatus,
        assignDriver,
        rateDriver,
        updateDriverLocation,
        saveProduct,
        deleteProduct,
        toggleProductAvailability,
        saveCategory,
        deleteCategory,
        saveTable,
        updateTableStatus,
        scannedTable,
        setScannedTable,
        saveDriver,
        toggleDriverStatus,
        saveCoupon,
        deleteCoupon,
        addExpense,
        deleteExpense,
        addReview,
        updateRestaurantSettings,
        updateThemeSettings,
        resetDemoData,
      }}
    >
      {children}
    </AppContext.Provider>
  );
};

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp must be used within an AppProvider');
  }
  return context;
};
