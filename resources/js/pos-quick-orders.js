document.addEventListener('alpine:init', () => {
    Alpine.data('posQuickOrders', (restaurantSlug) => ({
        storageKey: `restaurant-saas:pos:quick-orders:${encodeURIComponent(restaurantSlug)}`,
        presets: [],
        message: '',
        showNameEditor: false,
        editingPresetId: null,
        nameInput: '',
        pendingItems: [],

        init() {
            try {
                const stored = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                if (!Array.isArray(stored)) {
                    this.message = 'تعذر قراءة الطلبات السريعة القديمة؛ يمكنك إنشاء طلبات جديدة.';
                    return;
                }

                this.presets = stored
                    .filter((preset) => this.isValidPreset(preset))
                    .map((preset) => ({
                        id: preset.id,
                        name: preset.name,
                        items: preset.items.map((item) => ({
                            product_id: item.product_id,
                            quantity: item.quantity,
                            variant_index: item.variant_index ?? null,
                        })),
                    }));

                if (this.presets.length !== stored.length) {
                    this.message = 'تم تجاهل بعض بيانات الطلبات السريعة غير الصالحة.';
                    this.persist();
                }
            } catch {
                this.presets = [];
                this.message = 'تعذر قراءة الطلبات السريعة من هذا المتصفح.';
            }
        },

        isValidPreset(preset) {
            return preset
                && typeof preset.id === 'string'
                && preset.id.trim() !== ''
                && typeof preset.name === 'string'
                && preset.name.trim().length > 0
                && preset.name.length <= 80
                && Array.isArray(preset.items)
                && preset.items.length > 0
                && preset.items.length <= 100
                && preset.items.every((item) => item
                    && typeof item === 'object'
                    && Number.isInteger(Number(item.product_id))
                    && Number(item.product_id) > 0
                    && Number.isInteger(Number(item.quantity))
                    && Number(item.quantity) >= 1
                    && Number(item.quantity) <= 999
                    && (item.variant_index == null
                        || (Number.isInteger(Number(item.variant_index)) && Number(item.variant_index) >= 0)));
        },

        persist() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.presets));
                return true;
            } catch {
                this.message = 'تعذر حفظ الطلبات السريعة في هذا المتصفح.';
                return false;
            }
        },

        saveFromCart(cart) {
            const entries = Object.values(cart || {});
            const items = entries.map((line) => ({
                product_id: Number(line.product_id),
                quantity: Number(line.quantity),
                variant_index: line.variant_index ?? null,
            }));

            if (items.length === 0 || !items.every((item) => Number.isInteger(item.product_id)
                && item.product_id > 0
                && Number.isInteger(item.quantity)
                && item.quantity > 0
                && item.quantity <= 999)) {
                this.message = 'أضف أصنافاً صالحة إلى السلة قبل حفظ طلب سريع.';
                return;
            }

            this.pendingItems = items;
            this.editingPresetId = null;
            this.nameInput = '';
            this.showNameEditor = true;
            this.message = '';
        },

        startRename(preset) {
            this.pendingItems = [];
            this.editingPresetId = preset.id;
            this.nameInput = preset.name;
            this.showNameEditor = true;
            this.message = '';
        },

        saveName() {
            const trimmedName = this.nameInput.trim().slice(0, 80);
            if (trimmedName === '') {
                this.message = 'أدخل اسماً للطلب السريع.';
                return;
            }

            if (this.editingPresetId) {
                const preset = this.presets.find(({ id }) => id === this.editingPresetId);
                if (!preset) {
                    this.message = 'لم يعد هذا الطلب السريع متاحاً.';
                    this.cancelNameEditor();
                    return;
                }

                preset.name = trimmedName;
            } else {
                this.presets.push({
                    id: globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    name: trimmedName,
                    items: this.pendingItems,
                });
            }

            if (this.persist()) {
                this.message = '';
                this.cancelNameEditor();
            }
        },

        async apply(preset) {
            this.message = '';
            try {
                await this.$wire.applyQuickOrder(preset.items);
            } catch {
                this.message = 'تعذر تطبيق الطلب السريع. تحقق من الاتصال ثم أعد المحاولة.';
            }
        },

        cancelNameEditor() {
            this.showNameEditor = false;
            this.editingPresetId = null;
            this.pendingItems = [];
            this.nameInput = '';
        },

        remove(preset) {
            if (! window.confirm(`حذف الطلب السريع «${preset.name}»؟`)) {
                return;
            }

            this.presets = this.presets.filter((savedPreset) => savedPreset.id !== preset.id);
            this.message = this.persist() ? '' : this.message;
        },
    }));
});
