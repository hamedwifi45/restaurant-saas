document.addEventListener('alpine:init', () => {
    Alpine.data('orderNotificationToasts', () => ({
        notifications: [],

        show(notification) {
            const toast = {
                id: `${Date.now()}-${Math.random()}`,
                title: notification.title,
                message: notification.message,
            };

            this.notifications.unshift(toast);
            window.setTimeout(() => {
                this.notifications = this.notifications.filter(({ id }) => id !== toast.id);
            }, 7000);
        },
    }));
});

const toastHost = document.querySelector('[data-order-notifications]');

if (toastHost && window.Echo) {
    const channelName = `restaurant.${toastHost.dataset.restaurantId}.customer.${toastHost.dataset.trackingCode}`;

    window.Echo.private(channelName).notification((notification) => {
        if (typeof notification.title !== 'string' || typeof notification.message !== 'string') {
            console.error('وصل إشعار طلب ببيانات غير صالحة.');

            return;
        }

        window.dispatchEvent(new CustomEvent('order-notification', { detail: notification }));

        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                throw new Error('Web Audio API is unavailable.');
            }

            const audioContext = new AudioContextClass();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = 660;
            gain.gain.setValueAtTime(0.035, audioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.16);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);

            audioContext.resume()
                .then(() => {
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.16);
                    oscillator.addEventListener('ended', () => audioContext.close(), { once: true });
                })
                .catch((error) => console.warn('تعذر تشغيل تنبيه الإشعار الصوتي.', error));
        } catch (error) {
            console.warn('تعذر تشغيل تنبيه الإشعار الصوتي.', error);
        }
    });
}
