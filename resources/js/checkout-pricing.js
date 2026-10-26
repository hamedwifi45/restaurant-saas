function registerCheckoutPricingComponent() {
    Alpine.data('checkoutPricing', (configuration) => ({
        ...configuration,
        customerName: '',
        customerPhone: '',
        customerAddress: '',
        customerLat: '',
        customerLng: '',
        deliveryType: configuration.deliveryType || (configuration.isDineIn ? 'dine_in' : 'delivery'),
        processing: false,
        distance: null,
        extraKilometers: 0,
        deliveryFeeHelper: '',
        deliveryFee: Number(configuration.legacyFee || 0),
        total: 0,
        deliveryFeeDisplay: '',
        totalDisplay: '',
        isOutOfRange: false,
        warningMessage: '',
        couponDiscount: 0,
        deliveryOptionsEndpoint: configuration.deliveryOptionsEndpoint || '',
        csrfToken: configuration.csrfToken || '',
        internalDriversAvailable: configuration.internalDriversAvailable !== undefined ? Boolean(configuration.internalDriversAvailable) : true,
        externalDeliveryEnabled: Boolean(configuration.externalDeliveryEnabled),
        eligibleProviders: [],
        selectedProviderId: configuration.initialProviderId ? String(configuration.initialProviderId) : '',
        loadingDeliveryOptions: false,
        routeSource: 'haversine',
        estimatedDurationMinutes: null,
        optionsAbortController: null,

        init() {
            const form = this.$root.matches('form') ? this.$root : this.$root.querySelector('form');
            window.checkoutPricingState = this;
            this.deliveryType = configuration.isDineIn ? 'dine_in' : this.deliveryType;
            this.isDineIn = this.deliveryType === 'dine_in';

            window.addEventListener('customer-location-updated', (event) => {
                const { latitude, longitude } = event.detail || {};

                if (latitude !== null && latitude !== undefined && latitude !== ''
                    && longitude !== null && longitude !== undefined && longitude !== ''
                    && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude))) {
                    this.setCustomerLocation(latitude, longitude);
                }
            });

            const fields = {
                customerName: form?.querySelector('[name="name"]'),
                customerPhone: form?.querySelector('[name="phone"]'),
                customerAddress: form?.querySelector('[name="address"]'),
                customerLat: form?.querySelector('[name="latitude"]'),
                customerLng: form?.querySelector('[name="longitude"]'),
            };
            const cookieNames = {
                customerName: 'customer_name',
                customerPhone: 'customer_phone',
                customerAddress: 'customer_address',
                customerLat: 'customer_lat',
                customerLng: 'customer_lng',
            };

            Object.entries(fields).forEach(([stateName, input]) => {
                let value = input?.value || this.readCookie(cookieNames[stateName]);

                if (['customerLat', 'customerLng'].includes(stateName) && value) {
                    const coordinate = Number(value);
                    const limit = stateName === 'customerLat' ? 90 : 180;

                    if (!Number.isFinite(coordinate) || Math.abs(coordinate) > limit) {
                        value = '';
                    }
                }

                this[stateName] = value;

                if (input && value) {
                    input.value = value;
                }
            });

            Object.values(fields).forEach((input) => {
                input?.dispatchEvent(new Event('input', { bubbles: true }));
            });

            window.dispatchEvent(new CustomEvent('checkout-customer-cookies-restored', {
                detail: {
                    latitude: this.customerLat,
                    longitude: this.customerLng,
                },
            }));

            this.recalculate();
            if (this.customerLat && this.customerLng) {
                this.fetchDeliveryOptions();
            }
        },

        formElement() {
            return this.$root.matches('form') ? this.$root : this.$root.querySelector('form');
        },

        syncCustomerFields() {
            const form = this.formElement();
            if (!form) return;
            const fields = {
                customerName: 'name',
                customerPhone: 'phone',
                customerAddress: 'address',
                customerLat: 'latitude',
                customerLng: 'longitude',
            };

            Object.entries(fields).forEach(([stateName, fieldName]) => {
                const input = form.querySelector(`[name="${fieldName}"]`);

                if (input) {
                    this[stateName] = input.value;
                }
            });
        },

        readCookie(name) {
            const cookie = document.cookie
                .split('; ')
                .find((part) => part.startsWith(`${name}=`));

            return cookie ? decodeURIComponent(cookie.slice(name.length + 1)) : '';
        },

        persistCustomer() {
            this.syncCustomerFields();
            const values = {
                customer_name: this.customerName,
                customer_phone: this.customerPhone,
                customer_address: this.customerAddress,
                customer_lat: this.customerLat,
                customer_lng: this.customerLng,
            };

            Object.entries(values).forEach(([name, value]) => {
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `${name}=${encodeURIComponent(value || '')}; Max-Age=2592000; Path=/; SameSite=Lax${secure}`;
            });
        },

        setCustomerLocation(latitude, longitude) {
            this.customerLat = String(latitude);
            this.customerLng = String(longitude);

            const form = this.formElement();
            if (form) {
                const latInput = form.querySelector('[name="latitude"]');
                const lngInput = form.querySelector('[name="longitude"]');
                if (latInput) latInput.value = this.customerLat;
                if (lngInput) lngInput.value = this.customerLng;
            }
            this.persistCustomer();
            this.recalculate();
            this.fetchDeliveryOptions();
        },

        setDeliveryType(deliveryType) {
            this.deliveryType = deliveryType;
            this.isDineIn = deliveryType === 'dine_in';
            this.recalculate();
            if (deliveryType === 'delivery') {
                this.fetchDeliveryOptions();
            }
            window.dispatchEvent(new Event('checkout-delivery-mode-changed'));
        },

        selectDeliveryProvider(providerId) {
            this.selectedProviderId = providerId ? String(providerId) : '';
            this.applySelectedDeliveryOption();
        },

        async fetchDeliveryOptions() {
            const lat = Number(this.customerLat);
            const lng = Number(this.customerLng);
            const hasCustomer = this.customerLat !== '' && this.customerLng !== ''
                && Number.isFinite(lat) && Number.isFinite(lng) && lat !== 0 && lng !== 0;

            if (!this.deliveryOptionsEndpoint || this.deliveryType !== 'delivery' || !hasCustomer) {
                return;
            }

            if (this.optionsAbortController) {
                this.optionsAbortController.abort();
            }
            this.optionsAbortController = new AbortController();
            this.loadingDeliveryOptions = true;

            try {
                const response = await fetch(this.deliveryOptionsEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng,
                        subtotal: Number(configuration.subtotal || 0),
                    }),
                    signal: this.optionsAbortController.signal,
                });

                if (!response.ok) return;

                const data = await response.json();
                this.internalDriversAvailable = Boolean(data.internal_driver_available);
                this.externalDeliveryEnabled = Boolean(data.external_delivery_enabled);
                this.eligibleProviders = Array.isArray(data.eligible_providers) ? data.eligible_providers : [];

                if (data.route && Number.isFinite(Number(data.route.distance_km))) {
                    this.distance = Number(data.route.distance_km);
                    this.routeSource = data.route.source || 'osrm';
                    this.estimatedDurationMinutes = data.route.duration_minutes ?? null;
                }

                const stillEligible = this.eligibleProviders.some(
                    (p) => String(p.provider_id) === String(this.selectedProviderId)
                );

                if (!this.internalDriversAvailable && !stillEligible) {
                    this.selectedProviderId = this.eligibleProviders.length > 0
                        ? String(this.eligibleProviders[0].provider_id)
                        : '';
                }

                this.applySelectedDeliveryOption(data);
            } catch (error) {
                // fallback to local calculation
            } finally {
                this.loadingDeliveryOptions = false;
            }
        },

        applySelectedDeliveryOption(serverData = null) {
            const chosenProvider = this.eligibleProviders.find(
                (p) => String(p.provider_id) === String(this.selectedProviderId)
            );

            if (chosenProvider) {
                this.distance = Number(chosenProvider.distance_km);
                this.deliveryFee = Number(chosenProvider.fee);
                this.estimatedDurationMinutes = chosenProvider.duration_minutes ?? this.estimatedDurationMinutes;
                this.routeSource = chosenProvider.route_source || this.routeSource;
                this.isOutOfRange = false;
                this.warningMessage = '';
                this.deliveryFeeHelper = `(عبر شركة ${chosenProvider.provider_name})`;
                this.updateTotals();
                return;
            }

            if (!this.internalDriversAvailable) {
                this.isOutOfRange = true;
                this.warningMessage = 'نعتذر، لا يتوفر مندوب توصيل داخلي حالياً، ولا توجد شركة توصيل خارجية مؤهلة تغطي موقعك.';
                this.updateTotals();
                return;
            }

            if (serverData && Number.isFinite(Number(serverData.internal_delivery_fee))) {
                this.deliveryFee = Number(serverData.internal_delivery_fee);
                this.isOutOfRange = !serverData.internal_in_range;
                this.warningMessage = this.isOutOfRange
                    ? `نعتذر، موقعك يبعد ${(this.distance || 0).toFixed(2)} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم`
                    : '';
                this.updateTotals();
            } else {
                this.recalculate();
            }
        },

        recalculate() {
            this.syncCustomerFields();
            const latitude = Number(this.customerLat);
            const longitude = Number(this.customerLng);
            const restaurantLatitude = Number(configuration.restaurantLatitude);
            const restaurantLongitude = Number(configuration.restaurantLongitude);
            const hasCustomerLocation = this.customerLat !== '' && this.customerLng !== ''
                && Number.isFinite(latitude) && Number.isFinite(longitude);
            const hasRestaurantLocation = configuration.restaurantLatitude !== null
                && configuration.restaurantLongitude !== null
                && Number.isFinite(restaurantLatitude) && Number.isFinite(restaurantLongitude);
            const isDineIn = this.deliveryType === 'dine_in';

            this.isOutOfRange = false;
            this.warningMessage = '';
            this.distance = null;
            this.extraKilometers = 0;

            if (isDineIn || this.deliveryType === 'takeaway') {
                this.deliveryFee = 0;
            } else if (hasCustomerLocation && hasRestaurantLocation) {
                const latitudeDifference = this.toRadians(latitude - restaurantLatitude);
                const longitudeDifference = this.toRadians(longitude - restaurantLongitude);
                const haversine = Math.sin(latitudeDifference / 2) ** 2
                    + Math.cos(this.toRadians(restaurantLatitude))
                    * Math.cos(this.toRadians(latitude))
                    * Math.sin(longitudeDifference / 2) ** 2;
                const boundedHaversine = Math.min(1, Math.max(0, haversine));

                this.distance = 6371 * 2 * Math.atan2(
                    Math.sqrt(boundedHaversine),
                    Math.sqrt(1 - boundedHaversine),
                );

                const baseKm = Number(configuration.baseDeliveryKilometers ?? 2);
                const baseFee = Number(configuration.baseDeliveryFee ?? 2);
                const perKmFee = Number(configuration.feePerAdditionalKilometer ?? 0.5);
                const radius = Number(configuration.deliveryRadius ?? 10);

                this.extraKilometers = Math.max(0, this.distance - baseKm);

                this.deliveryFee = Math.round((
                    baseFee + (this.extraKilometers * perKmFee)
                ) * 100) / 100;

                if (this.distance > radius) {
                    this.isOutOfRange = true;
                    this.warningMessage = `نعتذر، موقعك يبعد ${this.distance.toFixed(2)} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم (الحد الأقصى ${radius.toFixed(2)} كم)`;
                }
            } else {
                this.deliveryFee = hasRestaurantLocation
                    ? Number(configuration.baseDeliveryFee ?? 2)
                    : Number(configuration.legacyFee ?? 0);
            }

            this.deliveryFeeHelper = `(شاملة الرسم الأساسي + ${this.extraKilometers.toFixed(2)} كم إضافي)`;
            this.updateTotals();
        },

        updateTotals() {
            this.deliveryFeeDisplay = `${this.deliveryFee.toFixed(2)} ${configuration.currency || 'ر.س'}`;
            this.total = Number(configuration.subtotal || 0)
                + Number(configuration.tax || 0)
                + this.deliveryFee
                - Number(configuration.offerDiscount || 0)
                - this.couponDiscount;
            this.totalDisplay = `${this.total.toFixed(2)} ${configuration.currency || 'ر.س'}`;

            const form = this.formElement();
            if (form) {
                form.dataset.outOfRange = this.isOutOfRange ? 'true' : 'false';
            }
        },

        toRadians(degrees) {
            return degrees * Math.PI / 180;
        },

        formatDistance() {
            if (this.distance === null) return '';
            const durationLabel = this.estimatedDurationMinutes ? ` · حوالي ${this.estimatedDurationMinutes} د` : '';
            return `${this.distance.toFixed(2)} كم${durationLabel}`;
        },

        validateSubmission(event) {
            this.persistCustomer();
            if (!this.selectedProviderId) {
                this.recalculate();
            }

            if (this.isOutOfRange) {
                event.preventDefault();
                this.$nextTick(() => {
                    this.$refs.geofenceAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });

                return;
            }

            this.processing = true;
        },

        setCouponDiscount(discount) {
            this.couponDiscount = Number(discount) || 0;
            this.updateTotals();
        },
    }));
}

if (window.Alpine) {
    registerCheckoutPricingComponent();
} else {
    document.addEventListener('alpine:init', registerCheckoutPricingComponent);
}