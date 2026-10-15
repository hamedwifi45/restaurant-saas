<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<script src="{{ asset('js/offline-map-tiles.js') }}" defer></script>

<div
    x-data="restaurantLocationPicker($wire.entangle('data.latitude'), $wire.entangle('data.longitude'))"
    x-init="init()"
    class="space-y-3"
    dir="rtl"
>
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="flex min-w-0 flex-1 gap-2">
            <input
                x-model="searchQuery"
                @keydown.enter.prevent="searchLocation"
                type="search"
                class="min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                placeholder="ابحث عن مدينة أو شارع"
                aria-label="ابحث عن مدينة أو شارع"
            >
            <button
                type="button"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
                @click="searchLocation"
                :disabled="isSearching"
            >
                <span x-show="!isSearching">بحث</span>
                <span x-show="isSearching" x-cloak>جارٍ البحث...</span>
            </button>
        </div>

        <button
            type="button"
            class="rounded-lg border border-primary-600 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-50 disabled:opacity-50 dark:text-primary-300 dark:hover:bg-gray-800"
            @click="detectCurrentLocation"
            :disabled="isLocating"
        >
            <span x-show="!isLocating">تحديد موقعي الحالي</span>
            <span x-show="isLocating" x-cloak>جارٍ تحديد الموقع...</span>
        </button>
    </div>

    <p
        x-show="message"
        x-text="message"
        role="status"
        aria-live="polite"
        class="text-sm text-gray-600 dark:text-gray-300"
    ></p>
    <p
        x-show="errorMessage"
        x-text="errorMessage"
        role="alert"
        class="text-sm text-danger-600 dark:text-danger-400"
    ></p>

    <div
        x-ref="mapContainer"
        wire:ignore
        class="mt-3 w-full rounded-lg border border-gray-300 shadow-sm dark:border-gray-700"
        style="height: 400px; min-height: 400px; position: relative; z-index: 1;"
        aria-label="خريطة لتحديد موقع المطعم"
    ></div>

    <p class="text-sm text-gray-600 dark:text-gray-300">
        اسحب الدبوس أو انقر على الخريطة لتحديد الموقع المباشر لمطعمك بدقة عالية.
    </p>

    <div class="flex flex-wrap gap-2 text-sm" aria-live="polite">
        <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
            خط العرض:
            <span x-text="formattedCoordinate(latitude)"></span>
        </span>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
            خط الطول:
            <span x-text="formattedCoordinate(longitude)"></span>
        </span>
    </div>
</div>

