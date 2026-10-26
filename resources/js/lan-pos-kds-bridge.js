/**
 * Cross-Device & Same-Device Local POS <-> KDS Bridge (Zero PHP/Server Install on Restaurant Devices)
 *
 * Supports 3 Transport Layers Simultaneously:
 *   1. WebRTC Direct LAN Peer-to-Peer DataChannel (RTCPeerConnection + RTCDataChannel):
 *      - Connects two physically separate devices (Cashier POS device & Kitchen KDS device) on the same Wi-Fi/LAN.
 *      - Negotiates either automatically via the cloud signaling endpoint while internet is up, OR 100% offline
 *        via compact Base64 SDP Offer/Answer pairing code between the two screens.
 *      - Once the DataChannel is open, traffic flows directly device-to-device over the local router and
 *        continues working even if the external internet cable is completely disconnected.
 *   2. Optional Local WebSocket Relay (ws://192.168.x.x:port) if a local LAN relay is configured.
 *   3. BroadcastChannel ('restaurant-lan-bridge:{slug}') for same-device multi-monitor setups.
 *
 * Plus Durable Storage & Reconciliation:
 *   - IndexedDB ('restaurant_lan_bridge_v2') stores outbox orders (`outbox_orders`) and KDS receipts (`kds_ack_receipts`).
 *   - Automatic deduplication via `client_order_uuid` (UUID v4) and background sync to Laravel when internet returns.
 */

const DB_NAME = 'restaurant_lan_bridge_v2';
const DB_VERSION = 1;
const OUTBOX_STORE = 'outbox_orders';
const RECEIPTS_STORE = 'kds_ack_receipts';

export class LanPosKdsBridge {
    constructor({
        restaurantSlug,
        role = 'pos',
        syncEndpoint = null,
        ackEndpoint = null,
        signalPublishEndpoint = null,
        signalPollEndpoint = null,
        lanWsRelayUrl = null,
        csrfToken = null,
        onOrderReceived = null,
        onAckReceived = null,
        onStatusChanged = null,
        onConnectionStateChanged = null,
    } = {}) {
        this.restaurantSlug = restaurantSlug || 'default';
        this.role = role; // 'pos' | 'kds'
        this.syncEndpoint = syncEndpoint;
        this.ackEndpoint = ackEndpoint;
        this.signalPublishEndpoint = signalPublishEndpoint;
        this.signalPollEndpoint = signalPollEndpoint;
        this.lanWsRelayUrl = lanWsRelayUrl || this.readSavedRelayUrl();
        this.csrfToken = csrfToken;
        this.onOrderReceived = onOrderReceived;
        this.onAckReceived = onAckReceived;
        this.onStatusChanged = onStatusChanged;
        this.onConnectionStateChanged = onConnectionStateChanged;

        this.peerId = `peer-${this.role}-${Math.random().toString(36).slice(2, 10)}`;
        this.channelName = `restaurant-lan-bridge:${this.restaurantSlug}`;
        this.channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel(this.channelName) : null;
        this.peerConnection = null;
        this.dataChannel = null;
        this.wsRelay = null;
        this.lastProcessedSignalTimestamp = null;
        this.connectionState = 'disconnected'; // 'disconnected' | 'connecting' | 'connected_lan_p2p' | 'connected_lan_relay'
        this.dbPromise = this.openDatabase();

        this.bindListeners();
        this.initLanTransports();
    }

    readSavedRelayUrl() {
        try {
            return localStorage.getItem(`restaurant-saas:lan-ws-relay:${this.restaurantSlug}`) || null;
        } catch {
            return null;
        }
    }

    setLanWsRelayUrl(url) {
        this.lanWsRelayUrl = url ? url.trim() : null;
        try {
            if (this.lanWsRelayUrl) {
                localStorage.setItem(`restaurant-saas:lan-ws-relay:${this.restaurantSlug}`, this.lanWsRelayUrl);
            } else {
                localStorage.removeItem(`restaurant-saas:lan-ws-relay:${this.restaurantSlug}`);
            }
        } catch {
            // ignore storage errors
        }
        if (this.lanWsRelayUrl) {
            this.connectLanWebSocketRelay();
        }
    }

