function base64UrlToUint8Array(value) {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);

    return Uint8Array.from(rawData, character => character.charCodeAt(0));
}

async function persistPushSubscription(container, subscription) {
    const response = await fetch(container.dataset.subscriptionUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': container.dataset.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            ...subscription.toJSON(),
            token: container.dataset.deviceToken,
        }),
    });

    if (!response.ok) {
        throw new Error('تعذر حفظ اشتراك التنبيهات لهذا الطلب.');
    }
}

document.querySelectorAll('[data-order-push-subscription]').forEach(container => {
    const button = container.querySelector('[data-enable-order-push]');
    const status = container.querySelector('[data-order-push-status]');

    if (!button || !status) {
        return;
    }

    const supportsPush = 'Notification' in window
        && 'serviceWorker' in navigator
        && 'PushManager' in window;

    if (!supportsPush) {
        button.disabled = true;
        status.textContent = 'هذا المتصفح لا يدعم إشعارات الطلب.';
        return;
    }

    if (!container.dataset.vapidPublicKey) {
        button.disabled = true;
        status.textContent = 'إشعارات الطلب غير مهيأة لهذا الموقع.';
        return;
    }

    if (Notification.permission === 'denied') {
        button.disabled = true;
        status.textContent = 'تم حظر الإشعارات. يمكنك تفعيلها من إعدادات المتصفح.';
        return;
    }

    button.addEventListener('click', async () => {
        button.disabled = true;
        status.textContent = 'جارٍ تفعيل التنبيهات...';

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                status.textContent = 'لم يتم السماح بالإشعارات.';
                return;
            }

            const registration = await navigator.serviceWorker.register(
                container.dataset.serviceWorkerUrl,
                { scope: container.dataset.serviceWorkerScope },
            );
            const subscription = await registration.pushManager.getSubscription()
                || await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: base64UrlToUint8Array(container.dataset.vapidPublicKey),
                });

            await persistPushSubscription(container, subscription);
            status.textContent = 'تم تفعيل تنبيهات الطلب بنجاح.';
            button.textContent = 'التنبيهات مفعّلة';
        } catch (error) {
            console.error('Failed to enable order push notifications:', error);
            status.textContent = error.message || 'تعذر تفعيل التنبيهات.';
        } finally {
            button.disabled = false;
        }
    });
});
