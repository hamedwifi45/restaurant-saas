export type Role = 'super_admin' | 'restaurant_admin' | 'kitchen_staff' | 'driver' | 'customer';

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'on_way' | 'delivered' | 'cancelled';

export type DeliveryType = 'delivery' | 'takeaway' | 'dine_in';

export type PaymentMethod = 'cash_on_delivery' | 'card' | 'bank_transfer';

export type PaymentStatus = 'pending' | 'paid' | 'refunded';

export type TableStatus = 'available' | 'occupied' | 'reserved';

export interface ProductVariant {
  name: string;
  name_ar?: string;
  price: number;
}

export interface Product {
  id: string;
  restaurant_id: string;
  category_id: string;
  name: string;
  name_ar: string;
  description: string;
  description_ar: string;
  price: number;
  price_type: 'fixed' | 'variants';
  variants?: ProductVariant[];
  image: string;
  is_available: boolean;
  calories?: number;
  preparation_time_minutes?: number;
  rating?: number;
  reviews_count?: number;
}

export interface Category {
  id: string;
  restaurant_id: string;
  name: string;
  name_ar: string;
  icon?: string;
  sort_order: number;
  is_active: boolean;
}

export interface OrderItem {
  id: string;
  product_id: string;
  product_name: string;
  product_name_ar: string;
  variant_name?: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  notes?: string;
}

export interface Order {
  id: string;
  restaurant_id: string;
  tracking_code: string;
  customer_name: string;
  customer_phone: string;
  customer_address?: string;
  delivery_type: DeliveryType;
  restaurant_table_id?: string;
  table_number?: string;
  status: OrderStatus;
  payment_method: PaymentMethod;
  payment_status: PaymentStatus;
  subtotal: number;
  delivery_fee: number;
  discount: number;
  total: number;
  coupon_code?: string;
  notes?: string;
  items: OrderItem[];
  created_at: string;
  updated_at: string;
  driver_id?: string;
  driver_name?: string;
  driver_phone?: string;
  driver_rating?: number;
  driver_comment?: string;
  driver_arrived_at?: string;
  delivered_at?: string;
  driver_location?: {
    lat: number;
    lng: number;
    updated_at: string;
  };
  bank_receipt_url?: string;
}

export interface RestaurantTable {
  id: string;
  restaurant_id: string;
  table_number: string;
  seats: number;
  status: TableStatus;
  qr_token: string;
}

export interface Driver {
  id: string;
  restaurant_id: string;
  name: string;
  phone: string;
  vehicle_type: string;
  plate_number: string;
  rating: number;
  is_active: boolean;
  current_location: {
    lat: number;
    lng: number;
  };
  active_orders_count: number;
}

export interface Coupon {
  id: string;
  restaurant_id: string;
  code: string;
  discount_type: 'percent' | 'fixed';
  discount_value: number;
  min_order: number;
  max_discount?: number;
  expires_at: string;
  is_active: boolean;
  usage_count: number;
}

export interface Offer {
  id: string;
  restaurant_id: string;
  title: string;
  title_ar: string;
  description: string;
  description_ar: string;
  discount_percent: number;
  image: string;
  is_active: boolean;
  valid_until: string;
  product_ids?: string[];
}

export interface Expense {
  id: string;
  restaurant_id: string;
  title: string;
  category: 'supplies' | 'ingredients' | 'rent' | 'utilities' | 'salaries' | 'other';
  amount: number;
  date: string;
  notes?: string;
}

export interface Review {
  id: string;
  restaurant_id: string;
  order_tracking_code?: string;
  customer_name: string;
  rating: number;
  comment: string;
  date: string;
}

export interface RestaurantThemeSettings {
  primary_color: string;
  secondary_color: string;
  background_color: string;
  font_family: string;
  header_style: 'sticky' | 'transparent';
  show_hero_banner: boolean;
  hero_title: string;
  hero_subtitle: string;
  border_radius: string;
  card_style: 'elevated' | 'flat';
}

export interface Restaurant {
  id: string;
  slug: string;
  name: string;
  name_ar: string;
  subdomain: string;
  description: string;
  description_ar: string;
  phone: string;
  email: string;
  address: string;
  city: string;
  delivery_fee: number;
  estimated_delivery_time: number; // minutes
  plan: 'starter' | 'pro' | 'enterprise';
  is_active: boolean;
  logo: string;
  banner: string;
  theme_id: string;
  bank_details?: string;
  operating_hours: {
    open: string;
    close: string;
    days: string;
  };
  theme_settings: RestaurantThemeSettings;
}

export interface DeliveryProvider {
  id: string;
  name: string;
  contact_phone: string;
  service_type: string;
  is_active: boolean;
  active_deliveries: number;
}
