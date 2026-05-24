<template>
    <section class="py-12 bg-gray-50 min-h-screen">
        <div class="container max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="text-center mb-10">
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-3 tracking-tight">Track Your Order</h1>
                <p class="text-gray-500 max-w-lg mx-auto">Enter your Order ID and the Phone Number or Email associated with the order to see its current status.</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 mb-8" v-if="!orderDetails">
                <form @submit.prevent="submitTracking">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Order ID <span class="text-red-500">*</span></label>
                            <input v-model="form.order_serial_no" type="text" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="e.g. 123456789" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Phone or Email <span class="text-red-500">*</span></label>
                            <input v-model="form.phone_or_email" type="text" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" placeholder="Enter Phone or Email" required>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button type="submit" :disabled="loading" class="px-8 py-3.5 bg-primary text-white rounded-full font-black text-lg shadow-[0_8px_16px_rgba(var(--primary-color-rgb),0.3)] hover:shadow-[0_12px_24px_rgba(var(--primary-color-rgb),0.4)] hover:-translate-y-1 transition-all duration-300 flex items-center gap-2">
                            <span v-if="!loading">Track Order</span>
                            <span v-else>
                                <i class="fa-solid fa-spinner fa-spin"></i> Tracking...
                            </span>
                            <i v-if="!loading" class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Card -->
            <div v-else class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10">
                <div class="flex justify-between items-center border-b border-gray-100 pb-6 mb-8">
                    <div>
                        <button @click="resetForm" class="text-sm font-bold text-gray-500 hover:text-primary transition-colors flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Track Another Order
                        </button>
                        <h2 class="text-2xl font-black text-gray-900">Order #{{ orderDetails.order_serial_no }}</h2>
                        <p class="text-sm text-gray-500 mt-1">Placed on {{ orderDetails.order_datetime || orderDetails.order_date }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-black"
                            :class="getStatusColor(orderDetails.status)">
                            {{ getStatusText(orderDetails.status) }}
                        </span>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div v-if="orderTimeline.length > 0" class="mb-10">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Order Timeline</h3>
                    <div class="relative pl-6 border-l-2 border-gray-100 space-y-8">
                        <div v-for="(timeline, index) in orderTimeline" :key="index" class="relative">
                            <div class="absolute -left-[35px] top-1 h-6 w-6 rounded-full border-4 border-white flex items-center justify-center"
                                :class="timeline.status === orderDetails.status ? 'bg-primary' : 'bg-gray-300'">
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ getStatusText(timeline.status) }}</p>
                                <p class="text-sm text-gray-500">{{ timeline.date }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items Summary -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-6 border-b border-gray-100 pb-2">Order Summary</h3>

                    <div v-if="orderProducts.length === 0" class="text-sm text-gray-500 py-4">
                        No products found for this order.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(item, index) in orderProducts" :key="item.id || index" class="flex gap-4 items-center">
                            <img :src="item.product_image || placeholderImage" :alt="item.product_name" class="w-16 h-16 object-cover rounded-xl border border-gray-100 bg-gray-50">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 line-clamp-2">{{ item.product_name }}</p>
                                <p class="text-sm text-gray-500" v-if="item.variation_names">{{ item.variation_names }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-black text-gray-900">{{ item.total_currency_price }}</p>
                                <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-gray-100 space-y-3 max-w-sm ml-auto">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Subtotal</span>
                            <span class="font-bold text-gray-900">{{ orderDetails.subtotal_currency_price }}</span>
                        </div>
                        <div class="flex justify-between text-sm" v-if="orderDetails.discount_currency_price && orderDetails.discount_currency_price !== '$0.00' && orderDetails.discount_currency_price !== '0.00'">
                            <span class="text-gray-500 font-medium">Discount</span>
                            <span class="font-bold text-red-500">-{{ orderDetails.discount_currency_price }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-medium">Shipping</span>
                            <span class="font-bold text-gray-900">{{ orderDetails.shipping_charge_currency_price }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <span class="text-base font-black text-gray-900">Total</span>
                            <span class="text-xl font-black text-primary">{{ orderDetails.total_currency_price }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import axios from 'axios';
import alertService from '../../../services/alertService';
import orderStatusEnum from '../../../enums/modules/orderStatusEnum';

export default {
    name: "TrackOrderComponent",
    data() {
        return {
            loading: false,
            form: {
                order_serial_no: "",
                phone_or_email: ""
            },
            orderDetails: null,
            placeholderImage: '/images/default/product/thumb.png',
        }
    },
    computed: {
        orderProducts() {
            if (!this.orderDetails) {
                return [];
            }
            const items = this.orderDetails.products || this.orderDetails.order_products || [];
            return Array.isArray(items) ? items : [];
        },
        orderTimeline() {
            if (!this.orderDetails || !this.orderDetails.order_timeline) {
                return [];
            }
            return Array.isArray(this.orderDetails.order_timeline) ? this.orderDetails.order_timeline : [];
        },
    },
    methods: {
        submitTracking() {
            this.loading = true;
            axios.post('/frontend/order/track-order', this.form).then((res) => {
                this.orderDetails = res.data?.data || res.data;
                this.loading = false;
            }).catch((err) => {
                this.loading = false;
                alertService.error(err.response?.data?.message || 'Order not found. Please verify your details.');
            });
        },
        resetForm() {
            this.orderDetails = null;
            this.form.order_serial_no = "";
            this.form.phone_or_email = "";
        },
        getStatusColor(status) {
            const statusMap = {
                [orderStatusEnum.PENDING]: 'bg-yellow-100 text-yellow-700',
                [orderStatusEnum.CONFIRMED]: 'bg-blue-100 text-blue-700',
                [orderStatusEnum.ON_THE_WAY]: 'bg-indigo-100 text-indigo-700',
                [orderStatusEnum.DELIVERED]: 'bg-green-100 text-green-700',
                [orderStatusEnum.CANCELED]: 'bg-red-100 text-red-700',
                [orderStatusEnum.REJECTED]: 'bg-red-100 text-red-700',
            };
            return statusMap[status] || 'bg-gray-100 text-gray-700';
        },
        getStatusText(status) {
            const textMap = {
                [orderStatusEnum.PENDING]: this.$t('label.pending'),
                [orderStatusEnum.CONFIRMED]: this.$t('label.confirmed'),
                [orderStatusEnum.ON_THE_WAY]: this.$t('label.on_the_way'),
                [orderStatusEnum.DELIVERED]: this.$t('label.delivered'),
                [orderStatusEnum.CANCELED]: this.$t('label.canceled'),
                [orderStatusEnum.REJECTED]: this.$t('label.rejected'),
            };
            return textMap[status] || 'Unknown';
        }
    }
}
</script>
