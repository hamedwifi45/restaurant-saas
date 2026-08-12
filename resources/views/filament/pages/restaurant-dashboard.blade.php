@php
    $dashboardSummaryUrl = route('restaurant.dashboard.summary');
@endphp

<div>
<style>
  :root {
    --primary: #0a0a0a;
    --secondary: #c9a961;
    --accent: #d4af37;
    --accent-light: #e8d496;
    --bg: #f7f3ec;
    --surface: #ffffff;
    --surface-dark: #1a1410;
    --text: #1a1a1a;
    --muted: #6b6357;
    --cream: #f5efe4;
    --cream-dark: #e8dcc4;
    --border: rgba(201, 169, 97, 0.2);
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
  }
  * { font-family: 'Cairo', sans-serif; }
  .font-serif-ar { font-family: 'Amiri', serif; }
  .font-display { font-family: 'Playfair Display', serif; }
  body { background: var(--bg); color: var(--text); overflow-x: hidden; }
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: var(--cream); }
  ::-webkit-scrollbar-thumb { background: var(--secondary); border-radius: 3px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

  .gold-text {
    background: linear-gradient(135deg, #c9a961 0%, #e8d496 50%, #c9a961 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .sidebar-gradient {
    background: linear-gradient(180deg, #0a0a0a 0%, #1a1410 100%);
  }
  .nav-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border-right: 3px solid transparent;
  }
  .nav-item:hover { background: rgba(201, 169, 97, 0.08); }
  .nav-item.active {
    background: rgba(201, 169, 97, 0.12);
    border-right-color: var(--secondary);
  }
  .nav-item.active .nav-icon { color: var(--secondary); }
  .nav-item.active .nav-label { color: var(--secondary); }

  .stat-card {
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .stat-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
  }
  .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -15px rgba(10,10,10,0.15); }

  .card-hover { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
  .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -15px rgba(10,10,10,0.15); }

  .pulse-dot { animation: pulse 2s infinite; }
  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
  }
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .fade-in { animation: fadeInUp 0.5s ease-out forwards; }

  .btn-gold {
    background: linear-gradient(135deg, var(--primary) 0%, #1a1410 100%);
    color: var(--secondary);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
  }
  .btn-gold:hover {
    border-color: var(--secondary);
    box-shadow: 0 4px 20px rgba(201, 169, 97, 0.2);
    transform: translateY(-1px);
  }

  .status-new { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
  .status-preparing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
  .status-ready { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
  .status-delivered { background: rgba(107, 99, 87, 0.1); color: #6b6357; border: 1px solid rgba(107, 99, 87, 0.3); }
  .status-cancelled { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

  .table-available { background: linear-gradient(135deg, #10b981, #059669); }
  .table-occupied { background: linear-gradient(135deg, #ef4444, #dc2626); }
  .table-reserved { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .table-cleaning { background: linear-gradient(135deg, #3b82f6, #2563eb); }

  .view { display: none; }
  .view.active { display: block; animation: fadeInUp 0.4s ease-out; }

  .notification-panel {
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .notification-panel.open { transform: translateX(0); }

  .modal-overlay {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
  }
  .modal-overlay.open { opacity: 1; pointer-events: auto; }
  .modal-content {
    transform: scale(0.9) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .modal-overlay.open .modal-content { transform: scale(1) translateY(0); }

  .badge-count {
    background: linear-gradient(135deg, var(--secondary), var(--accent));
    color: var(--primary);
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(201, 169, 97, 0.4);
  }

  .live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .live-indicator::before {
    content: '';
    width: 8px; height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  }

  .progress-bar {
    background: linear-gradient(90deg, var(--secondary), var(--accent));
    transition: width 1s ease;
  }

  .menu-item-card {
    transition: all 0.3s ease;
  }
  .menu-item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 30px -10px rgba(10,10,10,0.15);
  }

  @media (max-width: 1024px) {
    .sidebar { transform: translateX(100%); transition: transform 0.3s ease; }
    .sidebar.open { transform: translateX(0); }
  }

  .shimmer {
    background: linear-gradient(90deg, transparent, rgba(201,169,97,0.1), transparent);
    background-size: 200% 100%;
    animation: shimmer 3s infinite;
  }
  @keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
  }

  .toast {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 9999;
  }
  .toast.show { transform: translateX(-50%) translateY(0); }
</style>

<div class="flex min-h-screen">

  <!-- Toast Notification -->
  <div id="toast" class="toast bg-[var(--primary)] text-white px-6 py-3 rounded-xl shadow-2xl border border-[var(--secondary)]/30 flex items-center gap-3" style="display: none;">
    <i class="fas fa-check-circle text-[var(--secondary)]"></i>
    <span id="toastMsg" class="text-sm">تمت العملية بنجاح</span>
  </div>

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar sidebar-gradient fixed lg:sticky top-0 right-0 h-screen w-72 z-40 flex flex-col shadow-2xl">
    <div class="p-6 border-b border-white/10">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full border border-[var(--secondary)] flex items-center justify-center bg-black/30">
            <span class="font-serif-ar text-[var(--secondary)] text-xl font-bold">ذ</span>
          </div>
          <div>
            <p class="font-serif-ar text-lg font-bold text-white">دار الذوق</p>
            <p class="text-[10px] text-[var(--secondary)] tracking-widest">لوحة التحكم</p>
          </div>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden text-white/60 hover:text-white">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <nav class="flex-1 py-6 px-3 overflow-y-auto">
      <p class="text-[10px] text-white/40 tracking-widest uppercase px-3 mb-3">القائمة الرئيسية</p>
      
      <button onclick="switchView('dashboard', this)" class="nav-item active w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-chart-pie text-white/60"></i>
          <span class="nav-label text-white/70">لوحة التحكم</span>
        </div>
      </button>
      <button onclick="switchView('orders', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-receipt text-white/60"></i>
          <span class="nav-label text-white/70">الطلبات</span>
        </div>
        <span class="badge-count text-[10px] px-2 py-0.5 rounded-full">12</span>
      </button>
      <button onclick="switchView('menu', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-utensils text-white/60"></i>
          <span class="nav-label text-white/70">القائمة</span>
        </div>
      </button>
      <button onclick="switchView('tables', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-chair text-white/60"></i>
          <span class="nav-label text-white/70">الطاولات</span>
        </div>
      </button>
      <button onclick="switchView('reservations', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-calendar-check text-white/60"></i>
          <span class="nav-label text-white/70">الحجوزات</span>
        </div>
        <span class="badge-count text-[10px] px-2 py-0.5 rounded-full">3</span>
      </button>
      <button onclick="switchView('staff', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-users text-white/60"></i>
          <span class="nav-label text-white/70">الموظفين</span>
        </div>
      </button>
      <button onclick="switchView('reports', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-chart-line text-white/60"></i>
          <span class="nav-label text-white/70">التقارير</span>
        </div>
      </button>
      <button onclick="switchView('settings', this)" class="nav-item w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm mb-1">
        <div class="flex items-center gap-3">
          <i class="nav-icon fas fa-cog text-white/60"></i>
          <span class="nav-label text-white/70">الإعدادات</span>
        </div>
      </button>
    </nav>

    <div class="p-4 border-t border-white/10">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[var(--secondary)] to-[var(--accent)] flex items-center justify-center text-[var(--primary)] font-bold">ف</div>
        <div class="flex-1 min-w-0">
          <p class="text-white text-sm font-semibold truncate">فاطمة السالم</p>
          <p class="text-white/50 text-xs truncate">مديرة المطعم</p>
        </div>
        <button class="text-white/40 hover:text-[var(--secondary)] transition">
          <i class="fas fa-sign-out-alt"></i>
        </button>
      </div>
    </div>
  </aside>

  <!-- Overlay for mobile -->
  <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

  <!-- Main Content -->
  <div class="flex-1 flex flex-col min-w-0">
    
    <!-- Header -->
    <header class="bg-white border-b border-[var(--border)] sticky top-0 z-20">
      <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-4">
          <button onclick="toggleSidebar()" class="lg:hidden text-[var(--primary)]">
            <i class="fas fa-bars text-xl"></i>
          </button>
          <div>
            <h1 id="pageTitle" class="font-serif-ar text-2xl font-bold text-[var(--primary)]">لوحة التحكم</h1>
            <p id="pageSubtitle" class="text-xs text-[var(--muted)] mt-0.5">نظرة عامة على أداء المطعم</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="hidden md:flex items-center gap-2 bg-[var(--cream)] rounded-xl px-4 py-2.5 w-64">
            <i class="fas fa-search text-[var(--muted)] text-sm"></i>
            <input type="text" placeholder="بحث..." class="bg-transparent outline-none text-sm flex-1 placeholder:text-[var(--muted)]">
          </div>
          <button onclick="toggleNotifications()" class="relative p-2.5 rounded-xl bg-[var(--cream)] hover:bg-[var(--cream-dark)] transition">
            <i class="fas fa-bell text-[var(--primary)]"></i>
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full pulse-dot"></span>
          </button>
          <div class="hidden md:flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[var(--cream)]">
            <i class="fas fa-clock text-[var(--muted)] text-sm"></i>
            <span id="liveTime" class="text-sm text-[var(--muted)]">--:--</span>
          </div>
          <div class="hidden md:flex items-center gap-2 px-3 py-2 rounded-xl bg-green-50 border border-green-200">
            <span class="live-indicator text-xs text-green-700 font-semibold">مفتوح الآن</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Notification Panel -->
    <div id="notifPanel" class="notification-panel fixed top-0 right-0 h-full w-96 max-w-full bg-white shadow-2xl z-50 overflow-y-auto">
      <div class="p-6 border-b border-[var(--border)] flex items-center justify-between sticky top-0 bg-white">
        <h3 class="font-serif-ar text-xl font-bold">الإشعارات</h3>
        <button onclick="toggleNotifications()" class="text-[var(--muted)] hover:text-[var(--primary)]">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-4 space-y-3">
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white flex-shrink-0">
              <i class="fas fa-receipt"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-sm">طلب جديد #1248</p>
              <p class="text-xs text-[var(--muted)] mt-1">أحمد الغامدي - طاولة T-07</p>
              <p class="text-xs text-blue-600 mt-2">منذ دقيقتين</p>
            </div>
          </div>
        </div>
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white flex-shrink-0">
              <i class="fas fa-calendar"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-sm">حجز جديد</p>
              <p class="text-xs text-[var(--muted)] mt-1">شركة التقنية - 12 شخص - غداً 1:00 م</p>
              <p class="text-xs text-amber-600 mt-2">منذ 15 دقيقة</p>
            </div>
          </div>
        </div>
        <div class="p-4 rounded-xl bg-green-50 border border-green-200">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white flex-shrink-0">
              <i class="fas fa-star"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-sm">تقييم جديد 5 نجوم</p>
              <p class="text-xs text-[var(--muted)] mt-1">"تجربة رائعة، الطعام ممتاز"</p>
              <p class="text-xs text-green-600 mt-2">منذ 30 دقيقة</p>
            </div>
          </div>
        </div>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white flex-shrink-0">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold text-sm">تنبيه المخزون</p>
              <p class="text-xs text-[var(--muted)] mt-1">مخزون الواغيو منخفض - يتبقى 3 قطع</p>
              <p class="text-xs text-red-600 mt-2">منذ ساعة</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto">

      <!-- ============ DASHBOARD VIEW ============ -->
      <div id="view-dashboard" class="view active p-6 space-y-6">
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="stat-card bg-white rounded-2xl p-6 shadow-sm fade-in" data-stat="sales">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[var(--secondary)] to-[var(--accent)] flex items-center justify-center text-[var(--primary)]">
                <i class="fas fa-coins text-xl"></i>
              </div>
              <div class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-green-50 text-green-600">
                <i class="fas fa-arrow-up text-[10px]"></i> 18.2%
              </div>
            </div>
            <p class="text-xs text-[var(--muted)] mb-1">مبيعات اليوم</p>
            <p id="stat-sales" class="font-display text-3xl font-bold text-[var(--primary)]">٩,٢٤٠ <span class="text-sm">ر.س</span></p>
            <p class="text-xs text-[var(--muted)] mt-2">مقابل ٧,٨٢٠ أمس</p>
          </div>

          <div class="stat-card bg-white rounded-2xl p-6 shadow-sm fade-in" data-stat="orders" style="animation-delay:0.1s">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white">
                <i class="fas fa-receipt text-xl"></i>
              </div>
              <div class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-green-50 text-green-600">
                <i class="fas fa-arrow-up text-[10px]"></i> +5
              </div>
            </div>
            <p class="text-xs text-[var(--muted)] mb-1">الطلبات النشطة</p>
            <p id="stat-orders" class="font-display text-3xl font-bold text-[var(--primary)]">٢٣</p>
            <p class="text-xs text-[var(--muted)] mt-2">٨ قيد التحضير</p>
          </div>

          <div class="stat-card bg-white rounded-2xl p-6 shadow-sm fade-in" data-stat="customers" style="animation-delay:0.2s">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white">
                <i class="fas fa-users text-xl"></i>
              </div>
              <div class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-green-50 text-green-600">
                <i class="fas fa-arrow-up text-[10px]"></i> 12.5%
              </div>
            </div>
            <p class="text-xs text-[var(--muted)] mb-1">العملاء اليوم</p>
            <p id="stat-customers" class="font-display text-3xl font-bold text-[var(--primary)]">١٨٧</p>
            <p class="text-xs text-[var(--muted)] mt-2">٤٢ عميل جديد</p>
          </div>

          <div class="stat-card bg-white rounded-2xl p-6 shadow-sm fade-in" data-stat="average" style="animation-delay:0.3s">
            <div class="flex items-start justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white">
                <i class="fas fa-star text-xl"></i>
              </div>
              <div class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-red-50 text-red-600">
                <i class="fas fa-arrow-down text-[10px]"></i> 3.2%
              </div>
            </div>
            <p class="text-xs text-[var(--muted)] mb-1">متوسط الطلب</p>
            <p id="stat-average" class="font-display text-3xl font-bold text-[var(--primary)]">١٨٥ <span class="text-sm">ر.س</span></p>
            <p class="text-xs text-[var(--muted)] mt-2">هدف الشهر: ٢٠٠ ر.س</p>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)]">المبيعات الأسبوعية</h3>
                <p class="text-xs text-[var(--muted)] mt-1">آخر ٧ أيام</p>
              </div>
              <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-[var(--secondary)]"></span>
                  <span class="text-[var(--muted)]">المبيعات</span>
                </div>
              </div>
            </div>
            <canvas id="salesChart" height="120"></canvas>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-1">الأصناف الأكثر طلباً</h3>
            <p class="text-xs text-[var(--muted)] mb-4">هذا الأسبوع</p>
            <canvas id="topItemsChart" height="200"></canvas>
            <div class="space-y-2 mt-4">
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#c9a961]"></span>
                  <span class="text-[var(--muted)]">ستيك الواغيو</span>
                </div>
                <span class="font-semibold">142</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#d4af37]"></span>
                  <span class="text-[var(--muted)]">مشاوي مشكلة</span>
                </div>
                <span class="font-semibold">118</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#e8d496]"></span>
                  <span class="text-[var(--muted)]">كبسة لحم</span>
                </div>
                <span class="font-semibold">95</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Second Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)]">الطلبات حسب الساعة</h3>
                <p class="text-xs text-[var(--muted)] mt-1">اليوم</p>
              </div>
              <span class="text-xs bg-[var(--cream)] px-3 py-1.5 rounded-full text-[var(--muted)]">
                ساعة الذروة: ٩ م
              </span>
            </div>
            <canvas id="hourlyChart" height="110"></canvas>
          </div>

          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-1">حالة الطاولات</h3>
            <p class="text-xs text-[var(--muted)] mb-6">١٢ طاولة</p>
            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 rounded-xl bg-green-50">
                <div class="flex items-center gap-3">
                  <span class="w-3 h-3 rounded-full bg-green-500"></span>
                  <span class="text-sm">شاغرة</span>
                </div>
                <span class="font-bold text-green-600">4</span>
              </div>
              <div class="flex items-center justify-between p-3 rounded-xl bg-red-50">
                <div class="flex items-center gap-3">
                  <span class="w-3 h-3 rounded-full bg-red-500"></span>
                  <span class="text-sm">مشغولة</span>
                </div>
                <span class="font-bold text-red-600">6</span>
              </div>
              <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50">
                <div class="flex items-center gap-3">
                  <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                  <span class="text-sm">محجوزة</span>
                </div>
                <span class="font-bold text-amber-600">1</span>
              </div>
              <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50">
                <div class="flex items-center gap-3">
                  <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                  <span class="text-sm">قيد التنظيف</span>
                </div>
                <span class="font-bold text-blue-600">1</span>
              </div>
            </div>
            <div class="mt-6 pt-6 border-t border-[var(--border)]">
              <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-[var(--muted)]">نسبة الإشغال</span>
                <span class="font-bold text-[var(--primary)]">٥٠٪</span>
              </div>
              <div class="h-2 bg-[var(--cream)] rounded-full overflow-hidden">
                <div class="progress-bar h-full rounded-full" style="width: 50%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <div class="p-6 border-b border-[var(--border)] flex items-center justify-between">
            <div>
              <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)]">الطلبات الأخيرة</h3>
              <p class="text-xs text-[var(--muted)] mt-1">آخر ٦ طلبات</p>
            </div>
            <button onclick="switchViewByName('orders')" class="text-sm text-[var(--secondary)] hover:underline">عرض الكل ←</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-[var(--cream)]">
                <tr>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">رقم الطلب</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">العميل</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الطاولة</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الأصناف</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الإجمالي</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الحالة</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الوقت</th>
                </tr>
              </thead>
              <tbody id="recentOrdersBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============ ORDERS VIEW ============ -->
      <div id="view-orders" class="view p-6 space-y-6">
        <div class="flex flex-wrap gap-2">
          <button onclick="filterOrders('all', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-[var(--primary)] text-[var(--secondary)]">الكل (12)</button>
          <button onclick="filterOrders('new', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">جديد (3)</button>
          <button onclick="filterOrders('preparing', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">قيد التحضير (4)</button>
          <button onclick="filterOrders('ready', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">جاهز (2)</button>
          <button onclick="filterOrders('delivered', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">تم التوصيل (2)</button>
          <button onclick="filterOrders('cancelled', this)" class="filter-btn px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">ملغى (1)</button>
        </div>

        <div id="ordersGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
      </div>

      <!-- ============ MENU VIEW ============ -->
      <div id="view-menu" class="view p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex flex-wrap gap-2">
            <button onclick="filterMenu('all', this)" class="menu-filter px-4 py-2 rounded-xl text-sm font-semibold bg-[var(--primary)] text-[var(--secondary)]">الكل</button>
            <button onclick="filterMenu('المقبلات', this)" class="menu-filter px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">المقبلات</button>
            <button onclick="filterMenu('الرئيسية', this)" class="menu-filter px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">الرئيسية</button>
            <button onclick="filterMenu('الحلويات', this)" class="menu-filter px-4 py-2 rounded-xl text-sm font-semibold bg-white text-[var(--muted)] hover:bg-[var(--cream)]">الحلويات</button>
          </div>
          <button onclick="showToast('سيتم فتح نموذج إضافة صنف جديد')" class="btn-gold px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة صنف جديد
          </button>
        </div>

        <div id="menuGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
      </div>

      <!-- ============ TABLES VIEW ============ -->
      <div id="view-tables" class="view p-6 space-y-6">
        <div class="flex flex-wrap gap-3 p-4 bg-white rounded-2xl shadow-sm">
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-sm">شاغرة</span></div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500"></span><span class="text-sm">مشغولة</span></div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500"></span><span class="text-sm">محجوزة</span></div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span><span class="text-sm">قيد التنظيف</span></div>
        </div>

        <div id="tablesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
      </div>

      <!-- ============ RESERVATIONS VIEW ============ -->
      <div id="view-reservations" class="view p-6 space-y-6">
        <div class="flex items-center justify-between">
          <p class="text-xs text-[var(--muted)]">٥ حجوزات قادمة</p>
          <button onclick="showToast('سيتم فتح نموذج حجز جديد')" class="btn-gold px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
            <i class="fas fa-plus"></i> حجز جديد
          </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-[var(--cream)]">
                <tr>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الضيف</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">عدد الأشخاص</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">التاريخ</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الوقت</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الطاولة</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الهاتف</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">الحالة</th>
                  <th class="text-right px-6 py-3 text-xs font-semibold text-[var(--muted)]">إجراءات</th>
                </tr>
              </thead>
              <tbody id="reservationsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============ STAFF VIEW ============ -->
      <div id="view-staff" class="view p-6 space-y-6">
        <div class="flex items-center justify-between">
          <p class="text-xs text-[var(--muted)]">٧ موظفين</p>
          <button onclick="showToast('سيتم فتح نموذج إضافة موظف')" class="btn-gold px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة موظف
          </button>
        </div>

        <div id="staffGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
      </div>

      <!-- ============ REPORTS VIEW ============ -->
      <div id="view-reports" class="view p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <p class="text-xs text-[var(--muted)] mb-2">إجمالي مبيعات الشهر</p>
            <p class="font-display text-3xl font-bold text-[var(--primary)]">٢٤٥,٨٩٠</p>
            <p class="text-xs text-green-600 mt-2 flex items-center gap-1">
              <i class="fas fa-arrow-up text-[10px]"></i> +٢٢.٤٪ عن الشهر الماضي
            </p>
          </div>
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <p class="text-xs text-[var(--muted)] mb-2">إجمالي الطلبات</p>
            <p class="font-display text-3xl font-bold text-[var(--primary)]">٢,٨٤٧</p>
            <p class="text-xs text-green-600 mt-2 flex items-center gap-1">
              <i class="fas fa-arrow-up text-[10px]"></i> +١٥.٨٪ عن الشهر الماضي
            </p>
          </div>
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <p class="text-xs text-[var(--muted)] mb-2">رضا العملاء</p>
            <p class="font-display text-3xl font-bold text-[var(--primary)]">٤.٨ / ٥</p>
            <div class="flex items-center gap-1 mt-2 text-[var(--secondary)]">
              <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">المبيعات الشهرية</h3>
            <canvas id="monthlyChart" height="140"></canvas>
          </div>
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">توزيع الأصناف</h3>
            <canvas id="categoryChart" height="140"></canvas>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">أداء الموظفين</h3>
          <canvas id="staffChart" height="80"></canvas>
        </div>
      </div>

      <!-- ============ SETTINGS VIEW ============ -->
      <div id="view-settings" class="view p-6 space-y-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">معلومات المطعم</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-[var(--muted)] mb-2 block">اسم المطعم</label>
              <input type="text" value="دار الذوق" class="w-full px-4 py-3 rounded-xl bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm">
            </div>
            <div>
              <label class="text-xs text-[var(--muted)] mb-2 block">رقم الهاتف</label>
              <input type="text" value="920012345" class="w-full px-4 py-3 rounded-xl bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm">
            </div>
            <div>
              <label class="text-xs text-[var(--muted)] mb-2 block">البريد الإلكتروني</label>
              <input type="email" value="info@daralthawq.sa" class="w-full px-4 py-3 rounded-xl bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm">
            </div>
            <div>
              <label class="text-xs text-[var(--muted)] mb-2 block">العنوان</label>
              <input type="text" value="طريق الملك فهد، الرياض" class="w-full px-4 py-3 rounded-xl bg-[var(--cream)] border border-transparent focus:border-[var(--secondary)] outline-none text-sm">
            </div>
          </div>
          <button onclick="showToast('تم حفظ التغييرات بنجاح')" class="btn-gold mt-6 px-6 py-2.5 rounded-xl text-sm font-semibold">حفظ التغييرات</button>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">ساعات العمل</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)]">
              <span class="text-sm font-semibold">السبت - الخميس</span>
              <div class="flex items-center gap-2">
                <input type="time" value="12:00" class="px-3 py-1.5 rounded-lg bg-white text-sm">
                <span class="text-[var(--muted)]">إلى</span>
                <input type="time" value="00:00" class="px-3 py-1.5 rounded-lg bg-white text-sm">
              </div>
            </div>
            <div class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)]">
              <span class="text-sm font-semibold">الجمعة</span>
              <div class="flex items-center gap-2">
                <input type="time" value="13:00" class="px-3 py-1.5 rounded-lg bg-white text-sm">
                <span class="text-[var(--muted)]">إلى</span>
                <input type="time" value="00:00" class="px-3 py-1.5 rounded-lg bg-white text-sm">
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <h3 class="font-serif-ar text-xl font-bold text-[var(--primary)] mb-6">الإشعارات</h3>
          <div class="space-y-3">
            <label class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)] cursor-pointer">
              <span class="text-sm">إشعارات الطلبات الجديدة</span>
              <input type="checkbox" checked class="w-5 h-5 accent-[var(--secondary)]">
            </label>
            <label class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)] cursor-pointer">
              <span class="text-sm">إشعارات الحجوزات</span>
              <input type="checkbox" checked class="w-5 h-5 accent-[var(--secondary)]">
            </label>
            <label class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)] cursor-pointer">
              <span class="text-sm">تنبيهات المخزون المنخفض</span>
              <input type="checkbox" checked class="w-5 h-5 accent-[var(--secondary)]">
            </label>
            <label class="flex items-center justify-between p-3 rounded-xl bg-[var(--cream)] cursor-pointer">
              <span class="text-sm">تقارير يومية بالبريد</span>
              <input type="checkbox" class="w-5 h-5 accent-[var(--secondary)]">
            </label>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
