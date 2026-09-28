<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: https://*.tile.openstreetmap.org; connect-src 'self' https://router.project-osrm.org;">
    <title>نظام تتبع الطلبات والتوصيل</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Fontsource Inter -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/css/inter@latest/arabic.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/css/inter@latest/latin.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ============================================
           المتغيرات الأساسية - يتم استيرادها من PHP
           ============================================ */
        :root {
            /* الألوان الأساسية - يتم استيرادها من PHP */
            --primary-color: #3B82F6;
            --primary-light: #60A5FA;
            --primary-dark: #2563EB;
            --secondary-color: #10B981;
            --secondary-light: #34D399;
            --accent-color: #F59E0B;
            --accent-light: #FBBF24;
            --danger-color: #EF4444;
            --danger-light: #F87171;
            --success-color: #22C55E;
            --info-color: #06B6D4;

            /* ألوان الخلفيات */
            --bg-primary: #FFFFFF;
            --bg-secondary: #F3F4F6;
            --bg-tertiary: #E5E7EB;
            --bg-dark: #1F2937;
            --bg-darker: #111827;
            --bg-overlay: rgba(0, 0, 0, 0.5);
            --bg-card: #FFFFFF;
            --bg-card-hover: #F9FAFB;

            /* النصوص */
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-light: #9CA3AF;
            --text-white: #FFFFFF;
            --text-link: #3B82F6;

            /* الحدود والظلال */
            --border-color: #E5E7EB;
            --border-light: #F3F4F6;
            --border-radius-sm: 0.375rem;
            --border-radius: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            --border-radius-full: 9999px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);

            /* المسافات */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;

            /* الخطوط */
            --font-family: 'Inter', system-ui, -apple-system, sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-weight-normal: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
            --line-height-tight: 1.25;
            --line-height-normal: 1.5;

            /* الانتقالات */
            --transition-fast: 150ms ease;
            --transition-normal: 300ms ease;
            --transition-slow: 500ms ease;

            /* أبعاد */
            --header-height: 4rem;
            --sidebar-width: 400px;
            --map-min-height: 400px;

            /* Z-index */
            --z-dropdown: 100;
            --z-sticky: 200;
            --z-overlay: 300;
            --z-modal: 400;
            --z-toast: 500;
        }

        /* ============================================
           إعادة التعيين والأساسيات
           ============================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: var(--line-height-normal);
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ============================================
           رأس الصفحة (Header)
           ============================================ */
        .app-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            z-index: var(--z-sticky);
            display: flex;
            align-items: center;
            padding: 0 var(--spacing-lg);
            gap: var(--spacing-md);
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .header-logo svg {
            width: 32px;
            height: 32px;
            color: var(--primary-color);
        }

        .header-logo span {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
        }

        .header-order-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-right: auto;
        }

        .order-id {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            background: var(--bg-secondary);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            font-weight: var(--font-weight-medium);
        }

        .order-status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            transition: all var(--transition-normal);
        }

        .order-status-badge.status-confirmed {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        .order-status-badge.status-preparing {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-color);
        }

        .order-status-badge.status-on-the-way {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary-color);
        }

        .order-status-badge.status-delivered {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.5);
            }
        }

        .mode-toggle {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: var(--bg-secondary);
            padding: var(--spacing-xs);
            border-radius: var(--border-radius-full);
            border: 1px solid var(--border-color);
        }

        .mode-toggle-btn {
            padding: var(--spacing-xs) var(--spacing-md);
            border: none;
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
            background: transparent;
            color: var(--text-secondary);
            font-family: var(--font-family);
        }

        .mode-toggle-btn.active {
            background: var(--primary-color);
            color: var(--text-white);
            box-shadow: var(--shadow-sm);
        }

        .mode-toggle-btn:hover:not(.active) {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* ============================================
           المحتوى الرئيسي
           ============================================ */
        .app-main {
            margin-top: var(--header-height);
            display: grid;
            grid-template-columns: 1fr var(--sidebar-width);
            min-height: calc(100vh - var(--header-height));
        }

        /* ============================================
           الخريطة
           ============================================ */
        .map-container {
            position: relative;
            min-height: var(--map-min-height);
            height: calc(100vh - var(--header-height));
        }

        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .map-overlay-info {
            position: absolute;
            bottom: var(--spacing-lg);
            left: var(--spacing-lg);
            right: var(--spacing-lg);
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-lg);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-md);
        }

        .map-info-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .map-info-item .icon {
            width: 36px;
            height: 36px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-lg);
        }

        .map-info-item .icon.distance {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
        }

        .map-info-item .icon.time {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary-color);
        }

        .map-info-item .icon.speed {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-color);
        }

        .map-info-label {
            font-size: var(--font-size-xs);
            color: var(--text-light);
        }

        .map-info-value {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
        }

        /* ============================================
           لوحة المعلومات الجانبية
           ============================================ */
        .info-panel {
            background: var(--bg-primary);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            height: calc(100vh - var(--header-height));
            padding: var(--spacing-lg);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        /* شريط التقدم */
        .progress-timeline {
            background: var(--bg-secondary);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
        }

        .progress-timeline h3 {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--text-secondary);
            margin-bottom: var(--spacing-md);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .timeline-steps {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-md);
            position: relative;
            padding-bottom: var(--spacing-lg);
        }

        .timeline-step:last-child {
            padding-bottom: 0;
        }

        .timeline-step::before {
            content: '';
            position: absolute;
            top: 24px;
            right: 11px;
            width: 2px;
            height: calc(100% - 24px);
            background: var(--border-color);
        }

        .timeline-step:last-child::before {
            display: none;
        }

        .timeline-step.completed::before {
            background: var(--secondary-color);
        }

        .timeline-step.active::before {
            background: linear-gradient(to bottom, var(--primary-color), var(--border-color));
        }

        .step-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-bold);
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            transition: all var(--transition-normal);
        }

        .timeline-step.completed .step-indicator {
            background: var(--secondary-color);
            color: var(--text-white);
        }

        .timeline-step.active .step-indicator {
            background: var(--primary-color);
            color: var(--text-white);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            animation: pulse-step 2s infinite;
        }

        .timeline-step.pending .step-indicator {
            background: var(--bg-tertiary);
            color: var(--text-light);
            border: 2px solid var(--border-color);
        }

        @keyframes pulse-step {

            0%,
            100% {
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.1);
            }
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }

        .timeline-step.pending .step-title {
            color: var(--text-light);
        }

        .step-time {
            font-size: var(--font-size-xs);
            color: var(--text-light);
        }

        /* بطاقة المندوب */
        .driver-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            transition: all var(--transition-normal);
        }

        .driver-card:hover {
            box-shadow: var(--shadow-md);
        }

        .driver-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .driver-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-light);
            flex-shrink: 0;
        }

        .driver-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .driver-info {
            flex: 1;
        }

        .driver-name {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
        }

        .driver-rating {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            margin-top: var(--spacing-xs);
        }

        .stars {
            color: var(--accent-color);
            font-size: var(--font-size-sm);
        }

        .rating-value {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            font-weight: var(--font-weight-medium);
        }

        .driver-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .driver-detail-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }

        .driver-detail-item .detail-icon {
            font-size: var(--font-size-base);
        }

        .driver-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--border-radius);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-family: var(--font-family);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--text-white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
        }

        .btn-success {
            background: var(--secondary-color);
            color: var(--text-white);
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--text-white);
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-full {
            width: 100%;
        }

        .btn-call {
            flex: 1;
            background: var(--secondary-color);
            color: var(--text-white);
        }

        .btn-call:hover {
            background: #059669;
        }

        /* بطاقة الطلب */
        .order-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
        }

        .order-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }

        .order-card-title {
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
        }

        .order-card-badge {
            font-size: var(--font-size-xs);
            background: var(--bg-secondary);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            color: var(--text-secondary);
        }

        .order-details-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .order-detail-row {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
        }

        .order-detail-row .label {
            color: var(--text-light);
            min-width: 80px;
            flex-shrink: 0;
        }

        .order-detail-row .value {
            color: var(--text-primary);
            font-weight: var(--font-weight-medium);
        }

        .order-items {
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-light);
        }

        .order-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-xs) 0;
            font-size: var(--font-size-sm);
        }

        .order-item-name {
            color: var(--text-primary);
        }

        .order-item-qty {
            color: var(--text-secondary);
            font-size: var(--font-size-xs);
        }

        /* ============================================
           Toast Notifications
           ============================================ */
        .toast-container {
            position: fixed;
            top: calc(var(--header-height) + var(--spacing-md));
            left: var(--spacing-md);
            z-index: var(--z-toast);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .toast {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            min-width: 300px;
            max-width: 400px;
            animation: slideInToast 0.3s ease forwards;
            border-right: 4px solid var(--primary-color);
        }

        .toast.toast-success {
            border-right-color: var(--secondary-color);
        }

        .toast.toast-error {
            border-right-color: var(--danger-color);
        }

        .toast.toast-warning {
            border-right-color: var(--accent-color);
        }

        .toast-icon {
            font-size: var(--font-size-xl);
            flex-shrink: 0;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--text-primary);
        }

        .toast-message {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: var(--font-size-lg);
            padding: var(--spacing-xs);
            border-radius: var(--border-radius-sm);
            transition: all var(--transition-fast);
        }

        .toast-close:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        @keyframes slideInToast {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ============================================
           Skeleton Loading
           ============================================ */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-secondary) 25%, var(--bg-tertiary) 50%, var(--bg-secondary) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: var(--border-radius-sm);
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-text {
            height: 14px;
            margin-bottom: var(--spacing-sm);
        }

        .skeleton-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
        }

        /* ============================================
           اتصال منقطع
           ============================================ */
        .connection-banner {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            background: var(--danger-color);
            color: var(--text-white);
            padding: var(--spacing-sm) var(--spacing-lg);
            text-align: center;
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            z-index: var(--z-sticky);
            display: none;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
        }

        .connection-banner.visible {
            display: flex;
        }

        .connection-banner .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ============================================
           Marker مخصص
           ============================================ */
        .custom-marker {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .marker-restaurant {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            border: 3px solid white;
        }

        .marker-restaurant span {
            transform: rotate(45deg);
            font-size: 18px;
        }

        .marker-driver {
            width: 44px;
            height: 44px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            border: 3px solid white;
            animation: marker-pulse 2s infinite;
            transition: transform 0.3s ease;
        }

        .marker-driver span {
            font-size: 20px;
        }

        @keyframes marker-pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4), var(--shadow-lg);
            }

            50% {
                box-shadow: 0 0 0 12px rgba(59, 130, 246, 0), var(--shadow-lg);
            }
        }

        .marker-customer {
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            border: 3px solid white;
        }

        .marker-customer span {
            transform: rotate(45deg);
            font-size: 18px;
        }

        /* ============================================
           Driver View - أزرار إضافية
           ============================================ */
        .driver-view-only {
            display: none;
        }

        [data-view="driver"] .driver-view-only {
            display: flex;
        }

        .customer-view-only {
            display: none;
        }

        [data-view="customer"] .customer-view-only {
            display: flex;
        }

        /* ============================================
           Responsive Design
           ============================================ */
        @media (max-width: 1024px) {
            .app-main {
                grid-template-columns: 1fr;
                grid-template-rows: 50vh auto;
            }

            .map-container {
                height: 50vh;
            }

            .info-panel {
                height: auto;
                border-right: none;
                border-top: 1px solid var(--border-color);
            }
        }

        @media (max-width: 768px) {
            .app-header {
                padding: 0 var(--spacing-md);
                gap: var(--spacing-sm);
            }

            .header-logo span {
                display: none;
            }

            .header-order-info {
                gap: var(--spacing-sm);
            }

            .order-id {
                font-size: var(--font-size-xs);
                padding: 2px var(--spacing-xs);
            }

            .order-status-badge {
                font-size: var(--font-size-xs);
                padding: 2px var(--spacing-sm);
            }

            .mode-toggle {
                padding: 2px;
            }

            .mode-toggle-btn {
                font-size: var(--font-size-xs);
                padding: 4px var(--spacing-sm);
            }

            .app-main {
                grid-template-columns: 1fr;
                grid-template-rows: 55vh auto;
            }

            .map-container {
                height: 55vh;
            }

            .info-panel {
                padding: var(--spacing-md);
                gap: var(--spacing-md);
            }

            .map-overlay-info {
                bottom: var(--spacing-sm);
                left: var(--spacing-sm);
                right: var(--spacing-sm);
                padding: var(--spacing-sm);
                flex-wrap: wrap;
            }

            .driver-details {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================
           Leaflet Overrides
           ============================================ */
        .leaflet-popup-content-wrapper {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            font-family: var(--font-family);
        }

        .leaflet-popup-content {
            margin: var(--spacing-sm) var(--spacing-md);
            font-size: var(--font-size-sm);
        }

        .driver-popup {
            text-align: center;
        }

        .driver-popup strong {
            display: block;
            margin-bottom: var(--spacing-xs);
            color: var(--text-primary);
        }

        .driver-popup small {
            color: var(--text-light);
            font-size: var(--font-size-xs);
        }

        /* ============================================
           آخر تحديث
           ============================================ */
        .last-update {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: var(--font-size-xs);
            color: var(--text-light);
            padding: var(--spacing-sm) 0;
        }

        .last-update .live-dot {
            width: 6px;
            height: 6px;
            background: var(--secondary-color);
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        /* ============================================
           ETA Display
           ============================================ */
        .eta-display {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            color: var(--text-white);
            text-align: center;
        }

        .eta-label {
            font-size: var(--font-size-sm);
            opacity: 0.9;
            margin-bottom: var(--spacing-xs);
        }

        .eta-time {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
        }

        .eta-unit {
            font-size: var(--font-size-sm);
            opacity: 0.8;
        }
    </style>
</head>

<body data-view="driver">

    <!-- رأس الصفحة -->
    <header class="app-header" role="banner">
        <div class="header-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 2L2 7l10 5 10-5-10-5z" />
                <path d="M2 17l10 5 10-5" />
                <path d="M2 12l10 5 10-5" />
            </svg>
            <span>توصيل سريع</span>
        </div>

        <div class="header-order-info">
            <!-- PHP: رقم الطلب -->
            <span class="order-id" aria-label="رقم الطلب">#{{ $order->tracking_code }}</span>
            <span
                class="order-status-badge {{ $order->status === 'on_way' ? 'status-on-the-way' : ($order->status === 'delivered' ? 'status-delivered' : 'status-preparing') }}"
                id="statusBadge" role="status" aria-live="polite">
                <span class="status-dot"></span>
                <span
                    id="statusText">{{ $order->status === 'on_way' ? 'في الطريق' : ($order->status === 'delivered' ? 'تم التسليم' : 'جاهز للتوصيل') }}</span>
            </span>
        </div>


    </header>

    <!-- بانر حالة الاتصال -->
    <div class="connection-banner" id="connectionBanner" role="alert" aria-live="assertive">
        <div class="spinner"></div>
        <span>جاري إعادة الاتصال...</span>
    </div>

    <!-- المحتوى الرئيسي -->
    <main class="app-main">
        <!-- الخريطة -->
        <section class="map-container" aria-label="خريطة التتبع">
            <div id="map"></div>
            <div class="map-overlay-info">
                <div class="map-info-item">
                    <div class="icon distance">📏</div>
                    <div>
                        <div class="map-info-label">المسافة المتبقية</div>
                        <div class="map-info-value" id="distanceValue">2.4 كم</div>
                    </div>
                </div>
                <div class="map-info-item">
                    <div class="icon time">⏱️</div>
                    <div>
                        <div class="map-info-label">وقت الوصول</div>
                        <div class="map-info-value" id="timeValue">8 دقائق</div>
                    </div>
                </div>
                <div class="map-info-item">
                    <div class="icon speed">🚗</div>
                    <div>
                        <div class="map-info-label">السرعة</div>
                        <div class="map-info-value" id="speedValue">35 كم/س</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- لوحة المعلومات -->
        <aside class="info-panel" aria-label="معلومات الطلب">
            <!-- عرض الوقت المتوقع -->
            <div class="eta-display">
                <div class="eta-label">الوقت المتوقع للوصول</div>
                <div class="eta-time" id="etaTime">--</div>
                <div class="eta-unit">دقائق</div>
            </div>

            <!-- شريط التقدم -->
            <div class="progress-timeline" role="list" aria-label="مراحل الطلب">
                <h3>مراحل الطلب</h3>
                <div class="timeline-steps" id="timelineSteps">
                    <div class="timeline-step {{ in_array($order->status, ['ready', 'on_way', 'delivered'], true) ? 'completed' : 'pending' }}"
                        role="listitem">
                        <div class="step-indicator">✓</div>
                        <div class="step-content">
                            <div class="step-title">تم تأكيد الطلب</div>
                            <div class="step-time" id="step1Time">
                                {{ $order->created_at ? $order->created_at->format('h:i A') : '—' }}</div>
                        </div>
                    </div>
                    <div class="timeline-step {{ in_array($order->status, ['on_way', 'delivered'], true) ? 'completed' : 'pending' }}"
                        role="listitem">
                        <div class="step-indicator">✓</div>
                        <div class="step-content">
                            <div class="step-title">قيد التحضير</div>
                            <div class="step-time" id="step2Time">
                                {{ $order->updated_at && $order->status === 'preparing' ? $order->updated_at->format('h:i A') : '—' }}
                            </div>
                        </div>
                    </div>
                    <div class="timeline-step {{ $order->status === 'on_way' ? 'active' : ($order->status === 'delivered' ? 'completed' : 'pending') }}"
                        role="listitem">
                        <div class="step-indicator">⚡</div>
                        <div class="step-content">
                            <div class="step-title">في الطريق إليك</div>
                            <div class="step-time" id="step3Time">
                                {{ $order->status === 'on_way' ? $order->updated_at->format('h:i A') : '—' }}</div>
                        </div>
                    </div>
                    <div class="timeline-step {{ $order->status === 'delivered' ? 'completed' : 'pending' }}"
                        role="listitem">
                        <div class="step-indicator">4</div>
                        <div class="step-content">
                            <div class="step-title">تم التسليم</div>
                            <div class="step-time">
                                {{ $order->status === 'delivered' ? $order->updated_at->format('h:i A') : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- آخر تحديث -->
            <div class="last-update">
                <span class="live-dot"></span>
                <span>مباشر - آخر تحديث: <span id="lastUpdateTime">--:--:--</span></span>
            </div>

            <!-- بطاقة المندوب -->
            <div class="driver-card" aria-label="معلومات المندوب">
                <div class="driver-header">
                    <div class="driver-avatar">
                        <!-- PHP: صورة المندوب -->
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='35' r='20' fill='%233B82F6'/%3E%3Cellipse cx='50' cy='85' rx='30' ry='25' fill='%233B82F6'/%3E%3C/svg%3E"
                            alt="صورة المندوب" loading="lazy" id="driverAvatar">
                    </div>
                    <div class="driver-info">
                        <!-- PHP: اسم المندوب -->
                        <div class="driver-name" id="driverName">{{ $order->driver?->name ?? 'المندوب' }}</div>
                        <div class="driver-rating">
                            <span class="stars" aria-label="التقييم 4.8 من 5">★★★★★</span>
                            <!-- PHP: التقييم -->
                            <span class="rating-value" id="driverRating">{{ $order->driver?->rating ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="driver-details">
                    <!-- PHP: رقم الهاتف -->
                    <a href="tel:{{ $order->driver?->phone }}" class="driver-detail-item"
                        aria-label="اتصال بالمندوب">
                        <span class="detail-icon">📱</span>
                        <span id="driverPhone">{{ $order->driver?->phone ?? '—' }}</span>
                    </a>
                    <!-- PHP: نوع المركبة -->
                    <div class="driver-detail-item">
                        <span class="detail-icon">🏍️</span>
                        <span id="driverVehicle">{{ $order->driver?->vehicle_type ?? 'غير محدد' }}</span>
                    </div>
                    <!-- PHP: رقم اللوحة -->
                    <div class="driver-detail-item">
                        <span class="detail-icon">🔢</span>
                        <span id="driverPlate">{{ $order->driver?->plate_number ?? 'بدون لوحة' }}</span>
                    </div>
                    <div class="driver-detail-item">
                        <span class="detail-icon">📦</span>
                        <span>طلبات نشطة: 1</span>
                    </div>
                </div>

                <div class="driver-actions">
                    <a href="tel:{{ $order->driver?->phone }}" class="btn btn-call" aria-label="اتصال بالمندوب">
                        📞 اتصال
                    </a>
                    <button class="btn btn-secondary" aria-label="محادثة المندوب"
                        onclick="showToast('info', 'المحادثة', 'سيتم فتح المحادثة قريباً')">
                        💬 محادثة
                    </button>
                </div>

                <!-- أزرار خاصة بوضع المندوب -->
                <div class="driver-view-only"
                    style="margin-top: var(--spacing-md); flex-direction: column; gap: var(--spacing-sm);">
                    <button class="btn btn-success btn-full" id="btnStartDelivery" onclick="handleStartDelivery()"
                        {{ $order->status === 'delivered' ? 'disabled' : '' }}>
                        🚀 بدء التوصيل
                    </button>
                    <button class="btn btn-primary btn-full customer-view-only"
                        onclick="showToast('success', 'تم', 'تم تحديث الموقع')">
                        📍 تحديث موقعي
                    </button>
                </div>
            </div>

            <!-- بطاقة الطلب -->
            <div class="order-card" aria-label="تفاصيل الطلب">
                <div class="order-card-header">
                    <span class="order-card-title">تفاصيل الطلب</span>
                    <span class="order-card-badge">3 أصناف</span>
                </div>

                <div class="order-details-list">
                    <!-- PHP: عنوان التوصيل -->
                    <div class="order-detail-row">
                        <span class="label">العنوان:</span>
                        <span class="value"
                            id="deliveryAddress">{{ $order->delivery_address ?? 'لا يوجد عنوان مسجل' }}</span>
                    </div>
                    <!-- PHP: ملاحظات التوصيل -->
                    <div class="order-detail-row">
                        <span class="label">ملاحظات:</span>
                        <span class="value" id="deliveryNotes">{{ $order->notes ?? 'لا توجد ملاحظات' }}</span>
                    </div>
                    <!-- PHP: طريقة الدفع -->
                    <div class="order-detail-row">
                        <span class="label">الدفع:</span>
                        <span
                            class="value">بطاق{{ $order->payment_method === 'cash' ? 'دفع عند الاستلام' : 'دفع إلكتروني' }}</span>
                    </div>
                </div>

                <div class="order-items">
                    <!-- PHP: عناصر الطلب -->
                    @foreach ($order->items as $item)
                        <div class="order-item">
                            <span class="order-item-name">{{ $item->product?->name ?? 'منتج محذوف' }}</span>
                            <span class="order-item-qty">×{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- أزرار خاصة بوضع المندوب -->
                <div class="driver-view-only"
                    style="margin-top: var(--spacing-md); flex-direction: column; gap: var(--spacing-sm);">
                    <a href="tel:{{ $order->customer_phone }}" class="btn btn-call btn-full">
                        📞 اتصال بالعميل
                    </a>
                    <button class="btn btn-success btn-full" id="btnDelivered" onclick="handleDelivered()"
                        {{ $order->status !== 'on_way' ? 'disabled' : '' }}>
                        ✓ تم التسليم
                    </button>
                </div>
            </div>
        </aside>
    </main>

    <!-- حاوية Toast -->
    <div class="toast-container" id="toastContainer" aria-live="polite"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const trackingConfig = {
            orderId: @json($order->id),
            viewMode: 'driver', //@json($isDriver ? 'driver' : 'customer'),
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            updateInterval: 5000,
            apiEndpoint: @json(route('driver.order.show', [$order->restaurant->id, $order])),
            locationEndpoint: @json(route('driver.location.update', [$order->restaurant->id, $order])),
            statusEndpoint: @json(route('driver.status.update', [$order->restaurant->id, $order])),
            initialData: {
                restaurant: {
                    name: @json($order->restaurant?->name)
                },
                customer: {
                    lat: @json($order->delivery_latitude),
                    lng: @json($order->delivery_longitude),
                    address: @json($order->delivery_address),
                },
                driver: {
                    lat: @json($order->driver_latitude),
                    lng: @json($order->driver_longitude),
                    name: @json($order->driver?->name),
                },
            },
        };

        // ============================================
        // المتغيرات والدوال المساعدة
        // ============================================
        let lastSentCoords = null;
        let lastSentTime = 0;

        // دالة حساب المسافة بالمتر بين نقطتين جغرافيين
        function getDistanceInMeters(lat1, lon1, lat2, lon2) {
            if (typeof L !== 'undefined' && L.latLng) {
                return L.latLng(lat1, lon1).distanceTo(L.latLng(lat2, lon2));
            }
            const R = 6371e3;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
        }

        // ============================================
        // نظام تتبع المواقع
        // ============================================
        class LocationTracker {
            constructor(config) {
                this.config = config;
                this.map = null;
                this.markers = {};
                this.routeLayer = null;
                this.accuracyCircle = null;
                this.lastUpdate = null;
                this.retryCount = 0;
                this.maxRetries = 5;
                this.updateTimer = null;
                this.isUpdating = false;
                this.driverPath = [];
                this.lastRouteKey = null;
                this.locationWatch = null;

                // محاكاة حركة المندوب
                this.simulationAngle = 0;
                this.simulationCenter = {
                    lat: config.initialData.driver.lat,
                    lng: config.initialData.driver.lng
                };
            }

            // تهيئة النظام
            async init() {
                this.initMap();
                this.addInitialMarkers();
                this.startAutoUpdate();
                this.setupEventListeners();
                this.updateLastUpdateTime();

                showToast('success', 'تم الاتصال', 'تم الاتصال بنظام التتبع بنجاح');
            }

            // تهيئة الخريطة
            initMap() {
                const centerLat = this.config.initialData.driver.lat || this.config.initialData.customer.lat || 24.7136;
                const centerLng = this.config.initialData.driver.lng || this.config.initialData.customer.lng || 46.6753;

                this.map = L.map('map', {
                    zoomControl: true,
                    attributionControl: true
                }).setView([centerLat, centerLng], 14);

                // استخدام OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(this.map);

                this.map.attributionControl.addAttribution('Routing: OSRM');
            }

            // إضافة الـ markers الأولية
            addInitialMarkers() {
                const {
                    restaurant,
                    customer,
                    driver
                } = this.config.initialData;
                const customerPoint = customer.lat && customer.lng ? [customer.lat, customer.lng] : null;
                const driverPoint = driver.lat && driver.lng ? [driver.lat, driver.lng] : null;

                // Marker المطعم
                const restaurantIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<div class="marker-restaurant"><span>🍔</span></div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    popupAnchor: [0, -40]
                });

                if (restaurant.lat && restaurant.lng) {
                    this.markers.restaurant = L.marker([restaurant.lat, restaurant.lng], {
                        icon: restaurantIcon
                    }).addTo(this.map).bindPopup(`
                        <div style="text-align:center;">
                            <strong>${restaurant.name}</strong><br>
                            <small>المطعم</small>
                        </div>
                    `);
                }

                // Marker العميل
                const customerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<div class="marker-customer"><span>📍</span></div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    popupAnchor: [0, -40]
                });

                if (customerPoint) {
                    this.markers.customer = L.marker(customerPoint, {
                        icon: customerIcon
                    }).addTo(this.map).bindPopup(`
                        <div style="text-align:center;">
                            <strong>موقع العميل</strong><br>
                            <small>${customer.address || ''}</small>
                        </div>
                    `);
                }

                // Marker المندوب
                const driverIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<div class="marker-driver"><span>🚗</span></div>',
                    iconSize: [44, 44],
                    iconAnchor: [22, 22],
                    popupAnchor: [0, -44]
                });

                if (driverPoint) {
                    this.markers.driver = L.marker(driverPoint, {
                        icon: driverIcon
                    }).addTo(this.map).bindPopup(`
                        <div class="driver-popup">
                            <strong>${driver.name}</strong><br>
                            <small>آخر تحديث: ${new Date().toLocaleTimeString('ar-SA')}</small>
                        </div>
                    `);

                    // دائرة الدقة حول المندوب
                    this.accuracyCircle = L.circle(driverPoint, {
                        radius: 50,
                        color: '#3B82F6',
                        fillColor: '#3B82F6',
                        fillOpacity: 0.1,
                        weight: 1
                    }).addTo(this.map);
                }

                // ضبط حدود الخريطة
                const points = [customerPoint, driverPoint].filter(Boolean);
                if (points.length > 1) this.map.fitBounds(L.latLngBounds(points), {
                    padding: [50, 50]
                });
            }

            // جلب البيانات من السيرفر
            async fetchLocationData() {
                try {
                    const response = await fetch(`${this.config.apiEndpoint}?t=${Date.now()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const payload = await response.json();
                    const driver = payload.driver || {};
                    const customer = payload.customer || {};
                    const distance = driver.latitude && driver.longitude && customer.latitude && customer.longitude ?
                        this.calculateDistance(Number(driver.latitude), Number(driver.longitude), Number(customer
                            .latitude), Number(customer.longitude)) :
                        0;
                    const eta = distance ? Math.max(1, Math.round(distance * 3)) : null;
                    const data = {
                        driver: {
                            lat: Number(driver.latitude),
                            lng: Number(driver.longitude),
                            name: this.config.initialData.driver.name,
                            speed: 0,
                            accuracy: 30,
                            heading: driver.heading
                        },
                        eta,
                        distance,
                        status: payload.status,
                    };
                    this.retryCount = 0;
                    return data;
                } catch (error) {
                    console.error('فشل جلب البيانات:', error);
                    this.handleConnectionError();
                    return null;
                }
            }

            // حساب المسافة (Haversine formula)
            calculateDistance(lat1, lng1, lat2, lng2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLng / 2) * Math.sin(dLng / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            // معالجة أخطاء الاتصال
            handleConnectionError() {
                this.retryCount++;
                if (this.retryCount > this.maxRetries) {
                    this.showConnectionError();
                    this.retryCount = 0;
                    return;
                }

                const backoffTime = Math.min(1000 * Math.pow(2, this.retryCount), 30000);
                const banner = document.getElementById('connectionBanner');
                if (banner) banner.classList.add('visible');

                clearTimeout(this.updateTimer);
                this.updateTimer = setTimeout(() => this.updateLoop(), backoffTime);
            }

            // حلقة التحديث التلقائي
            async updateLoop() {
                if (this.isUpdating) return;
                this.isUpdating = true;

                const data = await this.fetchLocationData();

                if (data) {
                    const banner = document.getElementById('connectionBanner');
                    if (banner) banner.classList.remove('visible');

                    if (Number.isFinite(data.driver.lat) && Number.isFinite(data.driver.lng) && data.driver.lat !== 0 &&
                        data.driver.lng !== 0) {
                        this.updateDriverLocation(data.driver);
                    }
                    if (data.driver.lat && data.driver.lng && this.config.initialData.customer.lat && this.config
                        .initialData.customer.lng) {
                        this.updateRoute(data.driver, this.config.initialData.customer);
                    }
                    if (data.eta !== null) this.updateETA(data.eta);
                    if (data.distance) this.updateDistanceInfo(data.distance);
                    this.updateSpeedInfo(data.driver.speed || 0);
                    this.updateOrderStatus(data.status);
                    if (data.driver.lat && data.driver.lng) this.updateAccuracyCircle(data.driver.lat, data.driver.lng,
                        data.driver.accuracy);
                    this.lastUpdate = new Date();
                    this.updateLastUpdateTime();
                }

                this.isUpdating = false;
                this.updateTimer = setTimeout(() => this.updateLoop(), this.config.updateInterval);
            }

            // تحديث موقع المندوب
            updateDriverLocation(driverData) {
                if (this.markers.driver) {
                    const newLatLng = [driverData.lat, driverData.lng];

                    this.markers.driver.setLatLng(newLatLng);

                    if (driverData.heading !== undefined && driverData.heading !== null) {
                        const iconEl = this.markers.driver.getElement()?.querySelector('.marker-driver');
                        if (iconEl) iconEl.style.transform = `rotate(${driverData.heading}deg)`;
                    }

                    if (this.config.viewMode === 'customer') {
                        this.map.panTo(newLatLng, {
                            animate: true,
                            duration: 1.0
                        });
                    }

                    this.markers.driver.setPopupContent(`
                        <div class="driver-popup">
                            <strong>${driverData.name}</strong><br>
                            <small>آخر تحديث: ${new Date().toLocaleTimeString('ar-SA')}</small>
                        </div>
                    `);

                    this.driverPath.push(newLatLng);
                    if (this.driverPath.length > 100) {
                        this.driverPath.shift();
                    }
                }
            }

            // تحديث المسار
            async updateRoute(driver, customer) {
                const routePoints = [
                    [driver.lat, driver.lng],
                    [customer.lat, customer.lng]
                ];
                const routeKey = routePoints.flat().map((point) => Number(point).toFixed(5)).join(',');

                if (routeKey === this.lastRouteKey) return;
                this.lastRouteKey = routeKey;

                if (this.routeLayer) this.map.removeLayer(this.routeLayer);

                const primaryColor = getComputedStyle(document.documentElement)
                    .getPropertyValue('--primary-color').trim() || '#3B82F6';

                try {
                    const routeUrl =
                        `https://router.project-osrm.org/route/v1/driving/${driver.lng},${driver.lat};${customer.lng},${customer.lat}?overview=full&geometries=geojson`;
                    const response = await fetch(routeUrl, {
                        signal: AbortSignal.timeout(8000)
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();

                    if (!data.routes?.[0]?.geometry) throw new Error('No route geometry');

                    this.routeLayer = L.geoJSON(data.routes[0].geometry, {
                        style: {
                            color: primaryColor,
                            weight: 5,
                            opacity: 0.8,
                            lineCap: 'round',
                            lineJoin: 'round'
                        }
                    }).addTo(this.map);
                } catch (error) {
                    this.routeLayer = L.polyline(routePoints, {
                        color: primaryColor,
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '10, 5'
                    }).addTo(this.map);
                }
            }

            // تحديث وقت الوصول المتوقع
            updateETA(eta) {
                const etaElement = document.getElementById('etaTime');
                if (etaElement) etaElement.textContent = eta;

                const timeValue = document.getElementById('timeValue');
                if (timeValue) timeValue.textContent = `${eta} دقائق`;
            }

            // تحديث معلومات المسافة
            updateDistanceInfo(distance) {
                const distanceEl = document.getElementById('distanceValue');
                if (distanceEl) distanceEl.textContent = `${distance.toFixed(1)} كم`;
            }

            // تحديث معلومات السرعة
            updateSpeedInfo(speed) {
                const speedEl = document.getElementById('speedValue');
                if (speedEl) speedEl.textContent = `${Math.round(speed)} كم/س`;
            }

            // تحديث حالة الطلب
            updateOrderStatus(status) {
                const badge = document.getElementById('statusBadge');
                const statusText = document.getElementById('statusText');
                const steps = document.querySelectorAll('.timeline-step');

                const statusMap = {
                    'confirmed': {
                        class: 'status-confirmed',
                        text: 'تم التأكيد',
                        step: 0
                    },
                    'preparing': {
                        class: 'status-preparing',
                        text: 'قيد التحضير',
                        step: 1
                    },
                    'ready': {
                        class: 'status-preparing',
                        text: 'جاهز للتوصيل',
                        step: 1
                    },
                    'on_way': {
                        class: 'status-on-the-way',
                        text: 'في الطريق',
                        step: 2
                    },
                    'delivered': {
                        class: 'status-delivered',
                        text: 'تم التسليم',
                        step: 3
                    }
                };

                const info = statusMap[status];
                if (info && badge && statusText) {
                    badge.className = `order-status-badge ${info.class}`;
                    statusText.textContent = info.text;

                    const startButton = document.getElementById('btnStartDelivery');
                    const deliveredButton = document.getElementById('btnDelivered');
                    if (startButton && status === 'delivered') startButton.disabled = true;
                    if (deliveredButton && status === 'delivered') deliveredButton.disabled = true;

                    steps.forEach((step, index) => {
                        step.classList.remove('completed', 'active', 'pending');
                        if (index < info.step) {
                            step.classList.add('completed');
                        } else if (index === info.step) {
                            step.classList.add('active');
                        } else {
                            step.classList.add('pending');
                        }
                    });
                }
            }

            // تحديث دائرة الدقة
            updateAccuracyCircle(lat, lng, accuracy) {
                if (this.accuracyCircle) {
                    this.accuracyCircle.setLatLng([lat, lng]);
                    this.accuracyCircle.setRadius(accuracy);
                }
            }

            // تحديث وقت آخر تحديث
            updateLastUpdateTime() {
                const el = document.getElementById('lastUpdateTime');
                if (el) el.textContent = new Date().toLocaleTimeString('ar-SA');
            }

            // عرض خطأ الاتصال
            showConnectionError() {
                showToast('error', 'خطأ في الاتصال', 'تعذر الاتصال بالخادم. يرجى التحقق من اتصال الإنترنت.');
                const banner = document.getElementById('connectionBanner');
                if (banner) {
                    banner.innerHTML = `
                        <span>⚠️</span>
                        <span>انقطع الاتصال - <button onclick="tracker.retryConnection()" style="background:white;color:var(--danger-color);border:none;padding:2px 8px;border-radius:4px;cursor:pointer;font-weight:bold;">إعادة المحاولة</button></span>
                    `;
                    banner.classList.add('visible');
                }
            }

            // إعادة المحاولة
            retryConnection() {
                this.retryCount = 0;
                const banner = document.getElementById('connectionBanner');
                if (banner) {
                    banner.classList.remove('visible');
                    banner.innerHTML = `
                        <div class="spinner"></div>
                        <span>جاري إعادة الاتصال...</span>
                    `;
                }
                this.updateLoop();
            }

            // بدء التحديث التلقائي
            startAutoUpdate() {
                this.updateTimer = setTimeout(() => this.updateLoop(), this.config.updateInterval);
            }

            // إيقاف التحديث
            stopAutoUpdate() {
                if (this.updateTimer) {
                    clearTimeout(this.updateTimer);
                    this.updateTimer = null;
                }
            }

            // إعداد مستمعات الأحداث
            setupEventListeners() {
                document.querySelectorAll('.mode-toggle-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const mode = btn.dataset.mode;
                        this.switchViewMode(mode);
                    });
                });

                window.addEventListener('beforeunload', () => {
                    this.stopAutoUpdate();
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.stopAutoUpdate();
                    } else {
                        this.startAutoUpdate();
                    }
                });
            }

            // تبديل وضع العرض
            switchViewMode(mode) {
                document.body.setAttribute('data-view', mode);
                this.config.viewMode = mode;

                document.querySelectorAll('.mode-toggle-btn').forEach(btn => {
                    const isActive = btn.dataset.mode === mode;
                    btn.classList.toggle('active', isActive);
                    btn.setAttribute('aria-selected', isActive);
                });

                if (mode === 'driver') {
                    const customer = this.config.initialData.customer;
                    if (customer.lat && customer.lng) this.map.panTo([customer.lat, customer.lng]);
                    showToast('info', 'وضع المندوب', 'تم التبديل إلى وضع المندوب');
                } else {
                    const driver = this.markers.driver?.getLatLng();
                    if (driver) this.map.panTo([driver.lat, driver.lng]);
                    showToast('info', 'وضع العميل', 'تم التبديل إلى وضع العميل');
                }
            }
        }

        // ============================================
        // نظام الإشعارات (Toast)
        // ============================================
        function showToast(type, title, message, duration = 5000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="إغلاق" onclick="this.parentElement.remove()">×</button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.animation = 'slideInToast 0.3s ease reverse forwards';
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }

        // ============================================
        // دوال الأزرار والشبكة
        // ============================================
        async function postTracking(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': trackingConfig.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        }

        function startLocationSharing() {
            if (!navigator.geolocation) {
                showToast('error', 'تحديد الموقع غير متاح', 'المتصفح لا يدعم تحديد الموقع.');
                return;
            }

            const button = document.getElementById('btnStartDelivery');
            if (button) {
                button.disabled = true;
                button.textContent = 'جاري تحديد موقعك...';
            }

            tracker.locationWatch = navigator.geolocation.watchPosition(
                async (position) => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const heading = position.coords.heading;
                        const now = Date.now();

                        // فلترة بالوقت: منع الإرسال إن لم تمضِ 5 ثوانٍ
                        if (now - lastSentTime < 5000) {
                            return;
                        }

                        // فلترة بالمسافة: منع الإرسال إن كانت الحركة أقل من 10 أمتار ولم تمضِ 30 ثانية
                        if (lastSentCoords) {
                            const distance = getDistanceInMeters(
                                lastSentCoords.latitude,
                                lastSentCoords.longitude,
                                latitude,
                                longitude
                            );
                            if (distance < 10 && (now - lastSentTime < 30000)) {
                                return;
                            }
                        }

                        try {
                            await postTracking(trackingConfig.locationEndpoint, {
                                latitude: latitude,
                                longitude: longitude,
                                heading: heading
                            });

                            lastSentCoords = {
                                latitude,
                                longitude
                            };
                            lastSentTime = now;

                            if (button) {
                                button.disabled = false;
                                button.textContent = 'إيقاف مشاركة الموقع';
                            }
                            const btnDelivered = document.getElementById('btnDelivered');
                            if (btnDelivered) btnDelivered.disabled = false;

                            tracker.updateOrderStatus('on_way');
                            showToast('success', 'تم تحديث الموقع', 'تم حفظ موقعك الحالي للعميل.');
                        } catch (error) {
                            if (button) button.disabled = false;
                            showToast('error', 'تعذر تحديث الموقع', 'تحقق من اتصال الإنترنت ثم أعد المحاولة.');
                        }
                    },
                    (error) => {
                        if (button) button.disabled = false;
                        if (error.code === error.PERMISSION_DENIED) {
                            alert('يرجى تفعيل خدمة تحديد الموقع (GPS) في المتصفح لمشاركة مسار الطلب.');
                        } else {
                            if (button) button.textContent = 'إعادة المحاولة';
                            showToast('warning', 'السماح بالموقع مطلوب', 'اسمح للمتصفح بالوصول إلى موقعك.');
                        }
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 15000
                    }
            );
        }

        async function handleStartDelivery() {
            const button = document.getElementById('btnStartDelivery');
            if (tracker.locationWatch) {
                navigator.geolocation.clearWatch(tracker.locationWatch);
                tracker.locationWatch = null;
                if (button) button.textContent = 'استئناف مشاركة الموقع';
                showToast('info', 'تم إيقاف المشاركة', 'يمكنك استئناف مشاركة موقعك في أي وقت.');
                return;
            }

            try {
                startLocationSharing();
            } catch (error) {
                showToast('error', 'تعذر بدء التوصيل', 'لا يمكن تغيير حالة الطلب حاليًا.');
            }
        }

        async function handleDelivered() {
            if (!confirm('هل تريد تأكيد تسليم الطلب؟')) return;

            try {
                await postTracking(trackingConfig.statusEndpoint, {
                    status: 'delivered'
                });
                if (tracker.locationWatch) navigator.geolocation.clearWatch(tracker.locationWatch);

                const btnDelivered = document.getElementById('btnDelivered');
                if (btnDelivered) {
                    btnDelivered.disabled = true;
                    btnDelivered.textContent = '✓ تم التسليم';
                }

                tracker.stopAutoUpdate();
                tracker.updateOrderStatus('delivered');
                showToast('success', 'تم التسليم', 'تم حفظ تسليم الطلب بنجاح.');
            } catch (error) {
                showToast('error', 'تعذر تأكيد التسليم', 'لا يمكن تسليم الطلب من حالته الحالية.');
            }
        }

        // ============================================
        // بدء التطبيق
        // ============================================
        let tracker;

        document.addEventListener('DOMContentLoaded', () => {
            tracker = new LocationTracker(trackingConfig);
            tracker.init();
        });
    </script>
</body>

</html>
