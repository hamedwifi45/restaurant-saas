(() => {
    const databasePrefix = 'restaurant-saas-kds-receipts-';
    const uuidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

    function openDatabase(restaurantId) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(`${databasePrefix}${restaurantId}`, 1);

            request.onupgradeneeded = () => {
                const database = request.result;
                if (!database.objectStoreNames.contains('tickets')) {
                    database.createObjectStore('tickets', { keyPath: 'client_order_uuid' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error || new Error('Could not open KDS local storage.'));
        });
    }

    async function persistTickets(restaurantId, orders) {
        const tickets = orders.filter((order) => uuidPattern.test(order.client_order_uuid || ''));
        if (tickets.length === 0) {
            return [];
        }

        const database = await openDatabase(restaurantId);
        try {
            await new Promise((resolve, reject) => {
                const transaction = database.transaction('tickets', 'readwrite');
                const store = transaction.objectStore('tickets');
                tickets.forEach((ticket) => store.put(ticket));
                transaction.oncomplete = resolve;
                transaction.onerror = () => reject(transaction.error || new Error('Could not persist KDS tickets.'));
                transaction.onabort = () => reject(transaction.error || new Error('KDS ticket storage transaction aborted.'));
            });
        } finally {
            database.close();
        }

        return tickets.map((ticket) => ticket.client_order_uuid);
    }

    async function persistAndAcknowledge(restaurantId, orders, endpoint, csrfToken) {
        const clientOrderUuids = await persistTickets(restaurantId, orders);
        if (clientOrderUuids.length === 0) {
            return;
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ client_order_uuids: clientOrderUuids }),
        });

        if (!response.ok) {
            throw new Error(`KDS receipt acknowledgement failed (${response.status}); local ticket data remains saved.`);
        }
    }

    window.localKdsReceipts = { persistAndAcknowledge };
})();