// ============ DATA ============
const orders = [
  { id: '#1247', customer: 'أحمد الغامدي', items: 4, total: 385, status: 'preparing', time: 'منذ 5 دقائق', table: 'T-07' },
  { id: '#1246', customer: 'نورة العتيبي', items: 2, total: 245, status: 'new', time: 'منذ 8 دقائق', table: 'T-12' },
  { id: '#1245', customer: 'خالد المطيري', items: 6, total: 620, status: 'ready', time: 'منذ 15 دقيقة', table: 'T-03' },
  { id: '#1244', customer: 'سارة القحطاني', items: 3, total: 195, status: 'delivered', time: 'منذ 25 دقيقة', table: 'T-09' },
  { id: '#1243', customer: 'فهد الدوسري', items: 5, total: 445, status: 'delivered', time: 'منذ 40 دقيقة', table: 'T-01' },
  { id: '#1242', customer: 'ريم الشمري', items: 2, total: 165, status: 'cancelled', time: 'منذ 55 دقيقة', table: '-' },
  { id: '#1241', customer: 'محمد العسيري', items: 3, total: 295, status: 'preparing', time: 'منذ ساعة', table: 'T-05' },
  { id: '#1240', customer: 'ليلى الحربي', items: 4, total: 520, status: 'new', time: 'منذ ساعة', table: 'T-11' },
  { id: '#1239', customer: 'عبدالرحمن السبيعي', items: 2, total: 180, status: 'preparing', time: 'منذ ساعة', table: 'T-02' },
  { id: '#1238', customer: 'منى الزهراني', items: 5, total: 670, status: 'ready', time: 'منذ ساعتين', table: 'T-08' },
  { id: '#1237', customer: 'سلطان القحطاني', items: 3, total: 340, status: 'new', time: 'منذ ساعتين', table: 'T-04' },
  { id: '#1236', customer: 'هند الشمري', items: 2, total: 210, status: 'preparing', time: 'منذ 3 ساعات', table: 'T-06' },
];

