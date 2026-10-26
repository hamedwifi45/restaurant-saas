<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>شاشة عرض المطبخ | KDS - نظام إدارة الطلبات</title>
    <meta name="description" content="نظام عرض المطبخ - Kitchen Display System لإدارة وتتبع الطلبات">

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @vite(['resources/js/app.js'])
    <style>
    :root {
        /* ===== الألوان الأساسية ===== */
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

        /* ===== ألوان الخلفيات ===== */
        --bg-primary: #FFFFFF;
        --bg-secondary: #F3F4F6;
        --bg-tertiary: #E5E7EB;
        --bg-dark: #1F2937;
        --bg-darker: #111827;
        --bg-overlay: rgba(0, 0, 0, 0.5);
        --bg-card: #FFFFFF;
        --bg-card-hover: #F9FAFB;

        /* ===== النصوص ===== */
        --text-primary: #111827;
        --text-secondary: #6B7280;
        --text-light: #9CA3AF;
        --text-white: #FFFFFF;
        --text-link: #3B82F6;

        /* ===== الحدود والظلال ===== */
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

        /* ===== المسافات ===== */
        --spacing-xs: 0.25rem;
        --spacing-sm: 0.5rem;
        --spacing-md: 1rem;
        --spacing-lg: 1.5rem;
        --spacing-xl: 2rem;
        --spacing-2xl: 3rem;

        /* ===== الخطوط ===== */
        --font-family: 'Inter', system-ui, -apple-system, sans-serif;
        --font-size-xs: 0.75rem;
        --font-size-sm: 0.875rem;
        --font-size-base: 1rem;
        --font-size-lg: 1.125rem;
        --font-size-xl: 1.25rem;
        --font-size-2xl: 1.5rem;
        --font-size-3xl: 1.875rem;
        --font-size-4xl: 2.25rem;
        --font-weight-normal: 400;
        --font-weight-medium: 500;
        --font-weight-semibold: 600;
        --font-weight-bold: 700;
        --font-weight-extrabold: 800;
        --line-height-tight: 1.25;
        --line-height-normal: 1.5;

        /* ===== الانتقالات ===== */
        --transition-fast: 150ms ease;
        --transition-normal: 300ms ease;
        --transition-slow: 500ms ease;

        /* ===== أبعاد خاصة بـ KDS ===== */
        --header-height: 4.5rem;
        --kds-touch-target: 48px;
    }

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
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
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

    button {
        font-family: inherit;
        cursor: pointer;
        border: none;
        background: none;
        color: inherit;
    }

    /* رأس الصفحة (Header) */
    .kds-header {
        position: sticky;
        top: 0;
        height: var(--header-height);
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        z-index: 100;
        display: flex;
        align-items: center;
        padding: 0 var(--spacing-lg);
        gap: var(--spacing-lg);
    }

    .order-availability-control {
        max-width: 1100px;
        margin: var(--spacing-md) auto;
        padding: var(--spacing-md) var(--spacing-lg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-md);
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
    }

    .order-availability-title {
        font-weight: var(--font-weight-bold);
    }

    .order-availability-status {
        display: block;
        margin-top: var(--spacing-xs);
        color: var(--text-secondary);
        font-size: var(--font-size-sm);
    }

    .order-availability-button {
        min-height: var(--kds-touch-target);
        padding: 0 var(--spacing-lg);
        border-radius: var(--border-radius);
        color: var(--text-white);
        font-weight: var(--font-weight-bold);
    }

    .order-availability-button.paused {
        background: var(--success-color);
    }

    .order-availability-button.accepting {
        background: var(--danger-color);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        flex-shrink: 0;
    }

    .brand-logo {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        border-radius: var(--border-radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-white);
        font-size: var(--font-size-xl);
        box-shadow: var(--shadow-md);
    }

    .brand-info {
        display: flex;
        flex-direction: column;
        line-height: var(--line-height-tight);
    }

    .brand-name {
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        color: var(--text-primary);
    }

    .brand-subtitle {
        font-size: var(--font-size-xs);
        color: var(--text-light);
        font-weight: var(--font-weight-medium);
    }

    .header-stats {
        display: flex;
        gap: var(--spacing-sm);
        margin-right: auto;
    }

    .stat-chip {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-lg);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
    }

    .stat-chip .stat-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-base);
    }

    .stat-chip.active .stat-icon {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-color);
    }

    .stat-chip.avg-time .stat-icon {
        background: rgba(245, 158, 11, 0.1);
        color: var(--accent-color);
    }

    .stat-chip.urgent .stat-icon {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-color);
    }

    .stat-value {
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        color: var(--text-primary);
        font-variant-numeric: tabular-nums;
    }

    .stat-label {
        font-size: var(--font-size-xs);
        color: var(--text-secondary);
        font-weight: var(--font-weight-normal);
    }

    .header-clock {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: var(--spacing-sm) var(--spacing-md);
        background: var(--bg-dark);
        border-radius: var(--border-radius-lg);
        color: var(--text-white);
        min-width: 110px;
    }

    .clock-time {
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-bold);
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.5px;
    }

    .clock-date {
        font-size: var(--font-size-xs);
        color: var(--text-light);
    }

    .header-actions {
        display: flex;
        gap: var(--spacing-sm);
    }

    .icon-btn {
        width: var(--kds-touch-target);
        height: var(--kds-touch-target);
        border-radius: var(--border-radius-lg);
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-lg);
        color: var(--text-secondary);
        transition: all var(--transition-fast);
    }

    .icon-btn:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border-color: var(--primary-light);
    }

    .icon-btn.active {
        background: var(--primary-color);
        color: var(--text-white);
        border-color: var(--primary-color);
    }

    /* لوحة العرض والأعمدة */
    .kds-board {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--spacing-md);
        padding: var(--spacing-md);
        min-height: calc(100vh - var(--header-height));
    }

    .kds-column {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-xl);
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
        transition: all var(--transition-normal);
    }

    .kds-column-header {
        padding: var(--spacing-md) var(--spacing-lg);
        background: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .column-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        color: var(--text-primary);
    }

    .column-status-dot {
        width: 12px;
        height: 12px;
        border-radius: var(--border-radius-full);
        position: relative;
    }

    .column-status-dot::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: var(--border-radius-full);
        background: currentColor;
        opacity: 0.25;
    }

    .kds-column.new .column-status-dot {
        background: var(--primary-color);
        color: var(--primary-color);
        animation: pulse-dot 2s infinite;
    }

    .kds-column.preparing .column-status-dot {
        background: var(--accent-color);
        color: var(--accent-color);
    }

    .kds-column.ready .column-status-dot {
        background: var(--secondary-color);
        color: var(--secondary-color);
    }

    @keyframes pulse-dot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.7; }
    }

    .column-count {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--border-radius-full);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        min-width: 36px;
        text-align: center;
        border: 1px solid var(--border-color);
    }

    .kds-column.new .column-count {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-color);
        border-color: var(--primary-light);
    }

    .kds-column.preparing .column-count {
        background: rgba(245, 158, 11, 0.1);
        color: var(--accent-color);
        border-color: var(--accent-light);
    }

    .kds-column.ready .column-count {
        background: rgba(16, 185, 129, 0.1);
        color: var(--secondary-color);
        border-color: var(--secondary-light);
    }

    .kds-column-body {
        flex: 1;
        overflow-y: auto;
        padding: var(--spacing-md);
        display: flex;
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .kds-column-body::-webkit-scrollbar {
        width: 6px;
    }

    .kds-column-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .kds-column-body::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: var(--border-radius-full);
    }

    .kds-column-body::-webkit-scrollbar-thumb:hover {
        background: var(--text-light);
    }

    /* حالة فارغة */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: var(--spacing-2xl) var(--spacing-md);
        text-align: center;
        color: var(--text-light);
        flex: 1;
        border: 2px dashed var(--border-color);
        border-radius: var(--border-radius-lg);
        margin: var(--spacing-sm);
    }

    .empty-state-icon {
        font-size: var(--font-size-3xl);
        margin-bottom: var(--spacing-sm);
        opacity: 0.4;
    }

    .empty-state-text {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
    }

    /* بطاقة الطلب */
    .order-card {
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius-xl);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: all var(--transition-normal);
        animation: slideInCard 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        word-break: break-word; /* منع فيضان النصوص الطويلة */
    }

    @keyframes slideInCard {
        from {
            opacity: 0;
            transform: translateY(-15px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .order-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .order-card.type-delivery {
        border-right: 5px solid var(--primary-color);
    }

    .order-card.type-dine-in {
        border-right: 5px solid var(--secondary-color);
    }

    .order-card.type-takeaway {
        border-right: 5px solid var(--accent-color);
    }

    .order-card.status-new {
        animation: slideInCard 0.4s cubic-bezier(0.4, 0, 0.2, 1), newOrderPulse 2s ease-in-out 3;
    }

    @keyframes newOrderPulse {
        0%, 100% { box-shadow: var(--shadow-md); }
        50% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2), var(--shadow-lg); }
    }

    .order-card.urgent {
        border-color: var(--danger-color) !important;
        background: linear-gradient(135deg, var(--bg-card) 0%, rgba(239, 68, 68, 0.05) 100%);
        animation: slideInCard 0.4s cubic-bezier(0.4, 0, 0.2, 1), urgentPulse 1.5s ease-in-out infinite;
    }

    @keyframes urgentPulse {
        0%, 100% { box-shadow: var(--shadow-md); }
        50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25), var(--shadow-lg); }
    }

    .card-header {
        padding: var(--spacing-md);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-sm);
        border-bottom: 1px solid var(--border-light);
        background: var(--bg-card-hover);
    }

    .order-type-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--border-radius-full);
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
        border: 1px solid;
    }

    .order-type-badge.delivery {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-color);
        border-color: var(--primary-light);
    }

    .order-type-badge.dine-in {
        background: rgba(16, 185, 129, 0.1);
        color: var(--secondary-color);
        border-color: var(--secondary-light);
    }

    .order-type-badge.takeaway {
        background: rgba(245, 158, 11, 0.1);
        color: var(--accent-color);
        border-color: var(--accent-light);
    }

    .order-type-badge .type-icon {
        font-size: var(--font-size-base);
    }

    .order-timer {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) var(--spacing-sm);
        background: var(--bg-secondary);
        border-radius: var(--border-radius-full);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        color: var(--text-secondary);
        font-variant-numeric: tabular-nums;
    }

    .order-timer.warning {
        background: rgba(245, 158, 11, 0.15);
        color: var(--accent-color);
    }

    .order-timer.urgent {
        background: var(--danger-color);
        color: var(--text-white);
        animation: timerPulse 1s infinite;
    }

    @keyframes timerPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    /* معلومات الطلب */
    .order-info {
        padding: var(--spacing-md);
    }

    .order-id-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--spacing-xs);
    }

    .order-id {
        font-size: var(--font-size-2xl);
        font-weight: var(--font-weight-extrabold);
        color: var(--text-primary);
        letter-spacing: -0.5px;
        font-variant-numeric: tabular-nums;
    }

    .table-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) var(--spacing-sm);
        background: rgba(16, 185, 129, 0.1);
        color: var(--secondary-color);
        border-radius: var(--border-radius);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        border: 1px solid var(--secondary-light);
    }

    .customer-name {
        font-size: var(--font-size-sm);
        color: var(--text-secondary);
        margin-top: var(--spacing-xs);
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .driver-info {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        margin-top: var(--spacing-sm);
        padding: var(--spacing-xs) var(--spacing-sm);
        background: rgba(59, 130, 246, 0.08);
        border-radius: var(--border-radius);
        font-size: var(--font-size-sm);
        color: var(--primary-color);
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    /* قائمة الأصناف */
    .items-list {
        list-style: none;
        padding: 0 var(--spacing-md) var(--spacing-md);
    }

    .item-row {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) 0;
        border-bottom: 1px dashed var(--border-light);
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-qty {
        flex-shrink: 0;
        min-width: 36px;
        height: 28px;
        background: var(--primary-color);
        color: var(--text-white);
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
    }

    .order-card.type-dine-in .item-qty {
        background: var(--secondary-color);
    }

    .order-card.type-takeaway .item-qty {
        background: var(--accent-color);
    }

    .item-details {
        flex: 1;
        min-width: 0;
    }

    .item-name {
        font-size: var(--font-size-base);
        font-weight: var(--font-weight-semibold);
        color: var(--text-primary);
        line-height: var(--line-height-tight);
    }

    .item-price {
        margin-top: var(--spacing-xs);
        color: var(--text-secondary);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
    }

    .item-note {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        margin-top: var(--spacing-xs);
        padding: 2px var(--spacing-sm);
        background: rgba(239, 68, 68, 0.08);
        color: var(--danger-color);
        border-radius: var(--border-radius-sm);
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-semibold);
        font-style: italic;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .item-note i {
        font-size: 0.625rem;
    }

    /* أزرار البطاقات */
    .card-actions {
        padding: var(--spacing-sm);
        display: flex;
        gap: var(--spacing-sm);
        background: var(--bg-card-hover);
        border-top: 1px solid var(--border-light);
    }

    .action-btn {
        flex: 1;
        min-height: var(--kds-touch-target);
        padding: 0 var(--spacing-md);
        border-radius: var(--border-radius-lg);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-xs);
        transition: all var(--transition-fast);
        border: 2px solid transparent;
    }

    .action-btn:active {
        transform: scale(0.96);
    }

    .action-btn.primary {
        background: var(--primary-color);
        color: var(--text-white);
    }

    .action-btn.primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
    }

    .action-btn.success {
        background: var(--secondary-color);
        color: var(--text-white);
    }

    .action-btn.success:hover {
        background: #059669;
        box-shadow: var(--shadow-md);
    }

    .action-btn.warning {
        background: var(--accent-color);
        color: var(--text-white);
    }

    .action-btn.warning:hover {
        background: #D97706;
        box-shadow: var(--shadow-md);
    }

    .action-btn.danger {
        background: var(--danger-color);
        color: var(--text-white);
    }

    .action-btn.ghost {
        background: var(--bg-secondary);
        color: var(--text-secondary);
        border-color: var(--border-color);
    }

    .action-btn.ghost:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .delivery-distance,
    .delivery-location {
        display: inline-flex;
        margin-top: var(--spacing-sm);
        margin-left: var(--spacing-sm);
        padding: 2px var(--spacing-sm);
        border-radius: var(--border-radius-full);
        background: #EFF6FF;
        color: #1D4ED8;
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
    }

    /* Modal */
    .reject-modal {
        position: fixed;
        inset: 0;
        z-index: 1100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--spacing-md);
        background: var(--bg-overlay);
    }

    .reject-modal[hidden] {
        display: none;
    }

    .reject-modal-card {
        display: grid;
        width: min(100%, 28rem);
        gap: var(--spacing-md);
        padding: var(--spacing-lg);
        border-radius: var(--border-radius-xl);
        background: var(--bg-primary);
        box-shadow: var(--shadow-xl);
    }

    .reject-modal-card h2 {
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-bold);
    }

    .reject-modal-card select {
        width: 100%;
        min-height: var(--kds-touch-target);
        padding: 0 var(--spacing-sm);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background: var(--bg-primary);
    }

    .reject-modal-actions {
        display: flex;
        gap: var(--spacing-sm);
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: calc(var(--header-height) + var(--spacing-md));
        left: var(--spacing-md);
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
        max-width: 380px;
    }

    .toast {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-right: 4px solid var(--primary-color);
        border-radius: var(--border-radius-lg);
        padding: var(--spacing-md);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        box-shadow: var(--shadow-xl);
        animation: slideInToast 0.3s ease-out;
    }

    .toast.success { border-right-color: var(--secondary-color); }
    .toast.warning { border-right-color: var(--accent-color); }
    .toast.error { border-right-color: var(--danger-color); }

    @keyframes slideInToast {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .toast-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-lg);
        flex-shrink: 0;
    }

    .toast.success .toast-icon {
        background: rgba(16, 185, 129, 0.1);
        color: var(--secondary-color);
    }

    .toast.warning .toast-icon {
        background: rgba(245, 158, 11, 0.1);
        color: var(--accent-color);
    }

    .toast.error .toast-icon {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-color);
    }

    .toast.info .toast-icon {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-color);
    }

    .toast-content {
        flex: 1;
        min-width: 0;
    }

    .toast-title {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        color: var(--text-primary);
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: var(--font-size-xs);
        color: var(--text-secondary);
    }

    /* ============================================
       التجاوب الشامل (Responsive Breakpoints)
       ============================================ */
    @media (max-width: 1280px) {
        .header-stats {
            display: none;
        }
    }

    @media (max-width: 1024px) {
        .kds-board {
            grid-template-columns: repeat(1, 1fr) !important;
            height: auto;
        }

        .kds-column {
            min-height: auto;
        }

        .order-availability-control {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            gap: var(--spacing-xs);
            margin: var(--spacing-sm);
        }

        .order-availability-button {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .kds-header {
            padding: 0.5rem 0.75rem;
            gap: 0.75rem;
        }

        .header-actions {
            gap: 0.375rem;
        }

        .column-title {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 640px) {
        .kds-header {
            padding: var(--spacing-xs) var(--spacing-sm);
            gap: var(--spacing-sm);
            height: auto;
            min-height: 3.5rem;
        }

        .header-clock,
        .brand-info {
            display: none !important;
        }

        .kds-board {
            padding: var(--spacing-sm);
            gap: var(--spacing-sm);
        }

        .kds-column-body {
            padding: var(--spacing-sm);
        }

        .order-id {
            font-size: var(--font-size-xl);
        }
    }

    @media (max-width: 430px) {
        .kds-column-header {
            padding: 0.75rem 0.75rem;
        }

        .column-title {
            font-size: 0.875rem;
            gap: 0.375rem;
        }

        .order-card {
            border-radius: 0.75rem;
        }

        .card-header,
        .order-info,
        .items-list,
        .card-actions {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .action-btn {
            min-height: 2.4rem;
            font-size: 0.7rem;
            padding: 0 0.5rem;
        }

        .item-note {
            max-width: 100%;
            white-space: normal;
        }
    }

    @media (max-width: 375px) {
        .toast-container {
            left: 0.5rem;
            right: 0.5rem;
            max-width: none;
        }

        .order-availability-control {
            padding: 0.75rem;
        }

        .kds-header {
            gap: 0.5rem;
        }
    }

    /* التأثيرات الإضافية */
    .card-exit {
        animation: cardExit 0.3s ease-in forwards;
    }

    @keyframes cardExit {
        to {
            opacity: 0;
            transform: scale(0.9) translateY(-10px);
        }
    }/* شارة "جديد" */
        .new-badge {
            position: absolute;
            top: var(--spacing-sm);
            left: var(--spacing-sm);
            background: var(--primary-color);
            color: var(--text-white);
            padding: 2px var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-bold);
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 2;
            animation: pulse-dot 1.5s infinite;
        }

        .new-badge i {
            font-size: 0.5rem;
        }

        /* اتصال منقطع */
        .connection-bar {
            position: fixed;
            bottom: var(--spacing-md);
            left: var(--spacing-md);
            right: var(--spacing-md);
            background: var(--danger-color);
            color: var(--text-white);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-lg);
            display: none;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            font-weight: var(--font-weight-semibold);
            z-index: 500;
            box-shadow: var(--shadow-xl);
        }
        .brand-logo {
    width: 44px;
    height: 44px;
    border-radius: var(--border-radius-lg);
    overflow: hidden; /* لضمان انحناء زوايا الصورة */
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
}

.brand-logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* لقص الصورة بأسلوب متناسق دون تشويه */
}
        .connection-bar.visible {
            display: flex;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: var(--border-radius-full);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <header class="kds-header" role="banner">
        <div class="brand">
            <div class="brand-logo">
                <img src="{{ media_url($restaurant->logo) }}"
                             alt="{{ $restaurant->name }}"  class="brand-logo-img">
            </div>
            <div class="brand-info">
                <div class="brand-name">{{ $restaurant->name }}</div>
                <div class="brand-subtitle">شاشة عرض المطبخ</div>
            </div>
        </div>

        <div class="header-stats" aria-label="إحصائيات الطلبات">
            <div class="stat-chip active">
                <div class="stat-icon"><i class="fa-solid fa-fire"></i></div>
                <div>
                    <div class="stat-value" id="statActive">0</div>
                    <div class="stat-label">طلبات نشطة</div>
                </div>
            </div>
            <div class="stat-chip avg-time">
                <div class="stat-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <div class="stat-value" id="statAvgTime">0 د</div>
                    <div class="stat-label">متوسط التحضير</div>
                </div>
            </div>
            <div class="stat-chip urgent">
                <div class="stat-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div class="stat-value" id="statUrgent">0</div>
                    <div class="stat-label">طلبات متأخرة</div>
                </div>
            </div>
        </div>

        <div class="header-clock" aria-live="polite">
            <div class="clock-time" id="clockTime">--:--:--</div>
            <div class="clock-date" id="clockDate">--</div>
        </div>

        <div class="header-actions">
            <button class="icon-btn" id="btnLanPair" aria-label="إقران الكاشير عبر الشبكة المحلية" title="ربط الكاشير محلياً (LAN P2P)" style="width: auto; padding: 0 12px; gap: 6px; font-size: 0.8rem; font-weight: 700;">
                <i class="fa-solid fa-network-wired" id="lanPairIcon"></i>
                <span id="lanPairLabel">ربط الكاشير (LAN)</span>
            </button>
            <button class="icon-btn active" id="btnSound" aria-label="تشغيل/كتم الصوت" title="الصوت">
                <i class="fa-solid fa-volume-high"></i>
            </button>
            <button class="icon-btn" id="btnFullscreen" aria-label="ملء الشاشة" title="ملء الشاشة">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </header>

    <section class="order-availability-control" aria-label="حالة استقبال الطلبات">
        <div>
            <div class="order-availability-title">إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)</div>
            <span class="order-availability-status">
                {{ $restaurant->canAcceptOrders() ? 'استقبال الطلبات متاح' : ($restaurant->is_accepting_orders ? 'المطعم مغلق خارج أوقات العمل' : 'استقبال الطلبات متوقف مؤقتاً') }}
            </span>
        </div>
        <form method="POST" action="{{ route('restaurant.orders.availability', $restaurant->slug) }}">
            @csrf
            <input type="hidden" name="is_accepting_orders" value="{{ $restaurant->is_accepting_orders ? '0' : '1' }}">
            <button type="submit" class="order-availability-button {{ $restaurant->is_accepting_orders ? 'accepting' : 'paused' }}">
                {{ $restaurant->is_accepting_orders ? 'إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)' : 'استقبال الطلبات متاح' }}
            </button>
        </form>
    </section>

    <main class="kds-board" role="main">

        <section class="kds-column new" aria-label="الطلبات الجديدة">
            <header class="kds-column-header">
                <div class="column-title">
                    <span class="column-status-dot"></span>
                    <span>طلبات جديدة</span>
                </div>
                <span class="column-count" id="countNew">0</span>
            </header>
            <div class="kds-column-body" id="columnNew">
                
            </div>
        </section>

        <section class="kds-column preparing" aria-label="الطلبات قيد التحضير">
            <header class="kds-column-header">
                <div class="column-title">
                    <span class="column-status-dot"></span>
                    <span>جاري التحضير</span>
                </div>
                <span class="column-count" id="countPreparing">0</span>
            </header>
            <div class="kds-column-body" id="columnPreparing"></div>
        </section>

        <section class="kds-column ready" aria-label="الطلبات الجاهزة">
            <header class="kds-column-header">
                <div class="column-title">
                    <span class="column-status-dot"></span>
                    <span>جاهزة</span>
                </div>
                <span class="column-count" id="countReady">0</span>
            </header>
            <div class="kds-column-body" id="columnReady"></div>
        </section>
    </main>

    <div class="toast-container" id="toastContainer" aria-live="polite"></div>

    <div class="reject-modal" id="reject-modal" hidden>
        <form class="reject-modal-card" id="reject-form">
            <h2>رفض الطلب</h2>
            <label for="reject-reason">سبب الرفض</label>
            <select id="reject-reason" required>
                <option value="">اختر سبب الرفض</option>
                <option value="خارج نطاق التوصيل">خارج نطاق التوصيل</option>
                <option value="العنوان غير واضح">العنوان غير واضح</option>
                <option value="نفاد وجبات المطبخ">نفاد وجبات المطبخ</option>
                <option value="أخرى">أخرى</option>
            </select>
            <div class="reject-modal-actions">
                <button type="button" id="reject-cancel" class="action-btn ghost">إلغاء</button>
                <button type="submit" class="action-btn danger">تأكيد الرفض</button>
            </div>
        </form>
    </div>

    <div class="reject-modal" id="lan-pair-modal" hidden>
        <div class="reject-modal-card" style="max-width: 540px;">
            <h2>ربط شاشة المطبخ (KDS) بجهاز الكاشير عبر الشبكة المحلية (LAN P2P)</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px;">
                ترتبط شاشة المطبخ بجهاز الكاشير تلقائياً عبر WebRTC DataChannel محلياً وتستمر باستقبال الطلبات حتى عند انقطاع الإنترنت الخارجي. في حال بدء التشغيل دون إنترنت، الصق رمز عرض الكاشير (SDP Offer) أدناه:
            </p>
            <label for="kds-lan-offer-input">1. الصق رمز عرض الكاشير (SDP Offer):</label>
            <textarea id="kds-lan-offer-input" rows="3" style="width: 100%; font-family: monospace; font-size: 0.75rem; padding: 8px; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 8px;" placeholder="الصق رمز الإقران من شاشة الكاشير..."></textarea>
            <button type="button" id="kds-generate-answer-btn" class="action-btn primary" style="width: 100%; margin-bottom: 12px;">توليد رمز استجابة المطبخ (SDP Answer)</button>

            <label for="kds-lan-answer-output">2. انسخ رمز الاستجابة والصقه في جهاز الكاشير:</label>
            <textarea id="kds-lan-answer-output" rows="3" readonly style="width: 100%; font-family: monospace; font-size: 0.75rem; padding: 8px; border-radius: 8px; border: 1px solid #d1d5db; margin-bottom: 12px;" placeholder="سيظهر رمز الاستجابة هنا..."></textarea>

            <label for="kds-lan-ws-input">خيار إضافي: وسيط WebSocket محلي (اختياري):</label>
            <div style="display: flex; gap: 8px; margin-bottom: 14px;">
                <input type="url" id="kds-lan-ws-input" placeholder="ws://192.168.1.100:6001" style="flex: 1; padding: 8px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.8rem;">
                <button type="button" id="kds-save-ws-btn" class="action-btn ghost">حفظ</button>
            </div>

            <div class="reject-modal-actions">
                <button type="button" id="lan-pair-close" class="action-btn ghost">إغلاق</button>
            </div>
        </div>
    </div>

    <div class="connection-bar" id="connectionBar" role="alert">
        <div class="spinner"></div>
        <span>جاري إعادة الاتصال بالخادم...</span>
    </div>

    <script>
    const KDS_CONFIG = {
        restaurantSlug: @js($restaurant->slug),
        ordersEndpoint: @js(route('kitchen.orders.index', ['slug' => $restaurant->slug])),
        acknowledgementEndpoint: @js(route('kitchen.orders.acknowledge', ['slug' => $restaurant->slug])),
        statusEndpoint: @js(route('kitchen.orders.updateStatusForRestaurant', ['slug' => $restaurant->slug, 'orderId' => '__ORDER__'])),
        signalPublishEndpoint: @js(route('lan.signal.publish', ['slug' => $restaurant->slug])),
        signalPollEndpoint: @js(route('lan.signal.poll', ['slug' => $restaurant->slug])),
        localSyncEndpoint: @js(route('local.orders.store', ['slug' => $restaurant->slug])),
        websocketUrl: true,
        updateInterval: 10000,
        urgentThreshold: 15,
        restaurantId: {{ $orderCollection->first()->restaurant_id ?? $restaurant->id }},
        csrfToken: "{{ csrf_token() }}",
        rejectEndpoint: @js(route('kitchen.orders.rejectForRestaurant', ['slug' => $restaurant->slug, 'orderId' => '__ORDER__'])),
        demoMode: false
    };

    class KitchenDisplaySystem {
        constructor(config) {
            this.config = config;
            this.orders = new Map();
            this.pendingStatusUpdates = new Set();
            this.soundEnabled = true;
            this.audioContext = null;
            this.updateTimer = null;
            this.tickTimer = null;
            this.websocket = null;
            this.seenOrderIds = new Set();
            this.rejectingOrderId = null;

            this.columns = {
                new: document.getElementById('columnNew'),
                preparing: document.getElementById('columnPreparing'),
                ready: document.getElementById('columnReady')
            };

            this.counts = {
                new: document.getElementById('countNew'),
                preparing: document.getElementById('countPreparing'),
                ready: document.getElementById('countReady')
            };
        }

        async init() {
            this.initAudio();
            this.setupEventListeners();
            this.startClock();
            this.loadInitialData();
            this.startTimers();
            this.initLanBridge();

            if (!this.config.demoMode) {
                this.startAutoFetch();
                if (this.config.websocketUrl) {
                    this.connectWebSocket();
                }
            }
        }

        initLanBridge() {
            const pairBtn = document.getElementById('btnLanPair');
            const pairModal = document.getElementById('lan-pair-modal');
            const pairClose = document.getElementById('lan-pair-close');
            const generateAnswerBtn = document.getElementById('kds-generate-answer-btn');
            const offerInput = document.getElementById('kds-lan-offer-input');
            const answerOutput = document.getElementById('kds-lan-answer-output');
            const wsInput = document.getElementById('kds-lan-ws-input');
            const saveWsBtn = document.getElementById('kds-save-ws-btn');
            const pairLabel = document.getElementById('lanPairLabel');

            if (pairBtn && pairModal) {
                pairBtn.addEventListener('click', () => { pairModal.hidden = false; });
            }
            if (pairClose && pairModal) {
                pairClose.addEventListener('click', () => { pairModal.hidden = true; });
            }

            const setupBridgeInstance = () => {
                if (!window.LanPosKdsBridge || this.lanBridge) return;
                this.lanBridge = new window.LanPosKdsBridge({
                    restaurantSlug: this.config.restaurantSlug,
                    role: 'kds',
                    syncEndpoint: this.config.localSyncEndpoint,
                    signalPublishEndpoint: this.config.signalPublishEndpoint,
                    signalPollEndpoint: this.config.signalPollEndpoint,
                    csrfToken: this.config.csrfToken,
                    onConnectionStateChanged: (state) => {
                        if (!pairLabel || !pairBtn) return;
                        if (state === 'connected_webrtc_lan') {
                            pairLabel.textContent = 'متصل بالكاشير (LAN P2P)';
                            pairBtn.classList.add('active');
                        } else if (state === 'connected_ws_lan') {
                            pairLabel.textContent = 'متصل عبر وسيط LAN';
                            pairBtn.classList.add('active');
                        } else {
                            pairLabel.textContent = 'ربط الكاشير (LAN)';
                            pairBtn.classList.remove('active');
                        }
                    },
                    onOrderReceived: (lanOrder) => {
                        const normalizedType = (lanOrder.delivery_type || 'takeaway').replace('_', '-');
                        const displayId = lanOrder.order_number || `LAN-${String(lanOrder.client_order_id || '').slice(0, 6).toUpperCase()}`;
                        this.handleNewOrder({
                            id: displayId,
                            client_order_id: lanOrder.client_order_id,
                            status: 'new',
                            type: normalizedType,
                            customer_name: lanOrder.customer_name || 'طلب شبكة محلية (LAN)',
                            notes: lanOrder.notes || '',
                            created_at: lanOrder.created_at_iso || new Date().toISOString(),
                            items: Array.isArray(lanOrder.items) ? lanOrder.items.map((item) => ({
                                name: item.name || item.product_name || 'صنف',
                                qty: item.quantity || item.qty || 1,
                                unit_price: item.unit_price ?? null,
                                notes: item.notes || '',
                            })) : [],
                        });
                    },
                });

                if (wsInput && this.lanBridge.lanWsRelayUrl) {
                    wsInput.value = this.lanBridge.lanWsRelayUrl;
                }
            };

            setupBridgeInstance();
            window.addEventListener('load', setupBridgeInstance);

            if (generateAnswerBtn && offerInput && answerOutput) {
                generateAnswerBtn.addEventListener('click', async () => {
                    setupBridgeInstance();
                    if (!this.lanBridge || !offerInput.value.trim()) return;
                    try {
                        answerOutput.value = await this.lanBridge.acceptManualOfferAndCreateAnswer(offerInput.value.trim());
                        this.showToast('success', 'تم توليد رمز الاستجابة', 'انسخ رمز الاستجابة والصقه في جهاز الكاشير لإتمام الربط المباشر.');
                    } catch (e) {
                        this.showToast('error', 'خطأ في الرمز', 'رمز عرض الكاشير غير صالح.');
                    }
                });
            }

            if (saveWsBtn && wsInput) {
                saveWsBtn.addEventListener('click', () => {
                    setupBridgeInstance();
                    if (!this.lanBridge) return;
                    this.lanBridge.setLanWsRelayUrl(wsInput.value.trim());
                    this.showToast('info', 'وسيط الشبكة المحلية', 'تم حفظ عنوان وسيط WebSocket المحلي.');
                });
            }
        }

        initAudio() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    this.audioContext = new AudioContext();
                }
            } catch (e) {
                // AudioContext غير مدعوم في المتصفح
            }
        }

        playDing() {
            if (!this.soundEnabled) return;

            try {
                if (!this.audioContext) {
                    this.initAudio();
                }

                if (this.audioContext && this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }

                if (this.audioContext) {
                    const oscillator = this.audioContext.createOscillator();
                    const gainNode = this.audioContext.createGain();

                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, this.audioContext.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(
                        1320, this.audioContext.currentTime + 0.1
                    );

                    gainNode.gain.setValueAtTime(0.4, this.audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(
                        0.01, this.audioContext.currentTime + 0.35
                    );

                    oscillator.connect(gainNode);
                    gainNode.connect(this.audioContext.destination);

                    oscillator.start();
                    oscillator.stop(this.audioContext.currentTime + 0.35);
                }
            } catch (e) {
                // تجميع الأخطاء في حال منع المتصفح التشغيل التلقائي
            }
        }

        setupEventListeners() {
            const unlockAudio = () => {
                if (this.audioContext && this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }
            };
            document.addEventListener('click', unlockAudio, {
                once: true
            });
            document.addEventListener('keydown', unlockAudio, {
                once: true
            });

            document.getElementById('btnSound').addEventListener('click', () => {
                this.soundEnabled = !this.soundEnabled;
                const btn = document.getElementById('btnSound');
                const icon = btn.querySelector('i');

                if (this.soundEnabled) {
                    btn.classList.add('active');
                    icon.className = 'fa-solid fa-volume-high';
                    if (this.audioContext && this.audioContext.state === 'suspended') {
                        this.audioContext.resume();
                    }
                    this.playDing();
                    this.showToast('success', 'تم تشغيل الصوت', 'سيتم تنبيهك عند وصول طلبات جديدة');
                } else {
                    btn.classList.remove('active');
                    icon.className = 'fa-solid fa-volume-xmark';
                    this.showToast('warning', 'تم كتم الصوت', 'لن يتم تنبيهك صوتياً');
                }
            });

            document.getElementById('btnFullscreen').addEventListener('click', () => {
                this.toggleFullscreen();
            });

            document.getElementById('reject-cancel').addEventListener('click', () => this.closeRejectDialog());
            document.getElementById('reject-form').addEventListener('submit', (event) => {
                event.preventDefault();
                this.submitRejection();
            });

            document.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea')) return;

                if (e.key === 'f' || e.key === 'F') {
                    this.toggleFullscreen();
                }
                if (e.key === 'm' || e.key === 'M') {
                    document.getElementById('btnSound').click();
                }
            });
        }

        toggleFullscreen() {
            const btn = document.getElementById('btnFullscreen');
            const icon = btn.querySelector('i');

            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    icon.className = 'fa-solid fa-compress';
                }).catch(() => {
                    this.showToast('error', 'خطأ', 'تعذر تفعيل ملء الشاشة');
                });
            } else {
                document.exitFullscreen().then(() => {
                    icon.className = 'fa-solid fa-expand';
                });
            }
        }

        startClock() {
            const updateClock = () => {
                const now = new Date();
                const time = now.toLocaleTimeString('ar-SA', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                const date = now.toLocaleDateString('ar-SA', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric'
                });

                document.getElementById('clockTime').textContent = time;
                document.getElementById('clockDate').textContent = date;
            };

            updateClock();
            setInterval(updateClock, 1000);
        }

        loadInitialData() {
            const initialOrders = @json(\App\Http\Resources\KdsOrderResource::collection($orderCollection));

            initialOrders.forEach(order => {
                this.orders.set(order.id, order);
                this.seenOrderIds.add(order.id);
            });

            this.renderAllOrders();
        }

        startAutoFetch() {
            this.updateTimer = setInterval(() => {
                this.fetchOrders();
            }, this.config.updateInterval);
        }

        async fetchOrders() {
            try {
                const response = await fetch(this.config.ordersEndpoint, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });

                if (!response.ok) throw new Error(`Order refresh failed (${response.status})`);

                const payload = await response.json();
                const activeOrders = Array.isArray(payload.data) ? payload.data : payload;

                if (!Array.isArray(activeOrders)) throw new Error('Order refresh returned an invalid payload');

                try {
                    await window.localKdsReceipts.persistAndAcknowledge(
                        this.config.restaurantId,
                        activeOrders,
                        this.config.acknowledgementEndpoint,
                        this.config.csrfToken,
                    );
                } catch (error) {
                    console.error('KDS ticket receipt is pending; the server will redeliver it.', error);
                }

                const activeIds = new Set();
                activeOrders.forEach(order => {
                    const orderId = String(order.id);
                    activeIds.add(orderId);

                    if (this.pendingStatusUpdates.has(orderId)) return;

                    if (!this.orders.has(orderId)) {
                        this.handleNewOrder(order);
                    } else {
                        this.orders.set(orderId, order);
                    }
                });

                this.orders.forEach((order, orderId) => {
                    if (!activeIds.has(String(orderId)) && !this.pendingStatusUpdates.has(orderId)) {
                        this.orders.delete(orderId);
                    }
                });

                this.renderAllOrders();
            } catch (error) {
                console.error('KDS order refresh failed:', error);
            }
        }

        connectWebSocket() {
            if (typeof window.Echo === 'undefined') {
                if (!this.echoUnavailableLogged) {
                    console.warn('[KDS Reverb Debug] Waiting for Echo to initialize.');
                    this.echoUnavailableLogged = true;
                }

                setTimeout(() => this.connectWebSocket(), 200);
                return;
            }

            const restaurantId = this.config.restaurantId;
            const channelName = `restaurant.${restaurantId}.kds`;

            console.log('🔍 [KDS Reverb Debug] Initializing Echo for Restaurant ID:', restaurantId);

            const connection = window.Echo.connector?.pusher?.connection;

            if (connection) {
                connection.bind('state_change', (states) => {
                    console.log('🔌 [KDS Reverb State]:', states.previous, '->', states.current);
                });
                connection.bind('error', (error) => {
                    console.error('❌ [KDS Reverb Connection Error]:', error);
                });
            } else {
                console.warn('[KDS Reverb Debug] Pusher connection diagnostics are unavailable.');
            }

            console.log(`📡 [KDS Reverb Subscribing to Private Channel]: ${channelName}`);

            const channel = window.Echo.private(channelName);

            channel.subscribed(() => {
                console.log(`✅ [KDS Reverb Subscribed Successfully]: Joined channel ${channelName}`);
            });
            channel.error((status) => {
                console.error('🚨 [KDS Reverb Subscription Auth Error]:', status);
            });
            channel.listen('.OrderCreated', (event) => {
                console.log('🎉 [KDS Reverb EVENT RECEIVED] .OrderCreated:', event);

                if (event && event.order) {
                    this.handleNewOrder(event.order);
                } else {
                    console.error('[KDS Reverb Debug] OrderCreated event has no order payload:', event);
                }
            });
            channel.listenToAll((eventName, data) => {
                console.warn(`📩 [KDS Reverb RAW EVENT DIRECTED]: ${eventName}`, data);
            });
        }

        startTimers() {
            this.tickTimer = setInterval(() => {
                this.updateAllTimers();
                this.updateStats();
            }, 30000);
        }

        updateAllTimers() {
            this.orders.forEach((order, id) => {
                const timerEl = document.querySelector(`[data-order-id="${id}"] .order-timer`);
                if (!timerEl) return;

                const elapsed = this.getElapsedMinutes(order.created_at);
                const card = document.querySelector(`[data-order-id="${id}"]`);

                timerEl.innerHTML = `<i class="fa-regular fa-clock"></i> منذ ${elapsed} د`;

                timerEl.classList.remove('warning', 'urgent');
                if (card) card.classList.remove('urgent');

                if (order.status !== 'ready') {
                    if (elapsed >= this.config.urgentThreshold) {
                        timerEl.classList.add('urgent');
                        if (card) card.classList.add('urgent');
                    } else if (elapsed >= this.config.urgentThreshold * 0.7) {
                        timerEl.classList.add('warning');
                    }
                }
            });
        }

        getElapsedMinutes(isoDate) {
            const created = new Date(isoDate).getTime();
            const now = Date.now();
            return Math.floor((now - created) / 60000);
        }

        renderAllOrders() {
            Object.values(this.columns).forEach(col => col.innerHTML = '');

            const grouped = {
                new: [],
                preparing: [],
                ready: []
            };

            this.orders.forEach(order => {
                if (grouped[order.status]) {
                    grouped[order.status].push(order);
                }
            });

            Object.keys(grouped).forEach(status => {
                grouped[status].sort((a, b) =>
                    new Date(a.created_at) - new Date(b.created_at)
                );
            });

            grouped.new.forEach(order => this.columns.new.appendChild(this.createOrderCard(order)));
            grouped.preparing.forEach(order => this.columns.preparing.appendChild(this.createOrderCard(order)));
            grouped.ready.forEach(order => this.columns.ready.appendChild(this.createOrderCard(order)));

            Object.keys(this.columns).forEach(status => {
                if (grouped[status].length === 0) {
                    this.columns[status].innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-text">لا توجد طلبات</div>
                    </div>
                `;
                }
            });

            this.updateStats();
        }

        createOrderCard(order) {
            const card = document.createElement('article');
            card.className = `order-card type-${order.type} status-${order.status}`;
            card.dataset.orderId = order.id;
            card.setAttribute('role', 'article');
            card.setAttribute('aria-label', `طلب رقم ${order.id}`);

            const elapsed = this.getElapsedMinutes(order.created_at);

            let timerClass = 'order-timer';
            if (order.status !== 'ready') {
                if (elapsed >= this.config.urgentThreshold) {
                    timerClass += ' urgent';
                    card.classList.add('urgent');
                } else if (elapsed >= this.config.urgentThreshold * 0.7) {
                    timerClass += ' warning';
                }
            }

            const newBadge = order.status === 'new' && elapsed < 1 ?
                `<div class="new-badge"><i class="fa-solid fa-circle"></i> جديد</div>` :
                '';

            let extraInfo = '';
            if (order.type === 'dine-in' && order.table_number) {
                extraInfo = `
                <div class="table-badge">
                    <i class="fa-solid fa-utensils"></i>
                    <span>طاولة ${order.table_number}</span>
                </div>
            `;
            } else if (order.type === 'delivery') {
                if (order.driver_name) {
                    extraInfo = `
                    <div class="driver-info">
                        <i class="fa-solid fa-motorcycle"></i>
                        <span>المندوب: ${this.escapeHtml(order.driver_name)}</span>
                    </div>
                `;
                } else {
                    extraInfo = `
                    <div class="driver-info" style="background: rgba(239, 68, 68, 0.08); color: var(--danger-color); border-color: rgba(239, 68, 68, 0.2);">
                        <i class="fa-solid fa-clock"></i>
                        <span>بانتظار المندوب</span>
                    </div>
                `;
                }
            }

            const typeIcons = {
                'dine-in': {
                    icon: '🪑',
                    label: 'طاولة'
                },
                'takeaway': {
                    icon: '🛍️',
                    label: 'استلام'
                },
                'delivery': {
                    icon: '🛵',
                    label: 'توصيل'
                }
            };
            const typeInfo = typeIcons[order.type] || {
                icon: '📦',
                label: 'طلب'
            };

            const itemsHtml = (order.items || []).map(item => {
                const itemName = item.name || item.product_name || 'منتج';
                const itemQty = item.qty || item.quantity || 1;

                return `
                <li class="item-row">
                    <span class="item-qty">${itemQty}×</span>
                    <div class="item-details">
                        <div class="item-name">${this.escapeHtml(itemName)}</div>
                    ${item.unit_price !== undefined && item.unit_price !== null ? `
                        <div class="item-price">${Number(item.unit_price).toLocaleString('en-US')} ل.س</div>
                    ` : ''}
                    ${item.notes ? `
                                <div class="item-note">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>${this.escapeHtml(item.notes)}</span>
                                </div>
                            ` : ''}
                    </div>
                </li>
            `;
            }).join('');

            const orderNote = order.notes || order.order_note || order.note;
            const orderNoteHtml = orderNote ? `
            <div class="order-general-note" style="margin: 8px 0; padding: 6px 10px; background: rgba(245, 158, 11, 0.1); border-right: 3px solid #f59e0b; border-radius: 4px; color: #b45309; font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-note-sticky"></i>
                <span>ملاحظة الطلب: ${this.escapeHtml(orderNote)}</span>
            </div>
        ` : '';
            const dineInArrival = order.dine_in_arrival_at
                ? new Date(order.dine_in_arrival_at).toLocaleString('ar', { dateStyle: 'medium', timeStyle: 'short' })
                : '';
            const dineInReservationHtml = dineInArrival ? `
            <div class="order-general-note" style="margin: 8px 0; padding: 6px 10px; background: rgba(234, 88, 12, 0.1); border-right: 3px solid #ea580c; border-radius: 4px; color: #9a3412; font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-utensils"></i>
                <span>حجز طاولة ${order.table_number ? `#${this.escapeHtml(order.table_number)}` : ''}: ${this.escapeHtml(dineInArrival)}${order.dine_in_duration_minutes ? ` · ${Number(order.dine_in_duration_minutes)} دقيقة` : ''}${order.dine_in_guest_count ? ` · ${Number(order.dine_in_guest_count)} ضيوف` : ''}</span>
                ${order.dine_in_confirmation_call ? '<span class="block">اتصال مطلوب لتأكيد الحجز</span>' : ''}
                ${order.dine_in_allergy_notes ? `<span class="block">حساسية: ${this.escapeHtml(order.dine_in_allergy_notes)}</span>` : ''}
            </div>
        ` : '';
            const pickupAt = order.requested_pickup_at
                ? new Date(order.requested_pickup_at).toLocaleString('ar', { dateStyle: 'medium', timeStyle: 'short' })
                : '';
            const scheduledPickupHtml = pickupAt ? `
            <div class="order-general-note" style="margin: 8px 0; padding: 6px 10px; background: rgba(14, 165, 233, 0.1); border-right: 3px solid #0ea5e9; border-radius: 4px; color: #075985; font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-store"></i>
                <span>استلام من المطعم: ${this.escapeHtml(pickupAt)}</span>
            </div>
        ` : '';
            const depositHtml = order.deposit_amount > 0 ? `
            <div class="order-general-note" style="margin: 8px 0; padding: 6px 10px; background: rgba(16, 185, 129, 0.1); border-right: 3px solid #10b981; border-radius: 4px; color: #047857; font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-circle-check"></i>
                <span>تم تأكيد العربون: ${Number(order.deposit_amount).toFixed(2)} ل.س</span>
            </div>
        ` : '';

            let actionsHtml = '';
            if (order.status === 'new') {
                actionsHtml = `
                <button class="action-btn primary" data-action="start" data-order-id="${order.id}">
                    <i class="fa-solid fa-play"></i>
                    <span>ابدأ التحضير</span>
                </button>
            `;
            } else if (order.status === 'preparing') {
                actionsHtml = `
                <button class="action-btn ghost" data-action="back" data-order-id="${order.id}">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button class="action-btn success" data-action="ready" data-order-id="${order.id}">
                    <i class="fa-solid fa-check"></i>
                    <span>تم التحضير / جاهز</span>
                </button>
            `;
            } else if (order.status === 'ready') {
                actionsHtml = `
                <button class="action-btn ghost" data-action="back" data-order-id="${order.id}">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>إرجاع</span>
                </button>
                <button class="action-btn warning" data-action="complete" data-order-id="${order.id}">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>أرشفة / تم التسليم</span>
                </button>
            `;
            }

            actionsHtml += `
                <button class="action-btn danger" data-action="reject" data-order-id="${order.id}">
                    <i class="fa-solid fa-xmark"></i>
                    <span>رفض الطلب</span>
                </button>
            `;

            const locationHtml = order.customer_lat && order.customer_lng ? `
                <a class="delivery-location" href="https://www.google.com/maps?q=${encodeURIComponent(order.customer_lat)},${encodeURIComponent(order.customer_lng)}" target="_blank" rel="noopener noreferrer">عرض الموقع</a>
            ` : '';
            const distanceHtml = order.distance_km !== null && order.distance_km !== undefined ? `
                <span class="delivery-distance">${Number(order.distance_km).toFixed(2)} كم</span>
            ` : '';

            card.innerHTML = `
            ${newBadge}
            <header class="card-header">
                <div class="order-type-badge ${order.type}">
                    <span class="type-icon">${typeInfo.icon}</span>
                    <span>${typeInfo.label}</span>
                </div>
                <div class="${timerClass}">
                    <i class="fa-regular fa-clock"></i>
                    <span>منذ ${elapsed} د</span>
                </div>
            </header>

            <div class="order-info">
                <div class="order-id-row">
                    <h3 class="order-id">#${order.id}</h3>
                    ${extraInfo}
                </div>
                ${order.customer_name ? `
                        <div class="customer-name">
                            <i class="fa-regular fa-user"></i>
                            <span>${this.escapeHtml(order.customer_name)}</span>
                        </div>
                    ` : ''}
                ${distanceHtml}
                ${locationHtml}
                ${dineInReservationHtml}
                ${scheduledPickupHtml}
                ${depositHtml}
            </div>

            <ul class="items-list">
                ${itemsHtml}
            </ul>

            ${orderNoteHtml}

            <div class="card-actions">
                ${actionsHtml}
            </div>
        `;

            card.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const action = btn.dataset.action;
                    const orderId = btn.dataset.orderId;
                    this.handleAction(action, orderId);
                });
            });

            return card;
        }

        async handleAction(action, orderId) {
            if (action === 'reject') {
                this.openRejectDialog(orderId);
                return;
            }

            const order = this.orders.get(orderId);
            if (!order || this.pendingStatusUpdates.has(orderId)) return;

            const statusMap = {
                'start': 'preparing',
                'ready': 'ready',
                'complete': 'completed',
                'back': this.getPreviousStatus(order.status)
            };

            const newStatus = statusMap[action];
            if (!newStatus) return;

            const targetId = String(orderId).replace('#', '').trim();
            const previousStatus = order.status;
            this.pendingStatusUpdates.add(orderId);

            if (newStatus === 'completed') {
                this.orders.delete(orderId);
            } else {
                order.status = newStatus;
                this.orders.set(orderId, order);
            }
            this.renderAllOrders();

            if (this.lanBridge) {
                this.lanBridge.publishKdsStatusChange(order.client_order_id || orderId, newStatus);
            }

            if (String(orderId).startsWith('LAN-') && !navigator.onLine) {
                this.pendingStatusUpdates.delete(orderId);
                this.showToast('info', 'تحديث محلي (LAN)', `تم تحديث حالة الطلب #${orderId} وإشعار الكاشير عبر الشبكة المحلية`);
                return;
            }

            try {
                const response = await fetch(this.config.statusEndpoint.replace('__ORDER__', encodeURIComponent(targetId)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                if (!response.ok) throw new Error('فشل التحديث في السيرفر');

                if (newStatus === 'completed') {
                    this.showToast('success', 'تم التسليم', `تم أرشفة الطلب #${orderId} بنجاح`);
                } else {
                    const statusLabels = {
                        'new': 'طلبات جديدة',
                        'preparing': 'جاري التحضير',
                        'ready': 'جاهزة'
                    };
                    this.showToast('success', 'تم التحديث', `الطلب #${orderId} ← ${statusLabels[newStatus]}`);
                }
            } catch (error) {
                if (!String(orderId).startsWith('LAN-')) {
                    order.status = previousStatus;
                    this.orders.set(orderId, order);
                    this.renderAllOrders();
                    this.showToast('error', 'خطأ', 'تعذر تحديث حالة الطلب في السيرفر');
                }
            } finally {
                this.pendingStatusUpdates.delete(orderId);
            }
        }

        openRejectDialog(orderId) {
            this.rejectingOrderId = orderId;
            document.getElementById('reject-form').reset();
            document.getElementById('reject-modal').hidden = false;
            document.getElementById('reject-reason').focus();
        }

        closeRejectDialog() {
            this.rejectingOrderId = null;
            document.getElementById('reject-modal').hidden = true;
        }

        async submitRejection() {
            const orderId = this.rejectingOrderId;
            const order = this.orders.get(orderId);
            const reason = document.getElementById('reject-reason').value;
            if (!order || !reason) return;

            const targetId = encodeURIComponent(String(orderId).replace('#', '').trim());
            const submitButton = document.querySelector('#reject-form [type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(this.config.rejectEndpoint.replace('__ORDER__', targetId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ rejection_reason: reason })
                });

                if (!response.ok) throw new Error('تعذر رفض الطلب في الخادم.');

                this.orders.delete(orderId);
                this.closeRejectDialog();
                this.renderAllOrders();
                this.showToast('success', 'تم رفض الطلب', `تم رفض الطلب #${orderId}`);
            } catch (error) {
                this.showToast('error', 'خطأ', error.message);
            } finally {
                submitButton.disabled = false;
            }
        }

        getPreviousStatus(currentStatus) {
            const flow = {
                'preparing': 'new',
                'ready': 'preparing'
            };
            return flow[currentStatus] || currentStatus;
        }

        handleNewOrder(order) {
            this.orders.set(order.id, order);
            if (!this.seenOrderIds.has(order.id)) {
                this.seenOrderIds.add(order.id);
                this.playDing();
                this.showToast('info', 'طلب جديد!', `وصل طلب جديد #${order.id}`);
            }
            this.renderAllOrders();
        }

        updateStats() {
            let active = 0;
            let urgent = 0;
            let totalTime = 0;
            let countForAvg = 0;

            this.orders.forEach(order => {
                if (order.status !== 'completed') {
                    active++;
                    const elapsed = this.getElapsedMinutes(order.created_at);
                    totalTime += elapsed;
                    countForAvg++;

                    if (elapsed >= this.config.urgentThreshold && order.status !== 'ready') {
                        urgent++;
                    }
                }
            });

            const avgTime = countForAvg > 0 ? Math.round(totalTime / countForAvg) : 0;

            document.getElementById('statActive').textContent = active;
            document.getElementById('statAvgTime').textContent = `${avgTime} د`;
            document.getElementById('statUrgent').textContent = urgent;

            const counts = {
                new: 0,
                preparing: 0,
                ready: 0
            };
            this.orders.forEach(order => {
                if (counts.hasOwnProperty(order.status)) {
                    counts[order.status]++;
                }
            });

            Object.keys(counts).forEach(status => {
                if (this.counts[status]) {
                    this.counts[status].textContent = counts[status];
                }
            });
        }

        showToast(type, title, message, duration = 4000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const icons = {
                success: 'fa-solid fa-circle-check',
                warning: 'fa-solid fa-triangle-exclamation',
                error: 'fa-solid fa-circle-xmark',
                info: 'fa-solid fa-circle-info'
            };

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icons[type] || icons.info}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
        `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideInToast 0.3s ease-in reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        destroy() {
            if (this.updateTimer) clearInterval(this.updateTimer);
            if (this.tickTimer) clearInterval(this.tickTimer);
            if (this.websocket) this.websocket.close();
            if (this.audioContext) this.audioContext.close();
        }
    }

    let kds;
    document.addEventListener('DOMContentLoaded', () => {
        kds = new KitchenDisplaySystem(KDS_CONFIG);
        kds.init();

        window.addEventListener('beforeunload', () => {
            kds.destroy();
        });
    });
</script>
</body>

</html>