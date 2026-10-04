document.querySelectorAll('[data-driver-rating][hidden]').forEach(panel => {
    const updateUrl = panel.dataset.statusUrl;

    if (!updateUrl) {
        return;
    }

    const interval = window.setInterval(async () => {
        try {
            const response = await fetch(updateUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`تعذر التحقق من حالة الطلب (HTTP ${response.status}).`);
            }

            const order = await response.json();
            if (order.status === 'delivered') {
                panel.hidden = false;
                window.clearInterval(interval);
            }
        } catch (error) {
            console.error('Failed to check whether the order was delivered:', error);
        }
    }, 10000);
});