const menuItems = [
  { id: 1, name: 'سلطة الفتوش', category: 'المقبلات', price: 42, status: 'available', orders: 142, image: '🥗', rating: 4.8 },
  { id: 2, name: 'حمص بالطحينة', category: 'المقبلات', price: 38, status: 'available', orders: 118, image: '🫘', rating: 4.9 },
  { id: 3, name: 'ورق عنب بلحمة', category: 'المقبلات', price: 45, status: 'available', orders: 95, image: '🍃', rating: 5.0 },
  { id: 4, name: 'متبل باذنجان', category: 'المقبلات', price: 36, status: 'available', orders: 78, image: '🍆', rating: 4.7 },
  { id: 5, name: 'ستيك الواغيو', category: 'الرئيسية', price: 285, status: 'available', orders: 78, image: '🥩', rating: 5.0 },
  { id: 6, name: 'كبسة لحم الغنم', category: 'الرئيسية', price: 95, status: 'available', orders: 156, image: '🍚', rating: 4.9 },
  { id: 7, name: 'مشاوي مشكلة', category: 'الرئيسية', price: 120, status: 'available', orders: 134, image: '🍢', rating: 4.9 },
  { id: 8, name: 'هامور مشوي', category: 'الرئيسية', price: 145, status: 'unavailable', orders: 67, image: '🐟', rating: 4.7 },
  { id: 9, name: 'كنافة نابلسية', category: 'الحلويات', price: 35, status: 'available', orders: 98, image: '🍰', rating: 4.8 },
  { id: 10, name: 'أم علي', category: 'الحلويات', price: 32, status: 'available', orders: 72, image: '🥧', rating: 4.6 },
  { id: 11, name: 'بقلاوة بالفستق', category: 'الحلويات', price: 28, status: 'available', orders: 89, image: '🥮', rating: 4.9 },
  { id: 12, name: 'مهلبية', category: 'الحلويات', price: 25, status: 'available', orders: 54, image: '🍮', rating: 4.5 },
];

