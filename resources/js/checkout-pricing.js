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

        init() {
            const form = this.$root.matches('form') ? this.$root : this.$root.querySelector('form');
            window.checkoutPricingState = this;
            this.deliveryType = configuration.isDineIn ? 'dine_in' : this.deliveryType;
            this.isDineIn = this.deliveryType === 'dine_in';

            // الاستماع لحدث تحديث موقع الخريطة الجغرافي
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
        },

        setDeliveryType(deliveryType) {
            this.deliveryType = deliveryType;
            this.isDineIn = deliveryType === 'dine_in';
            this.recalculate();
            window.dispatchEvent(new Event('checkout-delivery-mode-changed'));
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
            return this.distance === null ? '' : `${this.distance.toFixed(2)} كم`;
        },

        validateSubmission(event) {
            this.persistCustomer();
            this.recalculate();

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
            this.recalculate();
        },
    }));
}

// التسجيل الآمن الفوري للـ Component
if (window.Alpine) {
    registerCheckoutPricingComponent();
} else {
    document.addEventListener('alpine:init', registerCheckoutPricingComponent);
}