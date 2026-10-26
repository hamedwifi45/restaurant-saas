<div
    dir="rtl"
    class="min-h-screen overflow-x-hidden bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"
    x-data="localPosOrders(@js($restaurant->slug), @js(config('local_operations.enabled')), {
        syncEndpoint: @js(route('local.orders.store', $restaurant->slug)),
        signalPublishEndpoint: @js(route('lan.signal.publish', $restaurant->slug)),
        signalPollEndpoint: @js(route('lan.signal.poll', $restaurant->slug)),
        csrfToken: @js(csrf_token())
    })"
    x-on:local-order-accepted.window="clearAcceptedOrder($event.detail.uuid)"
    x-on:keydown.window="
        if ($event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.target.isContentEditable) {
            $event.preventDefault();
            $refs.productSearch.focus();
        }
    "
>
    <style>
        .pos-product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pos-product-card h2,
        .pos-product-card .line-clamp-2,
        .pos-cart-item h3,
        .pos-cart-item .line-price,
        .pos-cart-item strong {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        @media (min-width: 640px) {
            .pos-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .pos-product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1536px) {
            .pos-product-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 430px) {
            .pos-product-grid {
                grid-template-columns: 1fr;
            }

            .pos-product-card {
                min-height: 0;
            }

            .pos-product-card h2 {
                font-size: 0.875rem;
            }

            .pos-product-card .aspect-[4/3] {
                aspect-ratio: 16 / 10;
            }

            .pos-compact-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="mx-auto flex w-full max-w-none flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                @if($restaurant->logo)
                    <img src="{{ media_url($restaurant->logo) }}" alt="" class="size-11 rounded-xl object-cover">
                @else
                    <span class="grid size-11 place-items-center rounded-xl bg-orange-100 text-lg font-black text-orange-700 dark:bg-orange-950 dark:text-orange-300">POS</span>
                @endif
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-bold">{{ $restaurant->name }}</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">نقطة البيع · {{ auth()->user()->name }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                <button
                    type="button"
                    x-on:click="showLanPairingModal = true"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold transition"
                    :class="lanConnectionState.includes('connected')
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
                        : 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-200'"
                >
                    <span class="size-2 rounded-full" :class="lanConnectionState.includes('connected') ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span x-text="lanConnectionState === 'connected_webrtc_lan' ? 'متصل بشاشة المطبخ (LAN P2P)' : (lanConnectionState === 'connected_ws_lan' ? 'متصل عبر وسيط LAN' : 'إقران شاشة المطبخ (LAN Offline)')">إقران شاشة المطبخ (LAN)</span>
                </button>
                <span class="hidden rounded-full bg-zinc-100 px-3 py-1.5 sm:inline-flex dark:bg-zinc-800">جلسة الكاشير</span>
                <span>{{ now()->format('Y-m-d H:i') }}</span>
                <a href="{{ route('restaurant.home', $restaurant->slug) }}" class="rounded-lg border border-zinc-300 px-3 py-2 font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">عرض المنيو</a>
            </div>
        </div>
    </header>

    <div
        x-cloak
        x-show="showLanPairingModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="إقران الكاشير بشاشة المطبخ عبر الشبكة المحلية"
    >
        <div class="w-full max-w-lg space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                <h2 class="text-base font-extrabold">ربط جهاز الكاشير بجهاز المطبخ (KDS) عبر الشبكة المحلية (LAN)</h2>
                <button type="button" x-on:click="showLanPairingModal = false" class="rounded-lg px-2 py-1 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">✕</button>
            </div>

            <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">
                يتصل جهاز الكاشير بجهاز شاشة المطبخ تلقائياً عبر قناة WebRTC P2P مباشرة على الشبكة المحلية (Wi-Fi / LAN) ويستمر بالعمل حتى لو انقطع الإنترنت الخارجي تماماً. في حال بدء التشغيل أثناء انقطاع الإنترنت، يمكنك الإقران اليدوي المباشر أدناه دون أي خادم:
            </p>

            <div class="space-y-2 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold">1. إنشاء رمز عرض الاتصال (SDP Offer) لشاشة المطبخ:</span>
                    <button type="button" x-on:click="generateOfflinePairingOffer()" class="rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-orange-700">توليد رمز الإقران</button>
                </div>
                <textarea
                    x-show="manualOfferCode"
                    x-model="manualOfferCode"
                    readonly
                    rows="3"
                    class="w-full rounded-lg border border-zinc-300 bg-white p-2 font-mono text-[11px] dark:border-zinc-700 dark:bg-zinc-900"
                    placeholder="سيظهر رمز الإقران هنا..."
                ></textarea>
            </div>

            <div class="space-y-2 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950">
                <label class="block text-xs font-bold">2. الصق رمز استجابة شاشة المطبخ (SDP Answer):</label>
                <textarea
                    x-model="manualAnswerInput"
                    rows="3"
                    class="w-full rounded-lg border border-zinc-300 bg-white p-2 font-mono text-[11px] dark:border-zinc-700 dark:bg-zinc-900"
                    placeholder="الصق الرمز الناتج من شاشة المطبخ هنا..."
                ></textarea>
                <button type="button" x-on:click="confirmOfflinePairingAnswer()" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">تأكيد الربط المباشر بين الجهازين</button>
            </div>

            <div class="space-y-2 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                <label class="block text-xs font-bold">خيار إضافي: عنوان وسيط WebSocket محلي على الشبكة (اختياري)</label>
                <div class="flex gap-2">
                    <input
                        type="url"
                        x-model="lanWsRelayInput"
                        placeholder="ws://192.168.1.100:6001"
                        class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-950"
                    >
                    <button type="button" x-on:click="saveLanRelayUrl()" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-bold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <p
        x-cloak
        x-show="lastKdsAckMessage"
        x-text="lastKdsAckMessage"
        role="status"
        class="mx-auto mt-3 w-full max-w-none rounded-lg bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
    ></p>

    <p
    x-cloak
    x-show="submissionError"
    x-text="submissionError"
    role="alert"
    class="mx-auto mt-3 w-full max-w-none rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-800 dark:bg-red-950/40 dark:text-red-200"
    ></p>

    <main class="mx-auto grid w-full max-w-none gap-4 p-3 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)]">
        <section class="min-w-0 space-y-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 md:flex-row">
                    <label class="relative min-w-0 flex-1">
                        <span class="sr-only">البحث عن صنف أو تصنيف</span>
                        <input
                            x-ref="productSearch"
                            type="search"
                            wire:model.live.debounce.250ms="search"
                            placeholder="ابحث عن صنف أو تصنيف…"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 pe-11 text-base outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:border-zinc-700 dark:bg-zinc-950"
                        >
                        <span aria-hidden="true" class="pointer-events-none absolute end-4 top-3.5 text-zinc-400">⌕</span>
                    </label>
                    <span class="self-center rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800">اضغط / للبحث</span>
                </div>

                <nav aria-label="تصنيفات المنتجات" class="mt-4 flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        wire:click="$set('selectedCategory', '')"
                        aria-pressed="{{ $selectedCategory === '' ? 'true' : 'false' }}"
                        class="shrink-0 rounded-full px-4 py-2 text-sm font-bold {{ $selectedCategory === '' ? 'bg-orange-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' }}"
                    >
                        الكل
                    </button>
                    @foreach($categories as $category)
                        <button
                            type="button"
                            wire:key="pos-category-{{ $category->id }}"
                            wire:click="$set('selectedCategory', '{{ $category->id }}')"
                            aria-pressed="{{ $selectedCategory === (string) $category->id ? 'true' : 'false' }}"
                            class="shrink-0 rounded-full px-4 py-2 text-sm font-bold {{ $selectedCategory === (string) $category->id ? 'bg-orange-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <section
                x-data="posQuickOrders(@js($restaurant->slug))"
                class="rounded-2xl border border-orange-200 bg-orange-50/70 p-4 dark:border-orange-900 dark:bg-orange-950/20"
                aria-labelledby="quick-orders-title"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="quick-orders-title" class="font-extrabold">الطلبات السريعة</h2>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">تُضاف الأصناف إلى السلة فقط، ولا يتم تسجيل الطلب تلقائياً.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="saveFromCart($wire.cart)"
                        x-bind:disabled="showNameEditor"
                        class="min-h-10 rounded-lg border border-orange-300 bg-white px-3 py-2 text-sm font-bold text-orange-800 hover:bg-orange-100 dark:border-orange-800 dark:bg-zinc-900 dark:text-orange-200 dark:hover:bg-orange-950"
                    >حفظ السلة كطلب سريع</button>
                </div>

                <form
                    x-cloak
                    x-show="showNameEditor"
                    x-on:submit.prevent="saveName()"
                    class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-orange-200 bg-white p-3 dark:border-orange-900 dark:bg-zinc-900"
                >
                    <label for="quick-order-name" class="text-sm font-semibold" x-text="editingPresetId ? 'إعادة تسمية الطلب السريع' : 'اسم الطلب السريع'"></label>
                    <input
                        id="quick-order-name"
                        x-model="nameInput"
                        type="text"
                        maxlength="80"
                        required
                        autocomplete="off"
                        class="min-w-48 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                    >
                    <button type="submit" class="min-h-10 rounded-lg bg-orange-600 px-4 py-2 text-sm font-bold text-white hover:bg-orange-700">حفظ</button>
                    <button type="button" x-on:click="cancelNameEditor()" class="min-h-10 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">إلغاء</button>
                </form>

                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="preset in presets" :key="preset.id">
                        <div class="flex min-h-12 items-stretch overflow-hidden rounded-xl border border-orange-200 bg-white shadow-sm dark:border-orange-900 dark:bg-zinc-900">
                            <button
                                type="button"
                                x-on:click="apply(preset)"
                                x-bind:aria-label="`إضافة الطلب السريع ${preset.name} إلى السلة`"
                                class="min-w-36 px-4 text-sm font-extrabold text-zinc-900 hover:bg-orange-100 dark:text-zinc-100 dark:hover:bg-orange-950/50"
                            >
                                <span x-text="preset.name"></span>
                                <span class="ms-1 text-xs font-medium text-zinc-500" x-text="`(${preset.items.length})`"></span>
                            </button>
                            <button type="button" x-on:click.stop="startRename(preset)" class="border-s border-zinc-200 px-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="إعادة تسمية الطلب السريع">تسمية</button>
                            <button type="button" x-on:click.stop="remove(preset)" class="border-s border-zinc-200 px-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-zinc-700 dark:hover:bg-red-950/30" aria-label="حذف الطلب السريع">حذف</button>
                        </div>
                    </template>
                    <p x-cloak x-show="presets.length === 0" class="self-center text-sm text-zinc-500">احفظ مجموعة أصناف لتظهر هنا.</p>
                </div>
                <p x-cloak x-show="message" x-text="message" role="status" class="mt-2 text-xs font-semibold text-amber-800 dark:text-amber-200"></p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">محفوظة على هذا المتصفح والجهاز فقط.</p>
            </section>

            @if($products->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <p class="text-lg font-bold">لا توجد أصناف مطابقة</p>
                        <p class="mt-1 text-sm text-zinc-500">غيّر البحث أو اختر تصنيفاً آخر.</p>
                    </div>
                </div>
            @else
                <div class="pos-product-grid grid gap-3">
                    @foreach($products as $product)
                        <article wire:key="pos-product-{{ $product->id }}" class="pos-product-card flex min-h-48 flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="relative aspect-[4/3] bg-zinc-100 dark:bg-zinc-800">
                                @if($product->image)
                                    <img src="{{ media_url($product->image) }}" alt="" loading="lazy" class="size-full object-cover">
                                @else
                                    <div class="grid size-full place-items-center text-3xl text-zinc-300 dark:text-zinc-600">🍽</div>
                                @endif
                                <span class="absolute bottom-2 right-2 rounded-full bg-black/65 px-2.5 py-1 text-xs font-semibold text-white">
                                    {{ $product->category?->name }}
                                </span>
                            </div>
                            <div class="flex flex-1 flex-col gap-2 p-3">
                                <div class="flex-1">
                                    <h2 class="line-clamp-2 font-bold">{{ $product->name }}</h2>
                                    @if($product->price_type === 'open_price')
                                        <p class="mt-1 text-xs text-zinc-500">
                                            {{ number_format((float) $product->min_price, 2) }} – {{ $product->max_price ? number_format((float) $product->max_price, 2) : '∞' }} {{ $restaurant->currency_symbol }}
                                        </p>
                                    @elseif($product->price_type === 'variant')
                                        <p class="mt-1 text-xs text-zinc-500">اختر الحجم أو الوزن</p>
                                    @else
                                        <p class="mt-1 font-bold text-orange-700 dark:text-orange-300">
                                            {{ number_format((float) $product->price, 2) }} {{ $restaurant->currency_symbol }}
                                        </p>
                                    @endif
                                </div>

                                @if($product->price_type === 'variant')
                                    <div class="grid gap-1.5">
                                        @foreach(is_array($product->variants) ? $product->variants : [] as $variantIndex => $variant)
                                            @if(is_array($variant) && isset($variant['name'], $variant['price']))
                                                <button
                                                    type="button"
                                                    wire:click="addProduct({{ $product->id }}, {{ $variantIndex }})"
                                                    wire:loading.attr="disabled"
                                                    class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 px-2.5 py-2 text-start text-xs font-semibold hover:border-orange-400 hover:bg-orange-50 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-orange-950/30"
                                                >
                                                    <span>{{ $variant['name'] }}</span>
                                                    <span class="whitespace-nowrap">{{ number_format((float) $variant['price'], 2) }}</span>
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @elseif($product->price_type === 'open_price')
                                    <div class="flex gap-2">
                                        <input
                                            type="number"
                                            min="{{ $product->min_price ?? 0 }}"
                                            @if($product->max_price) max="{{ $product->max_price }}" @endif
                                            step="0.01"
                                            inputmode="decimal"
                                            wire:model="openPrices.{{ $product->id }}"
                                            aria-label="السعر لصنف {{ $product->name }}"
                                            placeholder="المبلغ"
                                            class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-2 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                                        >
                                        <button
                                            type="button"
                                            wire:click="addProduct({{ $product->id }})"
                                            wire:loading.attr="disabled"
                                            class="rounded-lg bg-orange-600 px-3 py-2 font-bold text-white hover:bg-orange-700 disabled:opacity-50"
                                            aria-label="إضافة {{ $product->name }}"
                                        >+</button>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        wire:click="addProduct({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex min-h-10 items-center justify-center gap-2 rounded-xl bg-orange-600 px-3 py-2 font-bold text-white hover:bg-orange-700 disabled:opacity-50"
                                    >
                                        <span aria-hidden="true">+</span>
                                        <span>إضافة</span>
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="h-fit min-w-0 rounded-2xl border border-zinc-200 bg-white shadow-sm lg:sticky lg:top-24 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-lg font-extrabold">الطلب الحالي</h2>
                    <p class="text-xs text-zinc-500">مسودة جلسة الكاشير</p>
                </div>
                <button type="button" wire:click="clearCart" wire:confirm="هل تريد حذف جميع أصناف الطلب الحالي؟" class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">
                    تفريغ
                </button>
            </div>

            @if($statusMessage !== '')
                <p role="{{ $statusTone === 'warning' ? 'alert' : 'status' }}" class="mx-4 mt-3 rounded-lg p-3 text-sm font-semibold {{ $statusTone === 'warning' ? 'bg-amber-50 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200' : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' }}">{{ $statusMessage }}</p>
            @endif
            @error('cart') <p role="alert" class="mx-4 mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200">{{ $message }}</p> @enderror

            <details class="mx-4 mt-3 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <summary class="cursor-pointer px-3 py-2.5 text-sm font-bold">المسودات · استعادة مسودة ({{ count($drafts) }})</summary>
                <div class="space-y-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                    @forelse($drafts as $draftId => $draft)
                        <div wire:key="pos-draft-{{ $draftId }}" class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $draft['name'] ?? 'مسودة' }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ count($draft['data']['cart'] ?? []) }} أصناف
                                    @if(! empty($draft['saved_at']))
                                        · {{ \Illuminate\Support\Carbon::parse($draft['saved_at'])->format('Y-m-d H:i') }}
                                    @endif
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="restoreDraft('{{ $draftId }}')"
                                wire:confirm="استعادة هذه المسودة؟ سيتم استبدال السلة الحالية."
                                class="shrink-0 rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white hover:bg-orange-700"
                            >استعادة</button>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">لا توجد مسودات محفوظة.</p>
                    @endforelse
                </div>
            </details>

            <div class="max-h-[32vh] space-y-3 overflow-y-auto p-4 lg:max-h-[38vh]">
                @forelse($quote['lines'] as $line)
                    <article wire:key="pos-cart-{{ $line['key'] }}" class="pos-cart-item rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-bold">{{ $line['product_name'] }}</h3>
                                @if($line['variant_name'])
                                    <p class="text-xs text-zinc-500">{{ $line['variant_name'] }}</p>
                                @endif
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ number_format((float) $line['unit_price'], 2) }} × {{ $line['quantity'] }}
                                </p>
                            </div>
                            <strong class="whitespace-nowrap text-sm">{{ number_format((float) $line['total'], 2) }} {{ $restaurant->currency_symbol }}</strong>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div class="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <button type="button" wire:click="updateQuantity('{{ $line['key'] }}', {{ $line['quantity'] - 1 }})" aria-label="تقليل الكمية" class="grid size-9 place-items-center font-bold hover:bg-zinc-100 dark:hover:bg-zinc-800">−</button>
                                <span class="min-w-9 text-center text-sm font-bold">{{ $line['quantity'] }}</span>
                                <button type="button" wire:click="updateQuantity('{{ $line['key'] }}', {{ $line['quantity'] + 1 }})" aria-label="زيادة الكمية" class="grid size-9 place-items-center font-bold hover:bg-zinc-100 dark:hover:bg-zinc-800">+</button>
                            </div>
                            <button type="button" wire:click="removeProduct('{{ $line['key'] }}')" class="text-xs font-semibold text-red-600 hover:underline">إزالة</button>
                        </div>
                        <input
                            type="text"
                            maxlength="1000"
                            wire:model.blur="cart.{{ $line['key'] }}.notes"
                            placeholder="ملاحظة للصنف (اختياري)"
                            class="mt-3 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"
                        >
                    </article>
                @empty
                    <div class="grid min-h-28 place-items-center rounded-xl border border-dashed border-zinc-300 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        اختر أصنافاً من القائمة لبدء الطلب.
                    </div>
                @endforelse
            </div>

            <div class="space-y-4 border-t border-zinc-200 p-4 dark:border-zinc-800">
                <fieldset>
                    <legend class="mb-2 text-sm font-bold">نوع الطلب</legend>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($availableOrderTypes as $type => $label)
                            <button
                                type="button"
                                wire:click="selectOrderType('{{ $type }}')"
                                aria-pressed="{{ $orderType === $type ? 'true' : 'false' }}"
                                class="rounded-xl border px-2 py-2.5 text-sm font-bold {{ $orderType === $type ? 'border-orange-600 bg-orange-50 text-orange-800 dark:bg-orange-950/40 dark:text-orange-200' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                </fieldset>

                @if($orderType === 'dine_in')
                    <label class="block text-sm font-semibold">
                        الطاولة
                        <select wire:model="tableId" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">اختر طاولة متاحة</option>
                            @foreach($tables as $table)
                                <option value="{{ $table->id }}">{{ $table->name ?: 'طاولة '.$table->number }} · {{ $table->seats }} مقاعد</option>
                            @endforeach
                        </select>
                        @error('tableId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                @endif

                <div class="pos-compact-form grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="text-sm font-semibold">
                        اسم العميل
                        <input wire:model.blur="customerName" type="text" maxlength="255" autocomplete="name" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('customer_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">
                        رقم الهاتف
                        <input wire:model.blur="customerPhone" type="tel" maxlength="20" autocomplete="tel" inputmode="tel" required pattern="\s*(09[0-9]{8}|\+?[1-9][0-9]{7,14})\s*" title="أدخل رقماً محلياً مثل 09xxxxxxxx أو دولياً مثل +963930123456" placeholder="09xxxxxxxx أو +963930123456" aria-describedby="customer-phone-error" aria-invalid="@error('customer_phone') true @else false @enderror" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('customer_phone') <span id="customer-phone-error" role="alert" class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                @if($orderType === 'delivery')
                    <label class="block text-sm font-semibold">
                        عنوان التوصيل
                        <textarea wire:model.blur="address" rows="2" maxlength="1000" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                        @error('address') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm font-semibold">
                            خط العرض
                            <input wire:model.blur="latitude" type="number" step="any" min="-90" max="90" inputmode="decimal" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                        <label class="text-sm font-semibold">
                            خط الطول
                            <input wire:model.blur="longitude" type="number" step="any" min="-180" max="180" inputmode="decimal" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    </div>
                    @if($quote['delivery_error'])
                        <p role="alert" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-950/50 dark:text-amber-200">{{ $quote['delivery_error'] }}</p>
                    @endif
                    @error('latitude') <p role="alert" class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endif

                <label class="block text-sm font-semibold">
                    ملاحظات الطلب
                    <textarea wire:model.blur="orderNotes" rows="2" maxlength="1000" class="mt-1.5 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                    @error('notes') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <dl class="space-y-2 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-800">
                    <div class="flex justify-between"><dt class="text-zinc-500">المجموع الفرعي</dt><dd>{{ number_format((float) $quote['subtotal'], 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                    @if((float) $quote['discount'] > 0)
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300"><dt>عرض تلقائي</dt><dd>− {{ number_format((float) $quote['discount'], 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-zinc-500">الضريبة ({{ number_format((float) $restaurant->tax_percentage, 2) }}%)</dt><dd>{{ number_format((float) $quote['tax'], 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                    @if($orderType === 'delivery')
                        <div class="flex justify-between"><dt class="text-zinc-500">رسوم التوصيل</dt><dd>{{ number_format((float) $quote['delivery_fee'], 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-zinc-200 pt-3 text-lg font-extrabold dark:border-zinc-800"><dt>الإجمالي</dt><dd>{{ number_format((float) $quote['total'], 2) }} {{ $restaurant->currency_symbol }}</dd></div>
                </dl>

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <input wire:model.live="cashReceived" type="checkbox" class="mt-1 size-4 accent-orange-600">
                    <span>
                        <span class="block text-sm font-bold">تم استلام المبلغ نقداً بالكامل</span>
                        <span class="block text-xs text-zinc-500">بدون تحديد، يُسجل الطلب غير مدفوع ومستحقاً نقداً.</span>
                    </span>
                </label>

                @error('checkout') <p role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200">{{ $message }}</p> @enderror
                @error('table_id') <p role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200">{{ $message }}</p> @enderror

                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-2">
                        <input
                            type="text"
                            wire:model.blur="draftName"
                            maxlength="80"
                            placeholder="اسم المسودة (اختياري)"
                            aria-label="اسم المسودة"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"
                        >
                        <button type="button" wire:click="saveDraft" class="min-h-11 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm font-bold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">حفظ المسودة</button>
                    </div>
                    <button
                        type="button"
                        x-on:click="if (window.confirm('تأكيد تسجيل الطلب بالإجمالي المعروض؟ لا يمكن تعديل الأصناف بعد الإرسال.')) submit($wire, @js([
                            'delivery_type' => $orderType,
                            'customer_name' => $customerName ?: ($orderType === 'dine_in' ? 'طلب طاولة' : 'عميل نقطة البيع'),
                            'customer_phone' => $customerPhone ?: '0900000000',
                            'delivery_address' => $deliveryAddress,
                            'notes' => $notes,
                            'total_amount' => (float) ($quote['total'] ?? 0),
                            'items' => array_values(array_map(fn ($item) => [
                                'product_id' => $item['product_id'],
                                'name' => $item['name'],
                                'quantity' => $item['quantity'],
                                'unit_price' => $item['unit_price'],
                                'variant_name' => $item['variant_name'] ?? null,
                                'notes' => $item['notes'] ?? null,
                            ], $cart)),
                        ]))"
                        wire:loading.attr="disabled"
                        wire:target="placeOrder"
                        @disabled($cart === [])
                        class="rounded-xl bg-orange-600 px-3 py-3 text-sm font-extrabold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="placeOrder">تسجيل الطلب</span>
                        <span wire:loading wire:target="placeOrder">جارٍ التسجيل…</span>
                    </button>
                </div>
            </div>
        </aside>
    </main>
</div>