const tables = [
  { id: 'T-01', seats: 4, status: 'occupied', order: '#1240', time: '45 دقيقة' },
  { id: 'T-02', seats: 2, status: 'available' },
  { id: 'T-03', seats: 6, status: 'occupied', order: '#1245', time: '20 دقيقة' },
  { id: 'T-04', seats: 4, status: 'reserved', guest: 'العائلة المالكة', time: '8:00 م' },
  { id: 'T-05', seats: 2, status: 'cleaning' },
  { id: 'T-06', seats: 8, status: 'available' },
  { id: 'T-07', seats: 4, status: 'occupied', order: '#1247', time: '10 دقائق' },
  { id: 'T-08', seats: 2, status: 'available' },
  { id: 'T-09', seats: 6, status: 'occupied', order: '#1244', time: '60 دقيقة' },
  { id: 'T-10', seats: 4, status: 'reserved', guest: 'السيد عبدالله', time: '9:30 م' },
  { id: 'T-11', seats: 2, status: 'available' },
  { id: 'T-12', seats: 4, status: 'occupied', order: '#1246', time: '5 دقائق' },
];

const reservations = [
  { id: 1, name: 'العائلة المالكة', guests: 8, date: 'اليوم', time: '8:00 م', table: 'T-04', phone: '0501234567', status: 'confirmed' },
  { id: 2, name: 'السيد عبدالله المنصور', guests: 4, date: 'اليوم', time: '9:30 م', table: 'T-10', phone: '0559876543', status: 'confirmed' },
  { id: 3, name: 'شركة التقنية', guests: 12, date: 'غداً', time: '1:00 م', table: 'VIP', phone: '0112345678', status: 'pending' },
  { id: 4, name: 'منى الحربي', guests: 2, date: 'غداً', time: '7:00 م', table: 'T-08', phone: '0541112233', status: 'confirmed' },
  { id: 5, name: 'عمر السبيعي', guests: 6, date: 'بعد غد', time: '8:30 م', table: 'T-06', phone: '0567778899', status: 'pending' },
];

