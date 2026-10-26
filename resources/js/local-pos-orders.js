import { LanPosKdsBridge } from './lan-pos-kds-bridge';

document.addEventListener('alpine:init', () => {
    Alpine.data('localPosOrders', (restaurantSlug, localOperationsEnabled, bridgeConfig = {}) => ({
        storageKey: `restaurant-saas:local-pos:order-uuid:${encodeURIComponent(restaurantSlug)}`,
        localOperationsEnabled,
        clientOrderUuid: null,
        submissionError: '',
        lanBridge: null,
        lanConnectionState: 'disconnected',
        showLanPairingModal: false,
        manualOfferCode: '',
        manualAnswerInput: '',
        lanWsRelayInput: '',
        lastKdsAckMessage: '',

        init() {
            try {
                const storedUuid = localStorage.getItem(this.storageKey);
                this.clientOrderUuid = this.isUuid(storedUuid) ? storedUuid : null;
            } catch (error) {
                console.error('Local order retry storage is unavailable.', error);
            }

            try {
                this.lanBridge = new LanPosKdsBridge({
                    restaurantSlug,
                    role: 'pos',
                    syncEndpoint: bridgeConfig.syncEndpoint || `/api/local/${encodeURIComponent(restaurantSlug)}/orders`,
                    signalPublishEndpoint: bridgeConfig.signalPublishEndpoint || `/${encodeURIComponent(restaurantSlug)}/lan-bridge/signal`,
                    signalPollEndpoint: bridgeConfig.signalPollEndpoint || `/${encodeURIComponent(restaurantSlug)}/lan-bridge/signal`,
                    csrfToken: bridgeConfig.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || null,
                    onConnectionStateChanged: (state) => {
                        this.lanConnectionState = state;
                    },
                    onAckReceived: (ack) => {
                        this.lastKdsAckMessage = `تم تأكيد استلام المطبخ (KDS) للطلب #${String(ack.client_order_id).slice(0, 8)} عبر الشبكة المحلية.`;
                    },
                });
                this.lanWsRelayInput = this.lanBridge.lanWsRelayUrl || '';
            } catch (error) {
                console.warn('Could not initialize LAN POS-KDS Bridge:', error);
            }
        },

        isUuid(value) {
            return typeof value === 'string'
                && /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
        },

        async generateOfflinePairingOffer() {
            if (!this.lanBridge) return;
            try {
                this.manualOfferCode = await this.lanBridge.createManualPairingOffer();
            } catch (error) {
                this.submissionError = error.message || 'تعذر إنشاء رمز الإقران المحلي.';
            }
        },

        async confirmOfflinePairingAnswer() {
            if (!this.lanBridge || !this.manualAnswerInput.trim()) return;
            try {
                await this.lanBridge.applyManualPairingAnswer(this.manualAnswerInput.trim());
                this.showLanPairingModal = false;
            } catch (error) {
                this.submissionError = 'رمز استجابة المطبخ غير صالح. تأكد من نسخه بالكامل.';
            }
        },

        saveLanRelayUrl() {
            if (!this.lanBridge) return;
            this.lanBridge.setLanWsRelayUrl(this.lanWsRelayInput);
        },

        async submit(wire, localDraftSnapshot = null) {
            this.submissionError = '';

            try {
                if (!this.clientOrderUuid) {
                    const uuid = globalThis.crypto?.randomUUID
                        ? globalThis.crypto.randomUUID()
                        : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                            const r = (Math.random() * 16) | 0;
                            const v = c === 'x' ? r : (r & 0x3) | 0x8;
                            return v.toString(16);
                        });
                    localStorage.setItem(this.storageKey, uuid);
                    this.clientOrderUuid = uuid;
                }

                // Always broadcast over LAN P2P DataChannel / LAN WS Relay / BroadcastChannel and save in IndexedDB outbox
                if (this.lanBridge && localDraftSnapshot) {
                    await this.lanBridge.publishPosOrder({
                        ...localDraftSnapshot,
                        client_order_id: this.clientOrderUuid,
                    });
                }

                if (!navigator.onLine) {
                    this.submissionError = 'انقطع الإنترنت الخارجي: تم إرسال الطلب مباشرة إلى شاشة المطبخ عبر الشبكة المحلية (LAN P2P) وحفظه في طابور المزامنة التلقائية.';
                    return;
                }

                if (!this.localOperationsEnabled) {
                    await wire.placeOrder();
                    this.clearAcceptedOrder(this.clientOrderUuid);
                    return;
                }

                await wire.placeOrder(this.clientOrderUuid);
            } catch (error) {
                console.error('Local POS order submission failed; retrying will reuse its identifier.', error);
                this.submissionError = 'تعذر تأكيد استلام الخادم للطلب (تم حفظه محلياً وإرساله لشاشة المطبخ عبر الشبكة المحلية). أعد المحاولة دون تغيير السلة لمنع التكرار.';
            }
        },

        clearAcceptedOrder(uuid) {
            if (uuid && this.clientOrderUuid && uuid !== this.clientOrderUuid) {
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