    updateConnectionState(newState) {
        this.connectionState = newState;
        if (typeof this.onConnectionStateChanged === 'function') {
            this.onConnectionStateChanged(newState);
        }
        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('lan-bridge-state-changed', {
                detail: { role: this.role, state: newState, restaurantSlug: this.restaurantSlug },
            }));
        }
    }

    openDatabase() {
        if (typeof indexedDB === 'undefined') {
            return Promise.resolve(null);
        }

        return new Promise((resolve) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(OUTBOX_STORE)) {
                    const store = db.createObjectStore(OUTBOX_STORE, { keyPath: 'client_order_id' });
                    store.createIndex('sync_status', 'sync_status', { unique: false });
                }
                if (!db.objectStoreNames.contains(RECEIPTS_STORE)) {
                    db.createObjectStore(RECEIPTS_STORE, { keyPath: 'client_order_id' });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
        });
    }

    bindListeners() {
        if (this.channel) {
            this.channel.onmessage = (event) => {
                this.handleEnvelopeMessage(event.data, 'broadcast_channel');
            };
        }

        if (typeof window !== 'undefined') {
            window.addEventListener('online', () => {
                this.flushPendingOutboxToServer();
                this.startAutoSignalingLoop();
            });
        }
    }

    initLanTransports() {
        if (this.lanWsRelayUrl) {
            this.connectLanWebSocketRelay();
        }

        if (typeof navigator !== 'undefined' && navigator.onLine && this.signalPublishEndpoint && this.signalPollEndpoint) {
            this.startAutoSignalingLoop();
        }
    }

    /**
     * Connect to an optional local network WebSocket relay (e.g. ws://192.168.1.50:8765)
     */
    connectLanWebSocketRelay() {
        if (!this.lanWsRelayUrl || typeof WebSocket === 'undefined') {
            return;
        }

        try {
            if (this.wsRelay) {
                this.wsRelay.close();
            }

            this.wsRelay = new WebSocket(this.lanWsRelayUrl);
            this.wsRelay.onopen = () => {
                if (this.connectionState !== 'connected_lan_p2p') {
                    this.updateConnectionState('connected_lan_relay');
                }
                this.replayUnacknowledgedOrdersOverLan();
            };

            this.wsRelay.onmessage = (event) => {
                try {
                    const parsed = JSON.parse(event.data);
                    this.handleEnvelopeMessage(parsed, 'lan_ws_relay');
                } catch {
                    // ignore malformed frames
                }
            };

            this.wsRelay.onclose = () => {
                if (this.connectionState === 'connected_lan_relay') {
                    this.updateConnectionState('disconnected');
                }
            };
        } catch {
            // ignore connection failure
        }
    }

    /**
     * Create a new RTCPeerConnection configured for local LAN host candidates (zero STUN dependency required on LAN).
     */
    createPeerConnection() {
        if (typeof RTCPeerConnection === 'undefined') {
            return null;
        }

        if (this.peerConnection) {
            try {
                this.peerConnection.close();
            } catch {
                // ignore
            }
        }

        const pc = new RTCPeerConnection({
            iceServers: [], // Pure local network (LAN) host candidates so it works without internet
        });

        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'connected') {
                this.updateConnectionState('connected_lan_p2p');
                this.replayUnacknowledgedOrdersOverLan();
            } else if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                this.updateConnectionState(this.wsRelay?.readyState === 1 ? 'connected_lan_relay' : 'disconnected');
            }
        };

        pc.ondatachannel = (event) => {
            this.attachDataChannel(event.channel);
        };

        this.peerConnection = pc;
        return pc;
    }

    attachDataChannel(channel) {
        this.dataChannel = channel;

        channel.onopen = () => {
            this.updateConnectionState('connected_lan_p2p');
            this.replayUnacknowledgedOrdersOverLan();
        };

        channel.onmessage = (event) => {
            try {
                const payload = JSON.parse(event.data);
                this.handleEnvelopeMessage(payload, 'webrtc_datachannel');
            } catch {
                // ignore invalid JSON
            }
        };

        channel.onclose = () => {
            if (this.connectionState === 'connected_lan_p2p') {
                this.updateConnectionState(this.wsRelay?.readyState === 1 ? 'connected_lan_relay' : 'disconnected');
            }
        };
    }

    waitForIceGatheringComplete(pc, timeoutMs = 2500) {
        if (pc.iceGatheringState === 'complete') {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const timer = setTimeout(() => {
                pc.removeEventListener('icegatheringstatechange', checkState);
                resolve();
            }, timeoutMs);

            function checkState() {
                if (pc.iceGatheringState === 'complete') {
                    clearTimeout(timer);
                    pc.removeEventListener('icegatheringstatechange', checkState);
                    resolve();
                }
            }

            pc.addEventListener('icegatheringstatechange', checkState);
        });
    }

    /**
     * Generate a compact Base64 WebRTC SDP Offer (usable both for automatic signaling and 100% offline manual pairing).
     */
    async createManualPairingOffer() {
        const pc = this.createPeerConnection();
        if (!pc) {
            throw new Error('WebRTC غير مدعوم في هذا المتصفح.');
        }

        this.updateConnectionState('connecting');
        const channel = pc.createDataChannel('pos-kds-lan-dc', { ordered: true });
        this.attachDataChannel(channel);

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        await this.waitForIceGatheringComplete(pc);

        const encoded = btoa(unescape(encodeURIComponent(JSON.stringify(pc.localDescription))));
        return encoded;
    }

    /**
     * Accept a Base64 WebRTC SDP Offer on KDS and return a Base64 SDP Answer.
     */
    async acceptManualPairingOfferAndCreateAnswer(encodedOffer) {
        const pc = this.createPeerConnection();
        if (!pc) {
            throw new Error('WebRTC غير مدعوم في هذا المتصفح.');
        }

        this.updateConnectionState('connecting');
        const offerDesc = JSON.parse(decodeURIComponent(escape(atob(encodedOffer.trim()))));
        await pc.setRemoteDescription(new RTCSessionDescription(offerDesc));

        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        await this.waitForIceGatheringComplete(pc);

        return btoa(unescape(encodeURIComponent(JSON.stringify(pc.localDescription))));
    }

    /**
     * Complete the handshake on POS by applying the Base64 SDP Answer from KDS.
     */
    async applyManualPairingAnswer(encodedAnswer) {
        if (!this.peerConnection) {
            throw new Error('ابدأ إنشاء طلب الإقران أولاً من الكاشير.');
        }

        const answerDesc = JSON.parse(decodeURIComponent(escape(atob(encodedAnswer.trim()))));
        await this.peerConnection.setRemoteDescription(new RTCSessionDescription(answerDesc));
        return true;
    }

    /**
     * Automatic background LAN WebRTC handshake while internet is available.
     * Once connected, the P2P DataChannel survives even if the internet drops!
     */
    async startAutoSignalingLoop() {
        if (!this.signalPublishEndpoint || !this.signalPollEndpoint || this.connectionState === 'connected_lan_p2p') {
            return;
        }

        try {
            if (this.role === 'pos' && (!this.peerConnection || this.peerConnection.connectionState === 'closed')) {
                const offerCode = await this.createManualPairingOffer();
                await fetch(this.signalPublishEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                    },
                    body: JSON.stringify({
                        role: 'pos',
                        type: 'offer',
                        sdp_payload: offerCode,
                        peer_id: this.peerId,
                    }),
                });
            }

            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }

            this.pollTimer = setInterval(async () => {
                if (this.connectionState === 'connected_lan_p2p' || !navigator.onLine) {
                    return;
                }

                try {
                    const response = await fetch(this.signalPollEndpoint, {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) return;
                    const signals = await response.json();

                    if (this.role === 'kds' && signals.offer?.sdp_payload) {
                        if (this.lastProcessedSignalTimestamp !== signals.offer.published_at) {
                            this.lastProcessedSignalTimestamp = signals.offer.published_at;
                            const answerCode = await this.acceptManualPairingOfferAndCreateAnswer(signals.offer.sdp_payload);
                            await fetch(this.signalPublishEndpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                                },
                                body: JSON.stringify({
                                    role: 'kds',
                                    type: 'answer',
                                    sdp_payload: answerCode,
                                    peer_id: this.peerId,
                                }),
                            });
                        }
                    } else if (this.role === 'pos' && signals.answer?.sdp_payload) {
                        if (
                            this.lastProcessedSignalTimestamp !== signals.answer.published_at &&
                            this.peerConnection?.signalingState === 'have-local-offer'
                        ) {
                            this.lastProcessedSignalTimestamp = signals.answer.published_at;
                            await this.applyManualPairingAnswer(signals.answer.sdp_payload);
                        }
                    }
                } catch {
                    // Silent retry while waiting for peer
                }
            }, 6000);
        } catch {
            // Fallback to manual pairing if offline
        }
    }

    /**
     * Send message across all active local transports (WebRTC DataChannel + LAN WS Relay + BroadcastChannel).
     */
    sendOverLocalTransports(message) {
        const serialized = JSON.stringify(message);

        if (this.dataChannel && this.dataChannel.readyState === 'open') {
            try {
                this.dataChannel.send(serialized);
            } catch {
                // ignore
            }
        }

        if (this.wsRelay && this.wsRelay.readyState === 1) {
            try {
                this.wsRelay.send(serialized);
            } catch {
                // ignore
            }
        }

        if (this.channel) {
            try {
                this.channel.postMessage(message);
            } catch {
                // ignore
            }
        }
    }

    handleEnvelopeMessage(payload, transportSource) {
        if (!payload || payload.restaurant_slug !== this.restaurantSlug) {
            return;
        }

        if (payload.type === 'POS_ORDER_CREATED' && this.role === 'kds') {
            this.handleIncomingPosOrderOnKds(payload.order, transportSource);
        } else if (payload.type === 'KDS_ORDER_ACK' && this.role === 'pos') {
            this.markOrderAcknowledgedByKds(payload.client_order_id, payload.acknowledged_at);
            if (typeof this.onAckReceived === 'function') {
                this.onAckReceived(payload);
            }
        } else if (payload.type === 'KDS_STATUS_CHANGED' && this.role === 'pos') {
            if (typeof this.onStatusChanged === 'function') {
                this.onStatusChanged(payload);
            }
        }
    }

    /**
     * Dispatch an order immediately from POS to KDS over LAN and store in IndexedDB outbox.
     */
    async publishPosOrder(orderDraft) {
        const clientOrderId =
            orderDraft.client_order_id ||
            orderDraft.idempotency_key ||
            (globalThis.crypto?.randomUUID ? globalThis.crypto.randomUUID() : `local-${Date.now()}`);

        const envelope = {
            ...orderDraft,
            client_order_id: clientOrderId,
            idempotency_key: clientOrderId,
            restaurant_slug: this.restaurantSlug,
            created_at_iso: orderDraft.created_at_iso || new Date().toISOString(),
            kds_acknowledged: false,
            sync_status: navigator.onLine ? 'syncing' : 'queued_offline',
        };

        await this.putRecord(OUTBOX_STORE, envelope);

        this.sendOverLocalTransports({
            type: 'POS_ORDER_CREATED',
            restaurant_slug: this.restaurantSlug,
            order: envelope,
        });

        if (navigator.onLine && this.syncEndpoint) {
            await this.syncSingleOrderToServer(envelope);
        }

        return envelope;
    }

    /**
     * Replay any unacknowledged orders from POS IndexedDB when KDS connects over WebRTC/LAN.
     */
    async replayUnacknowledgedOrdersOverLan() {
        if (this.role !== 'pos') {
            return;
        }

        const allOrders = await this.getAllRecords(OUTBOX_STORE);
        const unacknowledged = allOrders.filter((item) => !item.kds_acknowledged);

        for (const order of unacknowledged) {
            this.sendOverLocalTransports({
                type: 'POS_ORDER_CREATED',
                restaurant_slug: this.restaurantSlug,
                order,
            });
        }
    }

    async handleIncomingPosOrderOnKds(orderEnvelope, transportSource = 'lan') {
        const existingReceipt = await this.getRecord(RECEIPTS_STORE, orderEnvelope.client_order_id);
        const ackTimestamp = existingReceipt?.acknowledged_at || new Date().toISOString();

        if (!existingReceipt) {
            await this.putRecord(RECEIPTS_STORE, {
                client_order_id: orderEnvelope.client_order_id,
                acknowledged_at: ackTimestamp,
                transport_source: transportSource,
                order_snapshot: orderEnvelope,
            });

            if (typeof this.onOrderReceived === 'function') {
                this.onOrderReceived(orderEnvelope);
            }
        }

        this.sendOverLocalTransports({
            type: 'KDS_ORDER_ACK',
            restaurant_slug: this.restaurantSlug,
            client_order_id: orderEnvelope.client_order_id,
            acknowledged_at: ackTimestamp,
        });

        if (navigator.onLine && this.ackEndpoint && /^[0-9a-f-]{36}$/i.test(orderEnvelope.client_order_id)) {
            try {
                await fetch(this.ackEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                    },
                    body: JSON.stringify({ client_order_uuids: [orderEnvelope.client_order_id] }),
                });
            } catch {
                // Saved in IndexedDB; will be acknowledged on next online sync
            }
        }
    }

    /**
     * Broadcast status updates (preparing / ready / completed) from KDS back to POS over LAN.
     */
    publishKdsStatusChange(orderId, status) {
        this.sendOverLocalTransports({
            type: 'KDS_STATUS_CHANGED',
            restaurant_slug: this.restaurantSlug,
            order_id: orderId,
            status,
            updated_at_iso: new Date().toISOString(),
        });
    }

    async markOrderAcknowledgedByKds(clientOrderId, acknowledgedAt) {
        const existing = await this.getRecord(OUTBOX_STORE, clientOrderId);
        if (!existing) {
            return;
        }

        existing.kds_acknowledged = true;
        existing.kds_acknowledged_at = acknowledgedAt;
        await this.putRecord(OUTBOX_STORE, existing);
    }

    async syncSingleOrderToServer(orderEnvelope) {
        if (!this.syncEndpoint || !orderEnvelope.items || !orderEnvelope.checkout) {
            return false;
        }

        try {
            const response = await fetch(this.syncEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                body: JSON.stringify({
                    idempotency_key: orderEnvelope.client_order_id,
                    items: orderEnvelope.items,
                    checkout: orderEnvelope.checkout,
                }),
            });

            if (response.ok) {
                const result = await response.json();
                orderEnvelope.sync_status = 'synced';
                orderEnvelope.tracking_code = result.data?.tracking_code || orderEnvelope.tracking_code;
                await this.putRecord(OUTBOX_STORE, orderEnvelope);
                return true;
            }
        } catch {
            orderEnvelope.sync_status = 'queued_offline';
            await this.putRecord(OUTBOX_STORE, orderEnvelope);
        }

        return false;
    }

    async flushPendingOutboxToServer() {
        const allOrders = await this.getAllRecords(OUTBOX_STORE);
        const pending = allOrders.filter((item) => item.sync_status !== 'synced');

        for (const order of pending) {
            await this.syncSingleOrderToServer(order);
        }
    }

    async putRecord(storeName, value) {
        const db = await this.dbPromise;
        if (!db) return false;

        return new Promise((resolve) => {
            const tx = db.transaction(storeName, 'readwrite');
            tx.objectStore(storeName).put(value);
            tx.oncomplete = () => resolve(true);
            tx.onerror = () => resolve(false);
        });
    }

    async getRecord(storeName, key) {
        const db = await this.dbPromise;
        if (!db) return null;

        return new Promise((resolve) => {
            const tx = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).get(key);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => resolve(null);
        });
    }

    async getAllRecords(storeName) {
        const db = await this.dbPromise;
        if (!db) return [];

        return new Promise((resolve) => {
            const tx = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }
}

if (typeof window !== 'undefined') {
    window.LanPosKdsBridge = LanPosKdsBridge;
}
