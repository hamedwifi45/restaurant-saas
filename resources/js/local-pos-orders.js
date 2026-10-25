document.addEventListener('alpine:init', () => {
    Alpine.data('localPosOrders', (restaurantSlug, localOperationsEnabled) => ({
        storageKey: `restaurant-saas:local-pos:order-uuid:${encodeURIComponent(restaurantSlug)}`,
        localOperationsEnabled,
        clientOrderUuid: null,
        submissionError: '',

        init() {
            if (!this.localOperationsEnabled) {
                return;
            }

            try {
                const storedUuid = localStorage.getItem(this.storageKey);
                this.clientOrderUuid = this.isUuid(storedUuid) ? storedUuid : null;
            } catch (error) {
                console.error('Local order retry storage is unavailable.', error);
            }
        },

        isUuid(value) {
            return typeof value === 'string'
                && /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
        },

        async submit(wire) {
            if (!this.localOperationsEnabled) {
                await wire.placeOrder();
                return;
            }

            this.submissionError = '';

            try {
                if (!this.clientOrderUuid) {
                    if (!globalThis.crypto?.randomUUID) {
                        throw new Error('This browser cannot create a secure order identifier.');
                    }

                    const uuid = globalThis.crypto.randomUUID();
                    localStorage.setItem(this.storageKey, uuid);
                    this.clientOrderUuid = uuid;
                }

                await wire.placeOrder(this.clientOrderUuid);
            } catch (error) {
                console.error('Local POS order submission failed; retrying will reuse its identifier.', error);
                this.submissionError = 'تعذر تأكيد استلام الطلب. أعد المحاولة دون تغيير السلة؛ سيُستخدم معرّف المحاولة نفسه لمنع التكرار.';
            }
        },

        clearAcceptedOrder(uuid) {
            if (uuid !== this.clientOrderUuid) {
                return;
            }

            try {
                localStorage.removeItem(this.storageKey);
                this.clientOrderUuid = null;
            } catch (error) {
                console.error('Could not clear the accepted local order identifier.', error);
            }
        },
    }));
});
