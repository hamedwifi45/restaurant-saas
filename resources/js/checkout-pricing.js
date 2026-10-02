document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutPricing', (configuration) => ({
        ...configuration,
        customerName: '',
        customerPhone: '',
        customerAddress: '',
        customerLatitude: '',
        customerLongitude: '',
        deliveryType: configuration.isDineIn ? 'dine_in' : 'delivery',
        processing: false,
        distanceKm: null,
        deliveryFee: Number(configuration.legacyFee),
        deliveryFeeDisplay: '',
        totalDisplay: '',
        geofenceError: '',
        couponDiscount: 0,

        init() {
            const form = this.$root.matches('form') ? this.$root : this.$root.querySelector('form');
            window.checkoutPricingState = this;
            const fields = {
                customerName: form.querySelector('[name="name"]'),
                customerPhone: form.querySelector('[name="phone"]'),
                customerAddress: form.querySelector('[name="address"]'),
                customerLatitude: form.querySelector('[name="latitude"]'),
                customerLongitude: form.querySelector('[name="longitude"]'),
            };
            const cookieNames = {
                customerName: 'customer_name',
                customerPhone: 'customer_phone',
                customerAddress: 'customer_address',
                customerLatitude: 'customer_lat',
                customerLongitude: 'customer_lng',
            };

            Object.entries(fields).forEach(([stateName, input]) => {
                let value = input?.value || this.readCookie(cookieNames[stateName]);

                if (['customerLatitude', 'customerLongitude'].includes(stateName) && value) {
                    const coordinate = Number(value);
                    const limit = stateName === 'customerLatitude' ? 90 : 180;

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
                    latitude: this.customerLatitude,
                    longitude: this.customerLongitude,
                },
            }));

            this.recalculate();
        },

        formElement() {
            return this.$root.matches('form') ? this.$root : this.$root.querySelector('form');
        },

        syncCustomerFields() {
            const form = this.formElement();
            const fields = {
                customerName: 'name',
                customerPhone: 'phone',
                customerAddress: 'address',
                customerLatitude: 'latitude',
                customerLongitude: 'longitude',
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
                customer_lat: this.customerLatitude,
                customer_lng: this.customerLongitude,
            };

            Object.entries(values).forEach(([name, value]) => {
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `${name}=${encodeURIComponent(value || '')}; Max-Age=2592000; Path=/; SameSite=Lax${secure}`;
            });
        },

        recalculate() {
            this.syncCustomerFields();
            const latitude = Number(this.customerLatitude);
            const longitude = Number(this.customerLongitude);
            const restaurantLatitude = Number(configuration.restaurantLatitude);
            const restaurantLongitude = Number(configuration.restaurantLongitude);
            const hasCustomerLocation = this.customerLatitude !== '' && this.customerLongitude !== ''
                && Number.isFinite(latitude) && Number.isFinite(longitude);
            const hasRestaurantLocation = configuration.restaurantLatitude !== null
                && configuration.restaurantLongitude !== null
                && Number.isFinite(restaurantLatitude) && Number.isFinite(restaurantLongitude);
            const isDineIn = configuration.isDineIn || this.deliveryType === 'dine_in';

            this.geofenceError = '';
            this.distanceKm = null;

            if (isDineIn) {
                this.deliveryFee = 0;
            } else if (hasCustomerLocation && hasRestaurantLocation) {
                const latitudeDifference = this.toRadians(latitude - restaurantLatitude);
                const longitudeDifference = this.toRadians(longitude - restaurantLongitude);
                const haversine = Math.sin(latitudeDifference / 2) ** 2
                    + Math.cos(this.toRadians(restaurantLatitude))
                    * Math.cos(this.toRadians(latitude))
                    * Math.sin(longitudeDifference / 2) ** 2;
                const boundedHaversine = Math.min(1, Math.max(0, haversine));

                this.distanceKm = 6371 * 2 * Math.atan2(
                    Math.sqrt(boundedHaversine),
                    Math.sqrt(1 - boundedHaversine),
                );
                const extraKilometers = Math.max(0, this.distanceKm - Number(configuration.baseDeliveryKilometers));

                this.deliveryFee = Math.round((
                    Number(configuration.baseDeliveryFee)
                    + (extraKilometers * Number(configuration.feePerAdditionalKilometer))
                ) * 100) / 100;

                if (this.distanceKm > Number(configuration.deliveryRadius)) {
                    this.geofenceError = `نعتذر، موقعك يبعد ${this.distanceKm.toFixed(2)} كم وهو خارج نطاق التوصيل المتاح لهذا المطعم (الحد الأقصى ${Number(configuration.deliveryRadius).toFixed(2)} كم)`;
                }
            } else {
                this.deliveryFee = hasRestaurantLocation
                    ? Number(configuration.baseDeliveryFee)
                    : Number(configuration.legacyFee);
            }

            this.deliveryFeeDisplay = `${this.deliveryFee.toFixed(2)} ${configuration.currency}`;
            const total = Number(configuration.subtotal)
                + Number(configuration.tax)
                + this.deliveryFee
                - Number(configuration.offerDiscount)
                - this.couponDiscount;
            this.totalDisplay = `${total.toFixed(2)} ${configuration.currency}`;

            const form = this.formElement();
            if (form) {
                form.dataset.outOfRange = this.geofenceError ? 'true' : 'false';
            }
        },

        toRadians(degrees) {
            return degrees * Math.PI / 180;
        },

        formatDistance() {
            return this.distanceKm === null ? '' : `${this.distanceKm.toFixed(2)} كم`;
        },

        validateSubmission(event) {
            this.persistCustomer();
            this.recalculate();

            if (this.geofenceError) {
                event.preventDefault();
                this.$refs.geofenceAlert?.scrollIntoView({ behavior: 'smooth', block: 'center' });

                return;
            }

            this.processing = true;
        },

        setCouponDiscount(discount) {
            this.couponDiscount = Number(discount) || 0;
            this.recalculate();
        },
    }));
});
