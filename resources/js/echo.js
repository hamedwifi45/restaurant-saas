import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    csrfToken: csrfToken ?? null,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken ?? '',
            Accept: 'application/json',
        },
    },
    withCredentials: true,
    authorizer: (channel) => ({
        authorize(socketId, callback) {
            const orderChannel = channel.name.match(/^private-restaurant\.\d+\.customer\./);
            const orderTrackingElement = document.querySelector('[data-order-notifications]');
            const authUrl = orderChannel
                ? orderTrackingElement?.dataset.authUrl
                : '/broadcasting/auth';
            const currentCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!authUrl || !currentCsrfToken) {
                callback(new Error('تعذر تهيئة التحقق من قناة التحديثات: رابط التحقق أو رمز CSRF غير متاح.'), null);

                return;
            }

            fetch(authUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': currentCsrfToken,
                },
                body: new URLSearchParams({
                    socket_id: socketId,
                    channel_name: channel.name,
                }),
            })
                .then(async (response) => {
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'تعذر التحقق من قناة التحديثات.');
                    }

                    callback(null, data);
                })
                .catch((error) => callback(error, null));
        },
    }),
});
