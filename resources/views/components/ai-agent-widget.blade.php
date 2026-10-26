@props([
    'restaurant',
    'mode' => 'customer', // 'customer' | 'restaurant_admin'
])

@php
    $isEnabled = $restaurant && $restaurant->hasFeature('ai_assistant');
    $assistEndpoint = $restaurant ? route('restaurant.ai-agent.assist', $restaurant->slug) : route('ai-agent.assist');
    $cartAddEndpoint = $restaurant ? route('cart.add', $restaurant->slug) : null;
    $currency = $restaurant?->currency_symbol ?: 'ل.س';
    $widgetId = 'ai-agent-' . ($mode === 'restaurant_admin' ? 'admin' : 'customer');
@endphp

@if($isEnabled)
    @if($mode === 'restaurant_admin')
        <section id="{{ $widgetId }}" dir="rtl" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-900 px-6 py-4 text-white sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-orange-500/20 px-2.5 py-0.5 text-xs font-bold text-orange-300 border border-orange-400/30">
                            المستشار التشغيلي والمالي الذكي
                        </span>
                        <span class="text-xs text-slate-400">معزول ومخصص لبيانات {{ $restaurant->name }}</span>
                    </div>
                    <h2 class="mt-1.5 text-lg font-black tracking-tight text-white">
                        وكيل الذكاء الاصطناعي لإدارة المطعم (AI Copilot)
                    </h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-ai-quick-prompt="تحليل صافي الربح والمصاريف هذا الشهر" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-orange-400 hover:text-white">
                        تحليل الأرباح والمصاريف
                    </button>
                    <button type="button" data-ai-quick-prompt="هندسة المنيو: الأصناف الأكثر مبيعاً والأقل طلباً" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-orange-400 hover:text-white">
                        هندسة المنيو
                    </button>
                    <button type="button" data-ai-quick-prompt="تشخيص شاشة المطبخ (KDS) والطلبات المتأخرة" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-orange-400 hover:text-white">
                        تشخيص المطبخ KDS
                    </button>
                    <button type="button" data-ai-quick-prompt="تقرير أسطول التوصيل وإحالات الشركات الخارجية" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-orange-400 hover:text-white">
                        أسطول التوصيل
                    </button>
                </div>
            </div>

            <div class="grid gap-0 lg:grid-cols-[1.35fr_0.65fr]">
                <div class="flex flex-col justify-between border-b border-slate-100 p-5 lg:border-b-0 lg:border-l">
                    <div data-ai-messages class="max-h-80 min-h-[13rem] space-y-3 overflow-y-auto pr-1 text-sm">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-800">
                            <p class="font-bold text-slate-900">مرحباً بك في المستشار الذكي لمطعم {{ $restaurant->name }}.</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-600">
                                تم تدريبي على بيانات مطعمك الحية (المبيعات المؤكدة، سجلات المصاريف، هندسة أصناف المنيو، شاشة المطبخ KDS، التوصيل الداخلي والخارجي، والكوبونات). اسألني مباشرة أو اختر أحد التحليلات السريعة.
                            </p>
                        </div>
                    </div>

                    <div data-ai-suggested-bar class="mt-3 flex flex-wrap gap-1.5"></div>

                    <form data-ai-form class="mt-4 flex items-center gap-2">
                        <input
                            type="text"
                            data-ai-input
                            maxlength="1200"
                            placeholder="اسأل عن صافي الربح، المصاريف، الأصناف الأكثر مبيعاً، ضغط المطبخ، أو أوقف الطلبات مؤقتاً..."
                            class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:outline-none"
                        >
                        <button
                            type="submit"
                            data-ai-submit
                            class="rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-orange-700 disabled:opacity-50"
                        >
                            تحليل وإجابة
                        </button>
                    </form>
                </div>

                <div class="bg-slate-50/70 p-5">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700">مؤشرات وبطاقات القرار اللحظية</h3>
                        <span data-ai-intent-badge class="rounded bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700">جاهز</span>
                    </div>

                    <div data-ai-cards class="mt-3 space-y-2.5" style="font-variant-numeric: tabular-nums;">
                        <p class="py-6 text-center text-xs text-slate-500">
                            اضغط على أي زر تحليلي أعلاه لعرض مؤشرات الأرباح والمطبخ والتوصيل المباشرة.
                        </p>
                    </div>

                    <div data-ai-confirm-box class="mt-3 hidden rounded-xl border border-amber-300 bg-amber-50 p-3.5">
                        <p data-ai-confirm-label class="text-xs font-bold text-amber-950"></p>
                        <div class="mt-2.5 flex items-center gap-2">
                            <button type="button" data-ai-confirm-btn class="rounded-lg bg-amber-700 px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-amber-800">
                                تأكيد التنفيذ الفوري
                            </button>
                            <button type="button" data-ai-cancel-btn class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-bold text-amber-900">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
        <!-- Customer Storefront AI Concierge Floating Launcher & Drawer -->
        <div id="{{ $widgetId }}" dir="rtl" class="fixed bottom-6 left-6 z-50">
            <button
                type="button"
                data-ai-toggle
                aria-expanded="false"
                aria-controls="{{ $widgetId }}-panel"
                class="flex items-center gap-2.5 rounded-full border border-white/20 bg-gray-900 px-4 py-3 text-sm font-bold text-white shadow-2xl transition hover:bg-primary focus:outline-none"
            >
                <i class="fa-solid fa-wand-magic-sparkles text-primary transition group-hover:text-white" aria-hidden="true"></i>
                <span>المساعد الذكي للطلبات</span>
            </button>

            <section
                id="{{ $widgetId }}-panel"
                data-ai-panel
                class="fixed bottom-20 left-4 right-4 z-50 hidden max-h-[82vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl sm:left-6 sm:right-auto sm:w-[26rem]"
                role="dialog"
                aria-label="المساعد الذكي لمطعم {{ $restaurant->name }}"
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-900 px-4 py-3.5 text-white">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-black text-white">مساعد {{ $restaurant->name }} الذكي</h3>
                            <span class="rounded bg-white/10 px-2 py-0.5 text-[10px] font-bold text-orange-300">AI Concierge</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-gray-300">اقتراح وجبات حسب الميزانية · عروض · حساب التوصيل · تتبع الطلب</p>
                    </div>
                    <button
                        type="button"
                        data-ai-close
                        aria-label="إغلاق المساعد الذكي"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-300 transition hover:bg-white/10 hover:text-white"
                    >
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Interactive Budget & Location Context Toolbar -->
                <div class="border-b border-gray-100 bg-gray-50 px-3.5 py-2.5 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1">
                            <span class="text-gray-500">الميزانية:</span>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                data-ai-budget
                                placeholder="مثال: 50"
                                class="w-16 border-0 bg-transparent p-0 text-xs font-bold text-gray-800 focus:outline-none focus:ring-0"
                                style="font-variant-numeric: tabular-nums;"
                            >
                            <span class="text-[10px] text-gray-400">{{ $currency }}</span>
                        </div>
                        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1">
                            <span class="text-gray-500">الأشخاص:</span>
                            <input
                                type="number"
                                min="1"
                                max="30"
                                data-ai-party
                                placeholder="2"
                                class="w-10 border-0 bg-transparent p-0 text-xs font-bold text-gray-800 focus:outline-none focus:ring-0"
                                style="font-variant-numeric: tabular-nums;"
                            >
                        </div>
                        <button
                            type="button"
                            data-ai-share-gps
                            class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 font-bold text-gray-700 transition hover:border-primary hover:text-primary"
                        >
                            <i class="fa-solid fa-location-crosshairs text-[11px]" aria-hidden="true"></i>
                            <span data-ai-gps-label>حساب التوصيل لموقعي</span>
                        </button>
                    </div>
                </div>

                <!-- Conversation Stream -->
                <div data-ai-messages class="flex-1 space-y-3 overflow-y-auto p-4 text-sm" style="max-height: 22rem;">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-3.5 text-gray-800">
                        <p class="font-bold text-gray-900">أهلاً بك في {{ $restaurant->name }}!</p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-600">
                            أنا مساعدك الذكي المدرب على قائمة {{ $restaurant->name }}. يمكنني ترتيب وجبة تناسب ميزانيتك وعدد الأشخاص، عرض الكوبونات الفعالة، حساب رسوم التوصيل لموقعك، أو تتبع طلبك برقم الطلب.
                        </p>
                    </div>
                </div>

                <!-- Dynamic Action Cards (Products with Add to Cart / Coupons) -->
                <div data-ai-cards class="max-h-44 space-y-2 overflow-y-auto border-t border-gray-100 bg-gray-50/60 px-3.5 py-2 empty:hidden" style="font-variant-numeric: tabular-nums;"></div>

                <!-- Quick Prompt Chips -->
                <div data-ai-suggested-bar class="flex gap-1.5 overflow-x-auto border-t border-gray-100 bg-white px-3.5 py-2 text-xs">
                    <button type="button" data-ai-quick-prompt="اقترح لي أفضل الوجبات المتوفرة الآن" class="shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 font-bold text-gray-700 transition hover:border-primary hover:text-primary">
                        أفضل الوجبات
                    </button>
                    <button type="button" data-ai-quick-prompt="رتب لي وجبة لـ 3 أشخاص بميزانية اقتصادية" class="shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 font-bold text-gray-700 transition hover:border-primary hover:text-primary">
                        كومبو التوفير
                    </button>
                    <button type="button" data-ai-quick-prompt="ما هي العروض وأكواد الخصم الفعالة؟" class="shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 font-bold text-gray-700 transition hover:border-primary hover:text-primary">
                        العروض والكوبونات
                    </button>
                    <button type="button" data-ai-quick-prompt="أريد حجز طاولة ومعرفة أوقات العمل" class="shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 font-bold text-gray-700 transition hover:border-primary hover:text-primary">
                        حجز طاولة والدوام
                    </button>
                </div>

                <!-- Input Form -->
                <form data-ai-form class="flex items-center gap-2 border-t border-gray-100 bg-white p-3">
                    <input
                        type="text"
                        data-ai-input
                        maxlength="1200"
                        placeholder="اكتب سؤالك أو رقم طلبك للتتبع (مثال: #102)..."
                        class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-medium text-gray-900 focus:border-primary focus:bg-white focus:outline-none"
                    >
                    <button
                        type="submit"
                        data-ai-submit
                        class="btn-primary rounded-xl px-4 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-50"
                    >
                        إرسال
                    </button>
                </form>
            </section>
        </div>
    @endif

    <script>
        (function () {
            const root = document.getElementById(@json($widgetId));
            if (!root || root.dataset.bound === '1') return;
            root.dataset.bound = '1';

            const endpoint = @json($assistEndpoint);
            const cartAddUrl = @json($cartAddEndpoint);
            const csrfToken = @json(csrf_token());
            const isCustomerMode = @json($mode === 'customer');

            const toggleBtn = root.querySelector('[data-ai-toggle]');
            const closeBtn = root.querySelector('[data-ai-close]');
            const panel = root.querySelector('[data-ai-panel]');
            const form = root.querySelector('[data-ai-form]');
            const input = root.querySelector('[data-ai-input]');
            const submitBtn = root.querySelector('[data-ai-submit]');
            const messagesBox = root.querySelector('[data-ai-messages]');
            const cardsBox = root.querySelector('[data-ai-cards]');
            const suggestedBar = root.querySelector('[data-ai-suggested-bar]');
            const intentBadge = root.querySelector('[data-ai-intent-badge]');
            const budgetInput = root.querySelector('[data-ai-budget]');
            const partyInput = root.querySelector('[data-ai-party]');
            const gpsBtn = root.querySelector('[data-ai-share-gps]');
            const gpsLabel = root.querySelector('[data-ai-gps-label]');
            const confirmBox = root.querySelector('[data-ai-confirm-box]');
            const confirmLabel = root.querySelector('[data-ai-confirm-label]');
            const confirmBtn = root.querySelector('[data-ai-confirm-btn]');
            const cancelBtn = root.querySelector('[data-ai-cancel-btn]');

            let history = [];
            let userCoords = null;
            let pendingAdminAction = null;

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>'"]/g, ch => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;'
                }[ch]));
            }

            if (toggleBtn && panel) {
                toggleBtn.addEventListener('click', () => {
                    const isHidden = panel.classList.contains('hidden');
                    if (isHidden) {
                        panel.classList.remove('hidden');
                        panel.classList.add('flex');
                        toggleBtn.setAttribute('aria-expanded', 'true');
                        if (input) input.focus();
                    } else {
                        panel.classList.add('hidden');
                        panel.classList.remove('flex');
                        toggleBtn.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            if (closeBtn && panel && toggleBtn) {
                closeBtn.addEventListener('click', () => {
                    panel.classList.add('hidden');
                    panel.classList.remove('flex');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                });
            }

            function appendMessage(role, text) {
                if (!messagesBox) return;
                const wrapper = document.createElement('div');
                if (role === 'user') {
                    wrapper.className = 'mr-auto max-w-[88%] rounded-xl bg-slate-900 px-3.5 py-2.5 text-xs font-bold text-white';
                } else {
                    wrapper.className = 'ml-auto max-w-[95%] rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-xs leading-relaxed text-slate-800 shadow-xs';
                }
                wrapper.textContent = text;
                messagesBox.appendChild(wrapper);
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }

            function renderActionCards(cards) {
                if (!cardsBox) return;
                if (!Array.isArray(cards) || cards.length === 0) {
                    cardsBox.innerHTML = '';
                    return;
                }

                cardsBox.innerHTML = cards.map(card => {
                    if (card.type === 'product' && card.product_id) {
                        return `
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-gray-200 bg-white p-2.5 shadow-2xs">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-black text-gray-900">${escapeHtml(card.title)}</p>
                                    <p class="truncate text-[11px] text-gray-500">${escapeHtml(card.subtitle || '')}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-xs font-black text-primary">${escapeHtml(card.metric)}</span>
                                    <button type="button" data-ai-add-product="${Number(card.product_id)}" class="rounded-lg bg-gray-900 px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-primary">
                                        ${escapeHtml(card.cta_label || 'أضف للسلة')}
                                    </button>
                                </div>
                            </div>
                        `;
                    }

                    if (card.type === 'coupon' && card.code) {
                        return `
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-dashed border-orange-300 bg-orange-50/70 p-2.5">
                                <div>
                                    <p class="text-xs font-black text-orange-950">${escapeHtml(card.title)} (${escapeHtml(card.metric)})</p>
                                    <p class="text-[11px] text-orange-800">${escapeHtml(card.subtitle || '')}</p>
                                </div>
                                <button type="button" data-ai-copy-code="${escapeHtml(card.code)}" class="rounded-lg bg-orange-600 px-2.5 py-1 text-[11px] font-bold text-white">
                                    نسخ الكود
                                </button>
                            </div>
                        `;
                    }

                    return `
                        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-2xs">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="text-xs font-bold text-slate-600">${escapeHtml(card.title)}</span>
                                <span class="text-sm font-black text-slate-950">${escapeHtml(card.metric)}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">${escapeHtml(card.subtitle || '')}</p>
                        </div>
                    `;
                }).join('');
            }

            function renderSuggestions(prompts) {
                if (!suggestedBar || !Array.isArray(prompts) || prompts.length === 0) return;
                suggestedBar.innerHTML = prompts.map(promptText => `
                    <button type="button" data-ai-quick-prompt="${escapeHtml(promptText)}" class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 transition hover:border-orange-400 hover:text-orange-600">
                        ${escapeHtml(promptText)}
                    </button>
                `).join('');
            }

            async function sendPrompt(promptText, extraPayload = {}) {
                const clean = String(promptText || '').trim();
                if (!clean) return;

                appendMessage('user', clean);
                if (input) input.value = '';
                if (submitBtn) submitBtn.disabled = true;

                const payload = {
                    prompt: clean,
                    history: history.slice(-6),
                    ...extraPayload,
                };

                if (budgetInput && budgetInput.value) {
                    payload.budget = Number(budgetInput.value);
                }
                if (partyInput && partyInput.value) {
                    payload.party_size = Number(partyInput.value);
                }
                if (userCoords) {
                    payload.latitude = userCoords.latitude;
                    payload.longitude = userCoords.longitude;
                }

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        appendMessage('assistant', data.message || 'تعذر تنفيذ الطلب حالياً.');
                        return;
                    }

                    const replyText = data.reply || 'تم تحليل طلبك بنجاح.';
                    appendMessage('assistant', replyText);

                    history.push({ role: 'user', text: clean });
                    history.push({ role: 'assistant', text: replyText });

                    if (intentBadge && data.intent) {
                        intentBadge.textContent = data.intent;
                    }

                    renderActionCards(data.action_cards || []);
                    renderSuggestions(data.suggested_prompts || []);

                    if (confirmBox && confirmLabel) {
                        if (data.requires_confirmation && data.pending_action) {
                            pendingAdminAction = data.pending_action;
                            confirmLabel.textContent = data.pending_action.label || 'تأكيد تنفيذ الإجراء التشغيلي';
                            confirmBox.classList.remove('hidden');
                        } else {
                            pendingAdminAction = null;
                            confirmBox.classList.add('hidden');
                        }
                    }
                } catch (err) {
                    appendMessage('assistant', 'تعذر الاتصال بالوكيل الذكي حالياً. يرجى التحقق من الشبكة والمحاولة مجدداً.');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            }

            if (form) {
                form.addEventListener('submit', e => {
                    e.preventDefault();
                    if (input && input.value.trim() !== '') {
                        sendPrompt(input.value);
                    }
                });
            }

            root.addEventListener('click', async e => {
                const quickBtn = e.target.closest('[data-ai-quick-prompt]');
                if (quickBtn) {
                    e.preventDefault();
                    sendPrompt(quickBtn.getAttribute('data-ai-quick-prompt'));
                    return;
                }

                const copyBtn = e.target.closest('[data-ai-copy-code]');
                if (copyBtn) {
                    const code = copyBtn.getAttribute('data-ai-copy-code');
                    if (code && navigator.clipboard) {
                        await navigator.clipboard.writeText(code);
                        copyBtn.textContent = 'تم النسخ ✓';
                        setTimeout(() => { copyBtn.textContent = 'نسخ الكود'; }, 2000);
                    }
                    return;
                }

                const addProductBtn = e.target.closest('[data-ai-add-product]');
                if (addProductBtn && cartAddUrl) {
                    const productId = Number(addProductBtn.getAttribute('data-ai-add-product'));
                    const originalText = addProductBtn.textContent;
                    addProductBtn.disabled = true;
                    addProductBtn.textContent = 'جاري الإضافة...';

                    try {
                        const formData = new FormData();
                        formData.append('_token', csrfToken);
                        formData.append('product_id', String(productId));
                        formData.append('quantity', '1');

                        const res = await fetch(cartAddUrl, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                        const result = await res.json();
                        if (result && result.success) {
                            addProductBtn.textContent = 'أُضيف للسلة ✓';
                            if (typeof window.updateMiniCart === 'function') {
                                window.updateMiniCart(result);
                            }
                            if (typeof window.showToast === 'function') {
                                window.showToast(result.message || 'تمت إضافة الوجبة للسلة بنجاح', 'success');
                            }
                        } else {
                            addProductBtn.textContent = 'تعذرت الإضافة';
                        }
                    } catch (error) {
                        addProductBtn.textContent = 'خطأ اتصال';
                    } finally {
                        setTimeout(() => {
                            addProductBtn.disabled = false;
                            addProductBtn.textContent = originalText;
                        }, 1800);
                    }
                }
            });

            if (gpsBtn && navigator.geolocation) {
                gpsBtn.addEventListener('click', () => {
                    if (gpsLabel) gpsLabel.textContent = 'جاري تحديد موقعك...';
                    navigator.geolocation.getCurrentPosition(
                        pos => {
                            userCoords = {
                                latitude: Number(pos.coords.latitude.toFixed(6)),
                                longitude: Number(pos.coords.longitude.toFixed(6)),
                            };
                            if (gpsLabel) gpsLabel.textContent = 'تم تحديد الموقع ✓';
                            sendPrompt('كم تكلفة ووقت التوصيل لموقعي الحالي وما هي شركات التوصيل المؤهلة؟');
                        },
                        () => {
                            if (gpsLabel) gpsLabel.textContent = 'تعذر تحديد الموقع';
                        },
                        { enableHighAccuracy: true, timeout: 7000 }
                    );
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (!pendingAdminAction) return;
                    sendPrompt('تأكيد تنفيذ الإجراء: ' + (pendingAdminAction.label || ''), {
                        execute_action: pendingAdminAction.action_type,
                        action_payload: pendingAdminAction.payload || {},
                    });
                });
            }

            if (cancelBtn && confirmBox) {
                cancelBtn.addEventListener('click', () => {
                    pendingAdminAction = null;
                    confirmBox.classList.add('hidden');
                });
            }
        })();
    </script>
@endif