const staff = [
  { id: 1, name: 'عبدالله المنصور', role: 'الشيف التنفيذي', status: 'active', avatar: 'ع', shift: 'صباحي', orders: 48 },
  { id: 2, name: 'محمد الزهراني', role: 'شيف رئيسي', status: 'active', avatar: 'م', shift: 'صباحي', orders: 42 },
  { id: 3, name: 'فاطمة السالم', role: 'مديرة المطعم', status: 'active', avatar: 'ف', shift: 'كامل', orders: 0 },
  { id: 4, name: 'يوسف العنزي', role: 'نادل', status: 'active', avatar: 'ي', shift: 'مسائي', orders: 35 },
  { id: 5, name: 'ليلى الحارثي', role: 'نادلة', status: 'break', avatar: 'ل', shift: 'مسائي', orders: 28 },
  { id: 6, name: 'سلطان الغامدي', role: 'نادل', status: 'active', avatar: 'س', shift: 'مسائي', orders: 31 },
  { id: 7, name: 'هند القحطاني', role: 'محاسبة', status: 'offline', avatar: 'ه', shift: 'إداري', orders: 0 },
];

// ============ VIEW SWITCHING ============
const titles = {
  dashboard: { title: 'لوحة التحكم', subtitle: 'نظرة عامة على أداء المطعم' },
  orders: { title: 'الطلبات', subtitle: 'إدارة ومتابعة جميع الطلبات' },
  menu: { title: 'القائمة', subtitle: 'إدارة الأصناف والتصنيفات' },
  tables: { title: 'الطاولات', subtitle: 'حالة الطاولات والتوزيع' },
  reservations: { title: 'الحجوزات', subtitle: 'إدارة حجوزات الضيوف' },
  staff: { title: 'الموظفين', subtitle: 'فريق العمل والورديات' },
  reports: { title: 'التقارير', subtitle: 'تحليلات وإحصائيات مفصلة' },
  settings: { title: 'الإعدادات', subtitle: 'إعدادات المطعم والنظام' },
};
const dashboardSummaryUrl = @json($dashboardSummaryUrl);

