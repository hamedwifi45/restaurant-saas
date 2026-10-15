<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;

class PwaController extends Controller
{
    /**
     * توليد manifest.json ديناميكياً لكل مطعم
     */
    public function manifest($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // تحديد الأيقونة باستخدام media_url لضمان مسار صحيح وصورة تعمل
        $iconUrl = $restaurant->logo
            ? media_url($restaurant->logo)
            : asset('storage/products/01M359T5S4QVWJ0P4HB5DH5YM1.jpg');

        $manifest = [
            'name' => $restaurant->name.' - '.($restaurant->description ?? 'طعم لا يُقاوم'),
            'short_name' => $restaurant->name,
            'description' => $restaurant->description ?? 'اطلب أشهى الأطباق مع توصيل سريع',

            // ✅ مطابقة الـ start_url والـ scope بدون أسلاش زائدة في النهاية
            'start_url' => '/'.$restaurant->slug,
            'scope' => '/'.$restaurant->slug,

            'display' => 'standalone',
            'background_color' => $restaurant->background_color ?? '#1A1A1A',
            'theme_color' => $restaurant->primary_color ?? '#FF6B35',
            'orientation' => 'portrait-primary',
            'lang' => 'ar',
            'dir' => 'rtl',
            'categories' => ['food', 'lifestyle'],

            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Service Worker ديناميكي لكل مطعم
     */
    public function serviceWorker($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurantSlug = json_encode($restaurant->slug, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $restaurantLogo = json_encode(
            media_url($restaurant->logo) ?? asset('apple-touch-icon.png'),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $sw = <<<JS
const RESTAURANT_SLUG = {$restaurantSlug};
const RESTAURANT_LOGO = {$restaurantLogo};
const CACHE_NAME = RESTAURANT_SLUG + '-v5';
const urlsToCache = [
    '/' + RESTAURANT_SLUG,
    '/' + RESTAURANT_SLUG + '/manifest.json'
];

self.addEventListener('install', event => {
    event.waitUntil(Promise.all([
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache)),
        self.skipWaiting()
    ]));
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request).catch(() => {
                    return new Response('', {
                        status: 408,
                        statusText: 'Network Request Failed'
                    });
                });
            })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        self.clients.claim().then(() => caches.keys()).then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

self.addEventListener('push', event => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (error) {
            payload = { body: event.data.text() };
        }
    }

    event.waitUntil(self.registration.showNotification(payload.title || 'تحديث الطلب', {
        body: payload.body || payload.message || '',
        icon: payload.icon || RESTAURANT_LOGO,
        badge: payload.badge || RESTAURANT_LOGO,
        dir: payload.dir || 'rtl',
        lang: payload.lang || 'ar',
        tag: payload.tag,
        data: payload.data || { url: '/' + RESTAURANT_SLUG }
    }));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const notificationUrl = new URL(event.notification.data?.url || '/' + RESTAURANT_SLUG, self.location.origin);
    if (notificationUrl.origin !== self.location.origin) return;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        for (const client of clients) {
            if ('focus' in client) {
                client.navigate(notificationUrl.href);
                return client.focus();
            }
        }

        return self.clients.openWindow(notificationUrl.href);
    }));
});
JS;

        return response($sw, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/'.$restaurant->slug);
    }
}
