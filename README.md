# Restaurant SaaS Multi-Tenant Platform (React & TypeScript)

A production-grade, multi-tenant restaurant management SaaS application rewritten in **React 19**, **TypeScript**, **Vite**, and **Tailwind CSS**.

---

## 🌟 Core Features & Modules

### 1. 🛍️ Customer Storefront & Online Ordering (`/{slug}`)
- **Restaurant Switcher**: Switch between tenant brands (e.g. *Al Basha Burger*, *Qasr Al-Sham*, *Silver Chain*).
- **Dynamic Theme & Hero Banner**: Live styling reflecting custom colors, font family (`Tajawal`, `Cairo`, `Inter`), and banners.
- **Interactive Menu by Category**: Real-time filtering, product search, calorie indicators, and preparation time.
- **Product Customization Modal**: Choose size/meal variants (Single, Combo meal, Double Max), with custom instructions.
- **Special Offers & Coupon Discounts**: Apply coupon codes (`BASHA20`, `WELCOME10`, `FREEDEL`) with live subtotal validations.
- **Flexible Checkout Options**:
  - 🛵 **Home Delivery**: Delivery fee calculation, detailed address inputs.
  - 🥡 **Takeaway / Pickup**: Zero delivery fees.
  - 🍽️ **Dine-In (الصالة)**: Direct table selection or auto-locking via Table QR session.
- **Payment Methods**: Cash on Delivery, Electronic POS / Card, and Bank Transfer with account details.
- **Live Order Progress & Tracking (`/track/{tracking_code}`)**: Real-time 5-stage timeline pipeline (`Received` ➔ `Preparing` ➔ `Ready` ➔ `On The Way` ➔ `Delivered`), simulated driver GPS movement on an animated map, direct driver call, and post-delivery driver rating.
- **Formal Printable Tax Invoice (`فاتورة ضريبية`)**: Itemized billing, tax numbers, QR code representation, and printer-optimized layout.
- **Dine-In Table QR Simulation**: Simulate customer mobile scan to lock their session to specific dine-in tables.

### 2. 📊 Restaurant Management Portal (`/restaurant`)
- **Executive Dashboard**: Real-time revenue analytics, active orders count, average ticket size, and dining room table occupancy.
- **Sales & Demand Breakdown**: Visual breakdown by delivery type (Delivery, Dine-In, Takeaway) and top-selling dishes.
- **Menu Management**: Full CRUD operations for categories and products, variant configurations, nutritional calories, and instant availability toggles.
- **Order Management Lifecycle**: Filter by state (`pending`, `preparing`, `ready`, `on_way`, `delivered`, `cancelled`), assign drivers, and review receipts.
- **Point of Sale (POS) (`/pos`)**: Fast touchscreen register for cashiers/waiters, quick product taps, instant order placement, and thermal receipt printing.
- **Kitchen Display System (KDS) (`/kds`)**: 3-column live kitchen board (New Orders, Cooking / Preparing, Ready for Delivery), itemized checklist for chefs, and elapsed timers with color alerts.
- **Tables & Dine-In Management**: Floor tables grid, seat capacity, table status (`available`, `occupied`, `reserved`), and one-click printable QR table stands.
- **Fleet & Driver Logistics**: Active drivers list, vehicle assignments, phone dispatch, live GPS coordinates, and rating averages.
- **Coupons & Promotions**: Manage promo codes, percentage or fixed discounts, minimum spend thresholds, and expiry dates.
- **Financial Analytics & Expense Ledger**: Log ingredient supplies, utilities, salaries, and rent; track Gross Revenue minus Expenses to calculate Net Profit.
- **Live Theme Customizer**: Visual theme editor adjusting brand primary/secondary colors, background shades, Arabic typography, card border radius, and header layouts with live preview.

### 3. 🛵 Delivery Driver Portal (`/driver`)
- Mobile-first interface for couriers.
- Queue of assigned deliveries with customer address and phone link.
- Action pipeline: "Accept Order" ➔ "Picked Up & On the Way" ➔ "Mark Delivered & Paid".
- GPS simulation tool to transmit coordinates to customer tracking maps.

### 4. 👑 Super Admin Platform ("Sham Admin")
- Platform-wide aggregate metrics (Total Tenant Restaurants, Consolidated Platform Revenue, Total Processed Orders).
- Tenant Directory with subscription tiers (`Starter`, `Pro`, `Enterprise`).
- One-click "Impersonate / Manage Restaurant" functionality.
- Delivery Provider logistics directory.
- Global theme repository.

---

## 🛠️ Technology Stack
- **Framework**: React 19 + TypeScript
- **Bundler & Dev Server**: Vite 8 (`--host 0.0.0.0 --port 3000`)
- **Styling**: Tailwind CSS v4 + Custom Theme CSS Variables
- **Icons**: Lucide React
- **Celebrations**: Canvas Confetti
- **State & Storage**: React Context API with LocalStorage synchronization

---

## 🚀 Getting Started

```bash
# Install dependencies
npm install

# Start development server on port 3000
npm run dev

# Build for production
npm run build
```