async function refreshDashboardSummary() {
  try {
    const response = await fetch(dashboardSummaryUrl, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('فشل تحديث بيانات لوحة التحكم');
    }

    const payload = await response.json();

    if (!payload.success) {
      return;
    }

    const salesValue = Number(payload.summary.sales_today || 0);
    const activeOrdersValue = Number(payload.summary.active_orders || 0);
    const customerValue = Number(payload.summary.customers_today || 0);
    const averageValue = Number(payload.summary.average_order || 0);

    document.getElementById('stat-sales').innerHTML = `${formatNumber(salesValue)} <span class="text-sm">ر.س</span>`;
    document.getElementById('stat-orders').textContent = formatNumber(activeOrdersValue);
    document.getElementById('stat-customers').textContent = formatNumber(customerValue);
    document.getElementById('stat-average').innerHTML = `${formatNumber(averageValue)} <span class="text-sm">ر.س</span>`;

    const recentOrders = payload.recent_orders || orders;
    const tbody = document.getElementById('recentOrdersBody');
    tbody.innerHTML = recentOrders.slice(0, 6).map(o => `
      <tr class="border-b border-[var(--border)] hover:bg-[var(--cream)]/30 transition">
        <td class="px-6 py-4 text-sm font-semibold text-[var(--primary)]">${o.id}</td>
        <td class="px-6 py-4 text-sm">${o.customer}</td>
        <td class="px-6 py-4 text-sm font-mono text-[var(--muted)]">${o.table}</td>
        <td class="px-6 py-4 text-sm">${o.items} أصناف</td>
        <td class="px-6 py-4 text-sm font-semibold text-[var(--secondary)]">${o.total} ر.س</td>
        <td class="px-6 py-4"><span class="status-${o.status} text-xs px-3 py-1 rounded-full font-semibold">${statusLabel(o.status)}</span></td>
        <td class="px-6 py-4 text-xs text-[var(--muted)]">${o.time}</td>
      </tr>
    `).join('');
  } catch (error) {
    console.warn(error);
  }
}

function formatNumber(value) {
  return new Intl.NumberFormat('ar-SA').format(Math.round(value));
}

function statusLabel(status) {
  const labels = {
    new: 'جديد',
    preparing: 'قيد التحضير',
    ready: 'جاهز',
    delivered: 'تم التوصيل',
    cancelled: 'ملغى',
  };

  return labels[status] ?? 'جديد';
}

function switchView(viewId, btn) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.getElementById('view-' + viewId).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.getElementById('pageTitle').textContent = titles[viewId].title;
  document.getElementById('pageSubtitle').textContent = titles[viewId].subtitle;
  
  // Initialize charts if needed
  if (viewId === 'reports') setTimeout(initReportsCharts, 100);
  
  // Close sidebar on mobile
  if (window.innerWidth < 1024) toggleSidebar(false);
}

function switchViewByName(viewId) {
  const btn = Array.from(document.querySelectorAll('.nav-item')).find(b => b.textContent.includes(titles[viewId].title));
  switchView(viewId, btn);
}

function toggleSidebar(forceState) {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const isOpen = sidebar.classList.contains('open');
  const newState = forceState !== undefined ? forceState : !isOpen;
  
  if (newState) {
    sidebar.classList.add('open');
    overlay.classList.remove('hidden');
  } else {
    sidebar.classList.remove('open');
    overlay.classList.add('hidden');
  }
}

function toggleNotifications() {
  document.getElementById('notifPanel').classList.toggle('open');
}

