<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

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

        // تحديد الأيقونة (شعار المطعم أو أيقونة افتراضية)
        $iconUrl = $restaurant->logo
            ? asset('storage/' . $restaurant->logo)
            : asset('/icons/default-icon.png');

        // بناء الـ Manifest بتصحيح جميع أخطاء Chrome
        $manifest = [
            'name' => $restaurant->name . ' - ' . ($restaurant->description ?? 'طعم لا يُقاوم'),
            'short_name' => $restaurant->name,
            'description' => $restaurant->description ?? 'اطلب أشهى الأطباق مع توصيل سريع',

            // ✅ تصحيح مشكلة Scope: استخدام مسارات نسبية لضمان التطابق التام
            'start_url' => '/' . $restaurant->slug,
            'scope' => '/' . $restaurant->slug . '/',

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
                    'purpose' => 'any' // ✅ تم تغييرها من 'any maskable' لتجنب التحذير
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any' // ✅ تم تغييرها من 'any maskable' لتجنب التحذير
                ],
            ],

            // ✅ إضافة لقطات الشاشة لحل تحذيرات Richer PWA Install UI
            'screenshots' => [
                [
                    'src' => 'https://via.placeholder.com/1280x720/FF6B35/FFFFFF?text=Desktop+View',
                    'sizes' => '1280x720',
                    'type' => 'image/png',
                    'form_factor' => 'wide' // ✅ لحل تحذير سطح المكتب
                ],
                [
                    'src' => 'https://via.placeholder.com/750x1334/FF6B35/FFFFFF?text=Mobile+View',
                    'sizes' => '750x1334',
                    'type' => 'image/png',
                    'form_factor' => 'narrow' // ✅ لحل تحذير الجوال
                ]
            ]
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

        $sw = <<<JS
const CACHE_NAME = '{$restaurant->slug}-v1';
const urlsToCache = [
    '/{$restaurant->slug}',
    '/{$restaurant->slug}/manifest.json',
    'https://cdn.tailwindcss.com',
    'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap'
];

// تثبيت Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache for {$restaurant->slug}');
                return cache.addAll(urlsToCache);
            })
    );
});

// جلب الموارد من الـ Cache
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});

// تحديث Service Worker
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
JS;

        return response($sw, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/' . $restaurant->slug . '/');
    }
}