@once
    <script>
        window.restaurantLocationPicker = (latitude, longitude) => ({
            latitude,
            longitude,
            map: null,
            marker: null,
            searchQuery: '',
            isSearching: false,
            isLocating: false,
            message: '',
            errorMessage: '',
            lastSearchAt: 0,

            init() {
                this.$nextTick(() => {
                    const mapContainer = this.$refs.mapContainer;

                    if (!mapContainer || mapContainer._leaflet_id) {
                        return;
                    }

                    this.initializeMap(mapContainer);
                });
            },

            async initializeMap(mapContainer) {
                try {
                    await this.loadLeaflet();

                    if (!this.$refs.mapContainer || this.$refs.mapContainer !== mapContainer || mapContainer._leaflet_id) {
                        return;
                    }

                    const hasSavedCoordinates = this.hasCoordinates();
                    const initialLatitude = hasSavedCoordinates ? Number(this.latitude) : 33.5138;
                    const initialLongitude = hasSavedCoordinates ? Number(this.longitude) : 36.2765;
                    const initialZoom = hasSavedCoordinates ? 17 : 13;

                    this.map = window.L.map(mapContainer).setView(
                        [initialLatitude, initialLongitude],
                        initialZoom,
                    );

                    window.addOfflineAwareMapTiles(this.map);

                    this.marker = window.L.marker([initialLatitude, initialLongitude], {
                        draggable: true,
                    }).addTo(this.map);

                    this.marker.on('dragend', () => {
                        this.syncCoordinates(this.marker.getLatLng());
                    });

                    this.map.on('click', (event) => {
                        this.moveMarker(event.latlng, true);
                    });

                    this.$watch('latitude', () => this.syncMarkerFromFields());
                    this.$watch('longitude', () => this.syncMarkerFromFields());

                    this.$nextTick(() => {
                        setTimeout(() => {
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        }, 200);
                    });
                } catch (error) {
                    console.error('Unable to initialize the restaurant location map.', error);
                    this.errorMessage = 'تعذر تحميل الخريطة. يرجى تحديث الصفحة والمحاولة مجدداً.';
                }
            },

            loadLeaflet() {
                if (window.L) {
                    return Promise.resolve();
                }

                if (!window.restaurantLeafletLoading) {
                    window.restaurantLeafletLoading = new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = @json(asset('vendor/leaflet/leaflet.js'));
                        script.onload = resolve;
                        script.onerror = () => reject(new Error('Leaflet could not be loaded.'));
                        document.head.appendChild(script);
                    });
                }

                return window.restaurantLeafletLoading;
            },

            hasCoordinates() {
                return this.latitude !== null
                    && this.latitude !== ''
                    && this.longitude !== null
                    && this.longitude !== ''
                    && Number.isFinite(Number(this.latitude))
                    && Number.isFinite(Number(this.longitude));
            },

            syncCoordinates(position) {
                this.latitude = Number(position.lat).toFixed(7);
                this.longitude = Number(position.lng).toFixed(7);
                this.message = '';
                this.errorMessage = '';
            },

            syncMarkerFromFields() {
                if (!this.map || !this.marker || !this.hasCoordinates()) {
                    return;
                }

                const latitude = Number(this.latitude);
                const longitude = Number(this.longitude);

                if (latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                    return;
                }

                const position = window.L.latLng(latitude, longitude);

                if (!this.marker.getLatLng().equals(position)) {
                    this.marker.setLatLng(position);
                    this.map.panTo(position);
                }
            },

            moveMarker(position, zoomToMarker = false) {
                this.marker.setLatLng(position);
                this.syncCoordinates(position);

                if (zoomToMarker) {
                    this.map.setView(position, Math.max(this.map.getZoom(), 17));
                }
            },

            formattedCoordinate(coordinate) {
                if (coordinate === null || coordinate === '' || !Number.isFinite(Number(coordinate))) {
                    return 'غير محدد';
                }

                return Number(coordinate).toFixed(6);
            },

            detectCurrentLocation() {
                this.message = '';
                this.errorMessage = '';

                if (!navigator.geolocation) {
                    this.errorMessage = 'المتصفح لا يدعم تحديد الموقع الحالي.';
                    return;
                }

                this.isLocating = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const point = window.L.latLng(position.coords.latitude, position.coords.longitude);
                        this.moveMarker(point);
                        this.map.setView(point, 18);
                        this.isLocating = false;
                        this.message = 'تم تحديد موقعك الحالي.';
                    },
                    (error) => {
                        this.isLocating = false;
                        this.errorMessage = error.code === error.PERMISSION_DENIED
                            ? 'يرجى السماح للمتصفح بالوصول إلى موقعك الحالي.'
                            : 'تعذر تحديد موقعك الحالي. يرجى المحاولة مجدداً.';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    },
                );
            },

            async searchLocation() {
                const query = this.searchQuery.trim();

                if (!query) {
                    this.errorMessage = 'أدخل اسم المدينة أو الشارع للبحث.';
                    return;
                }

                if (!navigator.onLine) {
                    this.errorMessage = 'البحث عن المواقع يحتاج اتصالاً بالإنترنت. يمكنك تحديد الموقع يدوياً على الخريطة.';
                    return;
                }

                if (Date.now() - this.lastSearchAt < 1000) {
                    this.errorMessage = 'يرجى الانتظار قليلاً قبل إجراء بحث آخر.';
                    return;
                }

                this.lastSearchAt = Date.now();
                this.isSearching = true;
                this.message = '';
                this.errorMessage = '';

                try {
                    const parameters = new URLSearchParams({
                        q: query,
                        format: 'jsonv2',
                        limit: '1',
                        'accept-language': 'ar',
                    });
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/search?${parameters.toString()}`,
                        {
                            headers: { Accept: 'application/json' },
                            signal: AbortSignal.timeout(5000),
                        },
                    );

                    if (!response.ok) {
                        throw new Error(`Geocoding request failed with status ${response.status}.`);
                    }

                    const results = await response.json();

                    if (!results.length) {
                        this.errorMessage = 'لم يتم العثور على موقع مطابق لبحثك.';
                        return;
                    }

                    const point = window.L.latLng(Number(results[0].lat), Number(results[0].lon));
                    this.moveMarker(point);
                    this.map.setView(point, 17);
                    this.message = 'تم تحديد الموقع من نتيجة البحث.';
                } catch (error) {
                    console.error('Unable to search for the restaurant location.', error);
                    this.errorMessage = 'تعذر البحث عن الموقع حالياً. يرجى المحاولة مجدداً.';
                } finally {
                    this.isSearching = false;
                }
            },

            destroy() {
                this.map?.remove();
                this.map = null;
            },
        });
    </script>
@endonce