function showToast(msg) {
  const toast = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ============ RENDER FUNCTIONS ============
function renderRecentOrders() {
  const tbody = document.getElementById('recentOrdersBody');
  const statusLabels = { new: 'جديد', preparing: 'قيد التحضير', ready: 'جاهز', delivered: 'تم التوصيل', cancelled: 'ملغى' };
  tbody.innerHTML = orders.slice(0, 6).map(o => `
    <tr class="border-b border-[var(--border)] hover:bg-[var(--cream)]/30 transition">
      <td class="px-6 py-4 text-sm font-semibold text-[var(--primary)]">${o.id}</td>
      <td class="px-6 py-4 text-sm">${o.customer}</td>
      <td class="px-6 py-4 text-sm font-mono text-[var(--muted)]">${o.table}</td>
      <td class="px-6 py-4 text-sm">${o.items} أصناف</td>
      <td class="px-6 py-4 text-sm font-semibold text-[var(--secondary)]">${o.total} ر.س</td>
      <td class="px-6 py-4"><span class="status-${o.status} text-xs px-3 py-1 rounded-full font-semibold">${statusLabels[o.status]}</span></td>
      <td class="px-6 py-4 text-xs text-[var(--muted)]">${o.time}</td>
    </tr>
  `).join('');
}

function renderOrders(filter = 'all') {
  const grid = document.getElementById('ordersGrid');
  const statusLabels = { new: 'جديد', preparing: 'قيد التحضير', ready: 'جاهز', delivered: 'تم التوصيل', cancelled: 'ملغى' };
  const filtered = filter === 'all' ? orders : orders.filter(o => o.status === filter);
  
  grid.innerHTML = filtered.map((o, idx) => `
    <div class="bg-white rounded-2xl p-5 shadow-sm card-hover border border-[var(--border)] fade-in" style="animation-delay:${idx * 0.05}s">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[var(--secondary)] to-[var(--accent)] flex items-center justify-center text-[var(--primary)] font-bold text-sm">
            ${o.id.slice(1)}
          </div>
          <div>
            <p class="font-bold text-[var(--primary)]">${o.id}</p>
            <p class="text-xs text-[var(--muted)]">${o.time}</p>
          </div>
        </div>
        <span class="status-${o.status} text-xs px-3 py-1 rounded-full font-semibold">${statusLabels[o.status]}</span>
      </div>
      <div class="space-y-2 mb-4 pb-4 border-b border-[var(--border)]">
        <div class="flex items-center justify-between text-sm">
          <span class="text-[var(--muted)]">العميل</span>
          <span class="font-semibold">${o.customer}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-[var(--muted)]">الطاولة</span>
          <span class="font-mono">${o.table}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-[var(--muted)]">الأصناف</span>
          <span>${o.items} أصناف</span>
        </div>
      </div>
      <div class="flex items-center justify-between">
        <p class="font-display text-xl font-bold text-[var(--secondary)]">${o.total} <span class="text-xs">ر.س</span></p>
        <div class="flex items-center gap-2">
          <button onclick="showToast('تم فتح تفاصيل الطلب ${o.id}')" class="p-2 rounded-lg bg-[var(--cream)] hover:bg-[var(--cream-dark)] transition">
            <i class="fas fa-eye text-sm"></i>
          </button>
          <button onclick="showToast('تم تحديث حالة الطلب ${o.id}')" class="p-2 rounded-lg bg-[var(--primary)] text-[var(--secondary)] hover:opacity-90 transition">
            <i class="fas fa-check text-sm"></i>
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function filterOrders(status, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => {
    b.classList.remove('bg-[var(--primary)]', 'text-[var(--secondary)]');
    b.classList.add('bg-white', 'text-[var(--muted)]');
  });
  btn.classList.remove('bg-white', 'text-[var(--muted)]');
  btn.classList.add('bg-[var(--primary)]', 'text-[var(--secondary)]');
  renderOrders(status);
}

function renderMenu(filter = 'all') {
  const grid = document.getElementById('menuGrid');
  const filtered = filter === 'all' ? menuItems : menuItems.filter(i => i.category === filter);
  
  grid.innerHTML = filtered.map((item, idx) => `
    <div class="menu-item-card bg-white rounded-2xl overflow-hidden shadow-sm border border-[var(--border)] fade-in" style="animation-delay:${idx * 0.05}s">
      <div class="aspect-[4/3] bg-gradient-to-br from-[var(--cream)] to-[var(--cream-dark)] flex items-center justify-center text-6xl relative">
        ${item.image}
        <span class="absolute top-3 right-3 text-xs px-2 py-1 rounded-full font-semibold ${item.status === 'available' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
          ${item.status === 'available' ? 'متاح' : 'غير متاح'}
        </span>
      </div>
      <div class="p-4">
        <div class="flex items-start justify-between mb-2">
          <h4 class="font-serif-ar text-lg font-bold text-[var(--primary)]">${item.name}</h4>
          <span class="font-bold text-[var(--secondary)]">${item.price} ر.س</span>
        </div>
        <p class="text-xs text-[var(--muted)] mb-3">${item.category}</p>
        <div class="flex items-center justify-between text-xs text-[var(--muted)] mb-4">
          <span>${item.orders} طلب هذا الأسبوع</span>
          <div class="flex items-center gap-1 text-[var(--secondary)]">
            <i class="fas fa-star text-[10px]"></i>
            <span>${item.rating}</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="showToast('تم فتح تعديل ${item.name}')" class="flex-1 py-2 rounded-lg bg-[var(--cream)] hover:bg-[var(--cream-dark)] transition text-sm font-semibold flex items-center justify-center gap-1">
            <i class="fas fa-edit text-xs"></i> تعديل
          </button>
          <button onclick="showToast('تم حذف ${item.name}')" class="p-2 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition">
            <i class="fas fa-trash text-xs"></i>
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function filterMenu(category, btn) {
  document.querySelectorAll('.menu-filter').forEach(b => {
    b.classList.remove('bg-[var(--primary)]', 'text-[var(--secondary)]');
    b.classList.add('bg-white', 'text-[var(--muted)]');
  });
  btn.classList.remove('bg-white', 'text-[var(--muted)]');
  btn.classList.add('bg-[var(--primary)]', 'text-[var(--secondary)]');
  renderMenu(category);
}

function renderTables() {
  const grid = document.getElementById('tablesGrid');
  const statusLabels = { available: 'شاغرة', occupied: 'مشغولة', reserved: 'محجوزة', cleaning: 'قيد التنظيف' };
  
  grid.innerHTML = tables.map((t, idx) => `
    <div onclick="showToast('طاولة ${t.id} - ${statusLabels[t.status]}')" class="table-${t.status} rounded-2xl p-5 text-white shadow-lg card-hover cursor-pointer relative overflow-hidden fade-in" style="animation-delay:${idx * 0.05}s">
      <div class="absolute top-2 left-2 opacity-20">
        <i class="fas fa-chair text-5xl"></i>
      </div>
      <div class="relative">
        <p class="font-display text-2xl font-bold mb-1">${t.id}</p>
        <p class="text-xs opacity-80 mb-3">${t.seats} مقاعد</p>
        <p class="text-xs font-semibold bg-white/20 backdrop-blur-sm rounded-lg px-2 py-1 inline-block">
          ${statusLabels[t.status]}
        </p>
        ${t.order ? `<p class="text-xs mt-2 opacity-90">طلب: ${t.order}</p>` : ''}
        ${t.guest ? `<p class="text-xs mt-2 opacity-90 truncate">${t.guest}</p>` : ''}
        ${t.time ? `<p class="text-xs mt-1 opacity-75 flex items-center gap-1"><i class="fas fa-clock"></i> ${t.time}</p>` : ''}
      </div>
    </div>
  `).join('');
}

function renderReservations() {
  const tbody = document.getElementById('reservationsBody');
  tbody.innerHTML = reservations.map(r => `
    <tr class="border-b border-[var(--border)] hover:bg-[var(--cream)]/30 transition">
      <td class="px-6 py-4">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[var(--secondary)] to-[var(--accent)] flex items-center justify-center text-[var(--primary)] font-bold text-sm">
            ${r.name.charAt(0)}
          </div>
          <span class="font-semibold text-sm">${r.name}</span>
        </div>
      </td>
      <td class="px-6 py-4 text-sm">${r.guests} أشخاص</td>
      <td class="px-6 py-4 text-sm">${r.date}</td>
      <td class="px-6 py-4 text-sm font-semibold">${r.time}</td>
      <td class="px-6 py-4 text-sm font-mono">${r.table}</td>
      <td class="px-6 py-4 text-sm text-[var(--muted)]" dir="ltr">${r.phone}</td>
      <td class="px-6 py-4">
        <span class="text-xs px-3 py-1 rounded-full font-semibold ${r.status === 'confirmed' ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-amber-50 text-amber-600 border border-amber-200'}">
          ${r.status === 'confirmed' ? 'مؤكد' : 'قيد الانتظار'}
        </span>
      </td>
      <td class="px-6 py-4">
        <div class="flex items-center gap-1">
          <button onclick="showToast('عرض تفاصيل ${r.name}')" class="p-1.5 rounded-lg hover:bg-[var(--cream)] transition"><i class="fas fa-eye text-sm"></i></button>
          <button onclick="showToast('تعديل ${r.name}')" class="p-1.5 rounded-lg hover:bg-[var(--cream)] transition"><i class="fas fa-edit text-sm"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderStaff() {
  const grid = document.getElementById('staffGrid');
  grid.innerHTML = staff.map((m, idx) => `
    <div class="bg-white rounded-2xl p-5 shadow-sm card-hover border border-[var(--border)] fade-in" style="animation-delay:${idx * 0.05}s">
      <div class="flex items-start justify-between mb-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[var(--secondary)] to-[var(--accent)] flex items-center justify-center text-[var(--primary)] font-bold text-xl">
          ${m.avatar}
        </div>
        <span class="text-xs px-2 py-1 rounded-full font-semibold ${
          m.status === 'active' ? 'bg-green-50 text-green-600' :
          m.status === 'break' ? 'bg-amber-50 text-amber-600' :
          'bg-gray-100 text-gray-600'
        }">
          ${m.status === 'active' ? 'نشط' : m.status === 'break' ? 'استراحة' : 'غير متصل'}
        </span>
      </div>
      <h4 class="font-serif-ar text-lg font-bold text-[var(--primary)] mb-1">${m.name}</h4>
      <p class="text-sm text-[var(--muted)] mb-4">${m.role}</p>
      <div class="flex items-center justify-between pt-4 border-t border-[var(--border)]">
        <span class="text-xs text-[var(--muted)]">الوردية: ${m.shift}</span>
        <button onclick="showToast('عرض تفاصيل ${m.name}')" class="text-[var(--secondary)] hover:underline text-xs">التفاصيل</button>
      </div>
    </div>
  `).join('');
}

// ============ CHARTS ============
let chartsInitialized = false;
function initCharts() {
  if (chartsInitialized) return;
  chartsInitialized = true;

  // Sales Chart
  const salesCtx = document.getElementById('salesChart').getContext('2d');
  const salesGradient = salesCtx.createLinearGradient(0, 0, 0, 300);
  salesGradient.addColorStop(0, 'rgba(201, 169, 97, 0.4)');
  salesGradient.addColorStop(1, 'rgba(201, 169, 97, 0)');
  
  new Chart(salesCtx, {
    type: 'line',
    data: {
      labels: ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'],
      datasets: [{
        label: 'المبيعات',
        data: [4200, 3800, 5100, 4700, 6200, 7800, 9200],
        borderColor: '#c9a961',
        backgroundColor: salesGradient,
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#c9a961',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
          titleFont: { family: 'Cairo' },
          bodyFont: { family: 'Cairo' },
        }
      },
      scales: {
        y: { grid: { color: '#f5efe4' }, ticks: { font: { family: 'Cairo' } } },
        x: { grid: { display: false }, ticks: { font: { family: 'Cairo' } } }
      }
    }
  });

  // Top Items Chart
  new Chart(document.getElementById('topItemsChart'), {
    type: 'doughnut',
    data: {
      labels: ['ستيك الواغيو', 'مشاوي', 'كبسة', 'ورق عنب', 'كنافة'],
      datasets: [{
        data: [142, 118, 95, 78, 64],
        backgroundColor: ['#c9a961', '#d4af37', '#e8d496', '#8b6f47', '#6b5437'],
        borderWidth: 0,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
        }
      }
    }
  });

  // Hourly Chart
  new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
      labels: ['12م', '1م', '2م', '3م', '4م', '5م', '6م', '7م', '8م', '9م', '10م', '11م'],
      datasets: [{
        label: 'الطلبات',
        data: [12, 28, 35, 18, 15, 22, 38, 52, 68, 74, 58, 32],
        backgroundColor: '#c9a961',
        borderRadius: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
        }
      },
      scales: {
        y: { grid: { color: '#f5efe4' }, ticks: { font: { family: 'Cairo' } } },
        x: { grid: { display: false }, ticks: { font: { family: 'Cairo', size: 10 } } }
      }
    }
  });
}

function initReportsCharts() {
  // Monthly Chart
  const monthlyCtx = document.getElementById('monthlyChart');
  if (monthlyCtx._chartInit) return;
  monthlyCtx._chartInit = true;
  
  const monthlyGrad = monthlyCtx.getContext('2d').createLinearGradient(0, 0, 0, 300);
  monthlyGrad.addColorStop(0, 'rgba(201, 169, 97, 0.3)');
  monthlyGrad.addColorStop(1, 'rgba(201, 169, 97, 0)');

  new Chart(monthlyCtx, {
    type: 'line',
    data: {
      labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
      datasets: [{
        label: 'المبيعات',
        data: [180000, 195000, 210000, 225000, 240000, 235000, 250000, 260000, 245000, 255000, 265000, 280000],
        borderColor: '#c9a961',
        backgroundColor: monthlyGrad,
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#c9a961',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
        }
      },
      scales: {
        y: { grid: { color: '#f5efe4' }, ticks: { font: { family: 'Cairo' } } },
        x: { grid: { display: false }, ticks: { font: { family: 'Cairo' } } }
      }
    }
  });

  // Category Chart
  new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
      labels: ['المقبلات', 'الرئيسية', 'الحلويات', 'المشروبات'],
      datasets: [{
        label: 'المبيعات',
        data: [45000, 120000, 35000, 25000],
        backgroundColor: ['#c9a961', '#d4af37', '#e8d496', '#8b6f47'],
        borderRadius: 8,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
        }
      },
      scales: {
        y: { grid: { color: '#f5efe4' }, ticks: { font: { family: 'Cairo' } } },
        x: { grid: { display: false }, ticks: { font: { family: 'Cairo' } } }
      }
    }
  });

  // Staff Performance Chart
  new Chart(document.getElementById('staffChart'), {
    type: 'bar',
    data: {
      labels: staff.filter(s => s.orders > 0).map(s => s.name),
      datasets: [{
        label: 'الطلبات المنجزة',
        data: staff.filter(s => s.orders > 0).map(s => s.orders),
        backgroundColor: '#c9a961',
        borderRadius: 8,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0a0a0a',
          borderColor: '#c9a961',
          borderWidth: 1,
          titleColor: '#c9a961',
          bodyColor: '#fff',
          padding: 12,
          cornerRadius: 12,
        }
      },
      scales: {
        x: { grid: { color: '#f5efe4' }, ticks: { font: { family: 'Cairo' } } },
        y: { grid: { display: false }, ticks: { font: { family: 'Cairo' } } }
      }
    }
  });
}

// ============ LIVE CLOCK ============
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  document.getElementById('liveTime').textContent = `${h}:${m}`;
}

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
  renderRecentOrders();
  renderOrders();
  renderMenu();
  renderTables();
  renderReservations();
  renderStaff();
  initCharts();
  updateClock();
  refreshDashboardSummary();
  setInterval(updateClock, 1000);
  setInterval(refreshDashboardSummary, 30000);
});
</script>
</div>
