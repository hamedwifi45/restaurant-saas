import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
        authorize(socketId, callback) {
            const orderChannel = channel.name.match(/^private-restaurant\.\d+\.customer\./);
            const orderTrackingElement = document.querySelector('[data-order-notifications]');
            const authUrl = orderChannel
                ? orderTrackingElement?.dataset.authUrl
                : '/broadcasting/auth';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!authUrl || !csrfToken) {
                callback(new Error('تعذر تهيئة التحقق من قناة التحديثات.'), null);

                return;
            }

            fetch(authUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken,
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
