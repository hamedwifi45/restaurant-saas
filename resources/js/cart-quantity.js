function registerCartQuantityManager() {
    Alpine.data('cartQuantityManager', (configuration) => ({
        ...configuration,
        quantities: Object.fromEntries(
            Object.entries(configuration.items).map(([productId, item]) => [productId, Number(item.quantity)]),
        ),
        confirmedQuantities: {},
        pendingUpdates: {},
        isSaving: false,
        errorMessage: '',
        syncTimer: null,

        init() {
            this.confirmedQuantities = { ...this.quantities };
        },

        get subtotal() {
            return Object.entries(this.quantities).reduce(
                (total, [productId, quantity]) => total + (Number(this.items[productId].price) * quantity),
                0,
            );
        },

        get taxAmount() {
            return this.subtotal * (Number(this.taxPercentage) / 100);
        },

        get grandTotal() {
            return this.subtotal + this.taxAmount + Number(this.deliveryFee);
        },

        formatMoney(amount) {
            return Number(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        changeQuantity(productId, difference) {
            const key = String(productId);

            this.quantities[key] = Math.max(1, Number(this.quantities[key]) + difference);
            this.pendingUpdates[key] = this.quantities[key];
            this.errorMessage = '';

            window.clearTimeout(this.syncTimer);
            this.syncTimer = window.setTimeout(() => this.syncPendingUpdates(), 500);
        },

        async syncPendingUpdates() {
            if (this.isSaving || Object.keys(this.pendingUpdates).length === 0) {
                return;
            }

                const updates = Object.entries(this.pendingUpdates).map(([cartKey, quantity]) => ({
                    cart_key: cartKey,
                quantity,
            }));
            this.pendingUpdates = {};
            this.isSaving = true;

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ items: updates }),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'تعذر تحديث السلة. حاول مرة أخرى.');
                }

                for (const item of data.items) {
                    const key = String(item.cart_key);
                    this.confirmedQuantities[key] = Number(item.quantity);

                    if (!(key in this.pendingUpdates)) {
                        this.quantities[key] = Number(item.quantity);
                    }
                }

                this.errorMessage = '';
            } catch (error) {
                for (const item of updates) {
                    const key = String(item.cart_key);

                    if (!(key in this.pendingUpdates)) {
                        this.quantities[key] = this.confirmedQuantities[key];
                    }
                }

                this.errorMessage = error.message || 'تعذر تحديث السلة. حاول مرة أخرى.';
            } finally {
                this.isSaving = false;

                if (Object.keys(this.pendingUpdates).length > 0) {
                    this.syncTimer = window.setTimeout(() => this.syncPendingUpdates(), 500);
                }
            }
        },
    }));
}

if (window.Alpine) {
    registerCartQuantityManager();
} else {
    document.addEventListener('alpine:init', registerCartQuantityManager);
}
