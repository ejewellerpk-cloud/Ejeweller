<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="order-list" :skeleton-count="1" />

    <div v-else class="order-details mobile:mb-24">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-5">
            <button
                type="button"
                @click.prevent="$router.back()"
                class="w-10 h-10 rounded-xl border border-gray-200 bg-white text-heading flex items-center justify-center shadow-sm hover:bg-slate-50 transition"
                :aria-label="$t('button.back') || 'Back'"
            >
                <i class="lab-line-undo text-lg"></i>
            </button>
            <div class="min-w-0 flex-1">
                <h2 class="capitalize text-xl sm:text-2xl font-bold text-heading tracking-tight">{{ $t('label.order_details') }}</h2>
                <p class="text-xs sm:text-sm text-text truncate">#{{ order.order_serial_no }} · {{ order.order_datetime }}</p>
            </div>
            <router-link
                :to="{ name: 'frontend.account.orderHistory' }"
                class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-heading hover:border-primary hover:text-primary transition"
            >
                {{ $t('label.order_history') }}
            </router-link>
        </div>

        <!-- Status hero -->
        <div class="rounded-2xl border border-primary/15 bg-gradient-to-br from-[#FFF4F1] via-white to-[#F0F7FF] p-4 sm:p-6 mb-4 shadow-[0_2px_16px_rgba(15,23,42,0.05)]">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-primary/70 mb-1.5">{{ $t('label.order_status') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize" :class="orderStatusClass(order.status)">
                            {{ enums.orderStatusEnumArray[order.status] }}
                        </span>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                            :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#0F7A3F] bg-[#D9F7E6]' : 'text-[#B42318] bg-[#FEE4E2]'"
                        >
                            {{ enums.paymentStatusEnumArray[order.payment_status] }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize bg-[#E8F1FF] text-[#175CD3]">
                            {{ enums.orderTypeEnumArray[order.order_type] }}
                        </span>
                    </div>
                </div>
                <div class="text-left sm:text-right rounded-xl bg-white/80 border border-primary/10 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">{{ $t('label.total') }}</p>
                    <p class="text-2xl font-extrabold text-heading">{{ order.total_currency_price }}</p>
                    <p class="mt-0.5 text-xs text-text">{{ orderProducts.length }} {{ $t('label.product') }}</p>
                </div>
            </div>

            <div
                v-if="showTrackingId"
                class="mb-5 rounded-2xl border border-[#B2DDFF] bg-gradient-to-r from-[#EFF8FF] to-white p-4 sm:p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-[#175CD3] mb-1">
                            {{ $t('label.shipment') }} · {{ $t('label.tracking_no') }}
                        </p>
                        <p class="text-lg sm:text-xl font-extrabold text-heading break-all tracking-wide">
                            {{ order.postex_tracking_number }}
                        </p>
                        <p v-if="order.postex_status" class="mt-1 text-xs text-text">
                            {{ $t('label.status') }}: <span class="font-semibold text-heading capitalize">{{ order.postex_status }}</span>
                            <span v-if="order.postex_booked_at"> · {{ order.postex_booked_at }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white border border-[#B2DDFF] px-3.5 py-2 text-xs font-bold text-[#175CD3] hover:bg-[#175CD3] hover:text-white transition"
                            @click="copyTrackingId"
                        >
                            <i class="fa-regular fa-copy"></i>
                            {{ $t('button.copy') || 'Copy' }}
                        </button>
                        <router-link
                            :to="{ name: 'frontend.trackOrder' }"
                            class="inline-flex items-center gap-1.5 rounded-full bg-[#175CD3] px-3.5 py-2 text-xs font-bold text-white hover:opacity-90 transition"
                        >
                            <i class="fa-solid fa-location-dot"></i>
                            {{ $t('label.track_order') }}
                        </router-link>
                    </div>
                </div>
            </div>

            <div
                v-if="order.status !== enums.orderStatusEnum.CANCELED && order.status !== enums.orderStatusEnum.REJECTED && order.order_type !== enums.orderTypeEnum.PICK_UP"
                class="pt-2 pb-10"
            >
                <ul class="w-full flex items-center justify-center">
                    <li
                        v-for="(track, index) in tracks"
                        :key="'track-' + index"
                        class="w-full flex items-center justify-center gap-1 relative"
                    >
                        <hr :class="{ '!bg-success': track.step <= order.status }" class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <i
                            :class="{ 'lab-fill-save !bg-success text-white': track.step <= order.status }"
                            class="flex-shrink-0 w-7 h-7 !leading-7 text-center rounded-full bg-gray-200 lab-font-size-16"
                        />
                        <hr :class="{ '!bg-success': track.step <= order.status }" class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <span class="absolute top-9 left-1/2 -translate-x-1/2 w-16 sm:w-20 text-[10px] sm:text-xs leading-[14px] text-center capitalize text-text">
                            {{ track.title }}
                        </span>
                    </li>
                </ul>
            </div>

            <div
                v-if="order.status !== enums.orderStatusEnum.CANCELED && order.status !== enums.orderStatusEnum.REJECTED && order.order_type === enums.orderTypeEnum.PICK_UP"
                class="pt-2 pb-10"
            >
                <ul class="w-full flex items-center justify-center">
                    <li
                        v-for="(track, index) in pickupTracks"
                        :key="'pickup-' + index"
                        class="w-full flex items-center justify-center gap-1 relative"
                    >
                        <hr :class="{ '!bg-success': track.step <= order.status }" class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <i
                            :class="{ 'lab-fill-save !bg-success text-white': track.step <= order.status }"
                            class="flex-shrink-0 w-7 h-7 !leading-7 text-center rounded-full bg-gray-200 lab-font-size-16"
                        />
                        <hr :class="{ '!bg-success': track.step <= order.status }" class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <span class="absolute top-9 left-1/2 -translate-x-1/2 w-16 sm:w-20 text-[10px] sm:text-xs leading-[14px] text-center capitalize text-text">
                            {{ track.title }}
                        </span>
                    </li>
                </ul>
            </div>

            <div
                v-if="order.status === enums.orderStatusEnum.CANCELED || order.status === enums.orderStatusEnum.REJECTED"
                class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-red-200 bg-red-50 text-red-600"
            >
                <i class="lab-fill-close-circle text-lg"></i>
                <span class="text-sm font-bold capitalize">
                    {{ order.status === enums.orderStatusEnum.CANCELED ? $t('label.order_cancelled') : $t('label.order_rejected') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-7 space-y-4">
                <!-- Products -->
                <section class="rounded-2xl border border-[#C7D7FE] bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-[#C7D7FE] bg-gradient-to-r from-[#EEF4FF] to-[#F8FAFF] flex items-center justify-between">
                        <h3 class="text-sm font-bold text-[#3538CD] capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#3538CD] text-white text-xs">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </span>
                            {{ $t('label.order_summary') }}
                        </h3>
                        <span class="text-xs font-semibold text-[#3538CD]/bg-[#EEF4FF] px-2.5 py-1 rounded-full">
                            {{ orderProducts.length }} {{ $t('label.product') }}
                        </span>
                    </div>

                    <ul class="divide-y divide-indigo-50">
                        <li
                            v-for="(orderProduct, productIndex) in orderProducts"
                            :key="'product-' + productIndex"
                            class="p-4 sm:p-5 flex gap-3.5"
                        >
                            <router-link
                                v-if="orderProduct.product_slug"
                                :to="{ name: 'frontend.product.details', params: { slug: orderProduct.product_slug } }"
                                class="shrink-0"
                            >
                                <img
                                    :src="orderProduct.product_image"
                                    alt="product"
                                    class="w-16 h-16 sm:w-[76px] sm:h-[76px] object-cover rounded-xl border border-indigo-100 bg-slate-50"
                                />
                            </router-link>
                            <img
                                v-else
                                :src="orderProduct.product_image"
                                alt="product"
                                class="w-16 h-16 sm:w-[76px] sm:h-[76px] object-cover rounded-xl border border-indigo-100 bg-slate-50 shrink-0"
                            />

                            <div class="min-w-0 flex-1">
                                <router-link
                                    v-if="orderProduct.product_slug"
                                    :to="{ name: 'frontend.product.details', params: { slug: orderProduct.product_slug } }"
                                    class="text-sm font-semibold text-heading capitalize line-clamp-2 hover:text-primary transition"
                                >
                                    {{ orderProduct.product_name }}
                                </router-link>
                                <h4 v-else class="text-sm font-semibold text-heading capitalize line-clamp-2">{{ orderProduct.product_name }}</h4>
                                <p v-if="orderProduct.variation_names" class="mt-0.5 text-xs text-text line-clamp-2">{{ orderProduct.variation_names }}</p>
                                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="font-bold text-heading">{{ orderProduct.subtotal_currency_price }}</span>
                                        <span class="text-text">× {{ orderProduct.quantity }}</span>
                                        <span v-if="orderProduct.currency_price" class="text-[11px] text-slate-400">{{ orderProduct.currency_price }} {{ $t('label.each') || 'each' }}</span>
                                    </div>
                                    <router-link
                                        v-if="order.status === enums.orderStatusEnum.DELIVERED && !orderProduct.product_user_review"
                                        :to="{ name: 'frontend.account.productReview', params: { slug: orderProduct.product_slug } }"
                                        class="text-xs font-semibold capitalize py-1.5 px-3 rounded-full whitespace-nowrap bg-[#FFF4F1] text-primary transition hover:bg-primary hover:text-white"
                                    >
                                        {{ $t('button.write_review') }}
                                    </router-link>
                                    <router-link
                                        v-if="order.status === enums.orderStatusEnum.DELIVERED && orderProduct.product_user_review"
                                        :to="{ name: 'frontend.account.productReview.edit', params: { slug: orderProduct.product_slug, id: orderProduct.product_user_review_id } }"
                                        class="text-xs font-semibold capitalize py-1.5 px-3 rounded-full whitespace-nowrap bg-[#FFF4F1] text-primary transition hover:bg-primary hover:text-white"
                                    >
                                        {{ $t('button.edit_review') }}
                                    </router-link>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="border-t border-dashed border-indigo-100 px-4 sm:px-5 py-4 space-y-2.5 bg-[#F8FAFF]/50">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.subtotal') }}</span>
                            <span class="font-semibold text-heading">{{ order.subtotal_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.tax_fee') }}</span>
                            <span class="font-semibold text-heading">{{ order.tax_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.shipping_charge') }}</span>
                            <span class="font-semibold text-heading">{{ order.shipping_charge_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.discount') }}</span>
                            <span class="font-semibold text-heading">{{ order.discount_currency_price }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-4 sm:px-5 py-4 bg-gradient-to-r from-[#ECFDF3] to-[#F0FDF4] border-t border-[#ABEFC6]">
                        <span class="capitalize font-bold text-[#067647]">{{ $t('label.total') }}</span>
                        <span class="capitalize font-extrabold text-lg text-[#067647]">{{ order.total_currency_price }}</span>
                    </div>
                </section>

                <!-- Timeline -->
                <section
                    v-if="orderTimeline.length > 0"
                    class="rounded-2xl border border-[#E9D7FE] bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <div class="px-4 sm:px-5 py-3.5 border-b border-[#E9D7FE] bg-gradient-to-r from-[#F9F5FF] to-white">
                        <h3 class="text-sm font-bold text-[#6941C6] capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#7F56D9] text-white text-xs">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                            {{ $t('label.order_timeline') || 'Order Timeline' }}
                        </h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div class="relative pl-5 border-l-2 border-[#E9D7FE] space-y-5">
                            <div v-for="(item, index) in orderTimeline" :key="'timeline-' + index" class="relative">
                                <div
                                    class="absolute -left-[27px] top-0.5 h-4 w-4 rounded-full border-2 border-white"
                                    :class="parseInt(item.status, 10) === parseInt(order.status, 10) ? 'bg-[#7F56D9]' : 'bg-slate-300'"
                                ></div>
                                <p class="text-sm font-semibold text-heading capitalize">
                                    {{ enums.orderStatusEnumArray[item.status] || item.label || item.status }}
                                </p>
                                <p class="text-xs text-text mt-0.5">{{ item.date }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Note -->
                <section
                    v-if="order.note"
                    class="rounded-2xl border border-[#F9DBAF] bg-gradient-to-br from-[#FFFAEB] to-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <div class="px-4 sm:px-5 py-3.5 border-b border-[#F9DBAF] bg-[#FFFAEB]">
                        <h3 class="text-sm font-bold text-[#B54708] capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#DC6803] text-white text-xs">
                                <i class="fa-regular fa-note-sticky"></i>
                            </span>
                            {{ $t('label.note') }}
                        </h3>
                    </div>
                    <p class="p-4 sm:p-5 text-sm text-text leading-relaxed whitespace-pre-line">{{ order.note }}</p>
                </section>
            </div>

            <div class="xl:col-span-5 space-y-4">
                <!-- Order info -->
                <section class="rounded-2xl border border-[#FEC84B]/60 bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-[#FEC84B]/50 bg-gradient-to-r from-[#FFFAEB] to-white">
                        <h3 class="text-sm font-bold text-[#B54708] capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#F79009] text-white text-xs">
                                <i class="fa-solid fa-receipt"></i>
                            </span>
                            {{ $t('label.order_information') }}
                        </h3>
                    </div>
                    <ul class="p-4 sm:p-5 space-y-3">
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_id') }}</span>
                            <span class="text-sm font-semibold text-heading text-right">#{{ order.order_serial_no }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_date') }}</span>
                            <span class="text-sm font-medium text-heading text-right">{{ order.order_datetime }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_type') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ enums.orderTypeEnumArray[order.order_type] }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.payment_method') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ order.payment_method_name }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.payment_status') }}</span>
                            <span
                                class="text-sm font-semibold text-right capitalize"
                                :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#067647]' : 'text-[#B42318]'"
                            >
                                {{ enums.paymentStatusEnumArray[order.payment_status] }}
                            </span>
                        </li>
                        <li v-if="showTrackingId" class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.tracking_no') }}</span>
                            <span class="text-sm font-semibold text-heading text-right break-all">{{ order.postex_tracking_number }}</span>
                        </li>
                        <li v-if="order.status === enums.orderStatusEnum.REJECTED && order.reason" class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.reason') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ order.reason }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Addresses (merged when same) -->
                <template v-if="order.order_type === enums.orderTypeEnum.DELIVERY && displayAddresses.length > 0">
                    <section
                        v-for="(entry, addressIndex) in displayAddresses"
                        :key="'addr-' + addressIndex"
                        class="rounded-2xl overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                        :class="addressSectionClass(entry.kind)"
                    >
                        <div
                            class="px-4 py-3 border-b flex items-center justify-between gap-2"
                            :class="addressHeaderClass(entry.kind)"
                        >
                            <h3 class="text-sm font-bold capitalize flex items-center gap-2" :class="addressTitleClass(entry.kind)">
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-white text-xs"
                                    :class="addressIconBg(entry.kind)"
                                >
                                    <i :class="entry.kind === 'billing' ? 'fa-regular fa-credit-card' : 'fa-solid fa-truck'"></i>
                                </span>
                                {{ addressTitle(entry.kind) }}
                            </h3>
                            <span
                                v-if="entry.kind === 'both'"
                                class="text-[10px] font-bold uppercase tracking-wide rounded-full px-2 py-0.5 bg-white/80 text-[#067647]"
                            >
                                {{ $t('label.same_as_billing') }}
                            </span>
                        </div>
                        <ul class="p-4 space-y-2.5 bg-white">
                            <li class="text-sm">
                                <span class="text-text">{{ $t('label.name') }}:</span>
                                <span class="font-semibold capitalize text-heading"> {{ entry.address.full_name }}</span>
                            </li>
                            <li class="text-sm">
                                <span class="text-text">{{ $t('label.phone') }}:</span>
                                <span class="font-medium text-heading" dir="ltr"> {{ entry.address.country_code }}{{ entry.address.phone }}</span>
                            </li>
                            <li v-if="entry.address.email" class="text-sm">
                                <span class="text-text">{{ $t('label.email') }}:</span>
                                <span class="font-medium text-heading break-all"> {{ entry.address.email }}</span>
                            </li>
                            <li class="text-sm leading-relaxed">
                                <span class="text-text">{{ $t('label.address') }}:</span>
                                <span class="font-medium capitalize text-heading">
                                    <span v-if="entry.address.address"> {{ entry.address.address }}</span>
                                    <span class="block" :class="entry.address.address ? 'mt-1' : ''">
                                        <span v-if="entry.address.city">{{ entry.address.city }}, </span>
                                        <span v-if="entry.address.state">{{ entry.address.state }}, </span>
                                        <span v-if="entry.address.country">{{ entry.address.country }}, </span>
                                        <span v-if="entry.address.zip_code">{{ entry.address.zip_code }}</span>
                                    </span>
                                </span>
                            </li>
                        </ul>
                    </section>
                </template>

                <section
                    v-if="order.order_type === enums.orderTypeEnum.PICK_UP && Object.keys(outletAddress).length > 0"
                    class="rounded-2xl border border-[#A5F0FC] bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <div class="px-4 py-3 border-b border-[#A5F0FC] bg-gradient-to-r from-[#ECFEFF] to-white">
                        <h3 class="text-sm font-bold text-[#0E7490] capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#0891B2] text-white text-xs">
                                <i class="fa-solid fa-store"></i>
                            </span>
                            {{ $t('label.pick_up_address') }}
                        </h3>
                    </div>
                    <ul class="p-4 space-y-2.5">
                        <li class="text-sm"><span class="text-text">{{ $t('label.name') }}:</span> <span class="font-semibold capitalize text-heading">{{ outletAddress.name }}</span></li>
                        <li v-if="outletAddress.phone" class="text-sm"><span class="text-text">{{ $t('label.phone') }}:</span> <span class="font-medium text-heading" dir="ltr">{{ outletAddress.country_code }}{{ outletAddress.phone }}</span></li>
                        <li v-if="outletAddress.email" class="text-sm"><span class="text-text">{{ $t('label.email') }}:</span> <span class="font-medium text-heading break-all">{{ outletAddress.email }}</span></li>
                        <li class="text-sm leading-relaxed">
                            <span class="text-text">{{ $t('label.address') }}:</span>
                            <span class="font-medium capitalize text-heading">
                                <span v-if="outletAddress.address">{{ outletAddress.address }}</span>
                                <span class="block" :class="outletAddress.address ? 'mt-1' : ''">
                                    <span v-if="outletAddress.city">{{ outletAddress.city }}, </span>
                                    <span v-if="outletAddress.state">{{ outletAddress.state }}, </span>
                                    <span v-if="outletAddress.zip_code">{{ outletAddress.zip_code }}</span>
                                </span>
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- Help -->
                <section
                    v-if="setting.company_email || setting.company_phone || setting.whatsapp_number"
                    class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-slate-100/80">
                        <h3 class="text-sm font-bold text-slate-700 capitalize flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-700 text-white text-xs">
                                <i class="fa-regular fa-life-ring"></i>
                            </span>
                            {{ $t('label.need_help') || 'Need help?' }}
                        </h3>
                    </div>
                    <div class="p-4 sm:p-5">
                        <p class="text-xs text-text mb-3">{{ $t('message.order_help_hint') || 'Questions about this order? Contact us.' }}</p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                v-if="setting.company_email"
                                :href="'mailto:' + setting.company_email"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-heading hover:border-primary hover:text-primary transition"
                            >
                                <i class="fa-regular fa-envelope"></i>
                                {{ $t('label.email') }}
                            </a>
                            <a
                                v-if="setting.company_phone"
                                :href="'tel:' + (setting.company_calling_code || '') + setting.company_phone"
                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-heading hover:border-primary hover:text-primary transition"
                            >
                                <i class="fa-solid fa-phone"></i>
                                {{ $t('label.phone') }}
                            </a>
                            <a
                                v-if="setting.whatsapp_number"
                                :href="'https://api.whatsapp.com/send?phone=' + (setting.whatsapp_calling_code || '') + setting.whatsapp_number"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition"
                            >
                                <i class="fa-brands fa-whatsapp"></i>
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <OrderReceiptComponent
                v-if="order.status !== enums.orderStatusEnum.CANCELED"
                :order="order"
                :orderProducts="orderProducts"
                :orderUser="orderUser"
                :orderAddress="orderAddress[0]"
            />
            <button
                v-if="order.status === enums.orderStatusEnum.PENDING"
                @click="changeStatus(enums.orderStatusEnum.CANCELED)"
                type="button"
                class="px-5 py-3 capitalize rounded-full whitespace-nowrap text-center text-sm font-semibold border border-danger text-danger bg-white hover:bg-red-50 transition"
            >
                {{ $t('button.cancel_order') }}
            </button>
            <router-link
                v-if="order.status === enums.orderStatusEnum.DELIVERED && order.return_and_refund"
                :to="{ name: 'frontend.account.returnOrder.request', params: { id: order.id } }"
                class="px-5 py-3 capitalize rounded-full whitespace-nowrap text-center text-sm font-semibold border border-danger text-danger bg-white hover:bg-red-50 transition"
            >
                {{ $t('button.return_request') }}
            </router-link>
            <router-link
                :to="{ name: 'frontend.product' }"
                class="px-5 py-3 capitalize rounded-full whitespace-nowrap text-center text-sm font-semibold border border-gray-200 bg-white text-heading hover:border-primary hover:text-primary transition"
            >
                {{ $t('button.continue_shopping') || 'Continue Shopping' }}
            </router-link>
        </div>
    </div>

    <div
        id="payment-modal"
        :class="Object.keys(route.query).length > 0 && route.query.status === 'success' && cart.length > 0 && modal ? 'modal-active' : ''"
        class=" fixed inset-0 z-50 p-3 w-screen h-dvh overflow-y-auto bg-black/50 transition-all duration-300 opacity-0 invisible"
    >
        <div class="w-full rounded-xl mx-auto bg-white transition-all duration-300 max-w-[360px]">
            <div class="px-4 py-5 relative">
                <button @click.prevent="reset" type="button" class="lab-line-circle-cross text-lg text-[#E93C3C] absolute top-3 right-3"></button>
                <h3 class="font-medium text-center mb-5">{{ $t('message.thank_you_for_your_order') }}</h3>
                <img :src="setting.image_confirm" alt="confirm-image" class="w-[120px] mx-auto mb-5" />
                <h4 class="font-semibold text-center mb-5">{{ $t('message.your_order_is_successfully_placed') }}</h4>
                <button type="button" @click.prevent="reset" class="field-button font-semibold normal-case">
                    {{ $t('button.see_your_order_details') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import orderStatusEnum from "../../../../enums/modules/orderStatusEnum";
import paymentStatusEnum from "../../../../enums/modules/paymentStatusEnum";
import addressTypeEnum from "../../../../enums/modules/addressTypeEnum";
import appService from "../../../../services/appService";
import alertService from "../../../../services/alertService";
import targetService from "../../../../services/targetService";
import { useRoute } from 'vue-router'
import OrderReceiptComponent from "./OrderReceiptComponent";
import { trackOrderCompletedOnce } from "../../../../services/analyticsEcommerceBridge";
import { pixelService } from "../../../../services/pixelService";
import orderTypeEnum from "../../../../enums/modules/orderTypeEnum";
import activityEnum from "../../../../enums/modules/activityEnum";
import env from "../../../../config/env";
import {
    addressKindTitleKey,
    getDisplayOrderAddresses,
} from "../../../../utils/orderAddresses";

export default {
    name: "OrderDetailsComponent",
    components: { LoadingComponent, OrderReceiptComponent },
    setup() {
        const route = useRoute();
        return { route: route };
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            tracks: [
                { step: 1, title: this.$t('label.order_pending') },
                { step: 5, title: this.$t('label.order_confirmed') },
                { step: 7, title: this.$t('label.order_on_the_way') },
                { step: 10, title: this.$t('label.order_delivered') },
            ],
            pickupTracks: [
                { step: 1, title: this.$t('label.order_pending') },
                { step: 5, title: this.$t('label.order_confirmed') },
                { step: 10, title: this.$t('label.order_delivered') },
            ],
            enums: {
                orderStatusEnum: orderStatusEnum,
                paymentStatusEnum: paymentStatusEnum,
                addressTypeEnum: addressTypeEnum,
                orderTypeEnum: orderTypeEnum,
                activityEnum: activityEnum,
                orderStatusEnumArray: {
                    [orderStatusEnum.PENDING]: this.$t("label.pending"),
                    [orderStatusEnum.CONFIRMED]: this.$t("label.confirmed"),
                    [orderStatusEnum.ON_THE_WAY]: this.$t("label.on_the_way"),
                    [orderStatusEnum.DELIVERED]: this.$t("label.delivered"),
                    [orderStatusEnum.CANCELED]: this.$t("label.canceled"),
                    [orderStatusEnum.REJECTED]: this.$t("label.rejected"),
                },
                paymentStatusEnumArray: {
                    [paymentStatusEnum.PAID]: this.$t("label.paid"),
                    [paymentStatusEnum.UNPAID]: this.$t("label.unpaid")
                },
                orderTypeEnumArray: {
                    [orderTypeEnum.DELIVERY]: this.$t("label.delivery"),
                    [orderTypeEnum.PICK_UP]: this.$t("label.pick_up")
                }
            },
            modal: false

        };
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        order: function () {
            return this.$store.getters['frontendOrder/show'];
        },
        orderProducts: function () {
            return this.$store.getters['frontendOrder/orderProducts'];
        },
        orderUser: function () {
            return this.$store.getters['frontendOrder/orderUser'];
        },
        orderAddress: function () {
            return this.$store.getters['frontendOrder/orderAddress'];
        },
        displayAddresses: function () {
            return getDisplayOrderAddresses(this.orderAddress, addressTypeEnum);
        },
        outletAddress: function () {
            return this.$store.getters['frontendOrder/outletAddress'];
        },
        cart: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        paymentMethod: function () {
            return this.$store.getters['frontendCart/paymentMethod'];
        },
        orderTimeline: function () {
            const timeline = this.order?.order_timeline;
            return Array.isArray(timeline) ? timeline : [];
        },
        showTrackingId: function () {
            const tracking = String(this.order?.postex_tracking_number || '').trim();
            if (!tracking) {
                return false;
            }
            const status = parseInt(this.order?.status, 10);
            return status === orderStatusEnum.CONFIRMED
                || status === orderStatusEnum.ON_THE_WAY
                || status === orderStatusEnum.DELIVERED;
        },
    },
    mounted() {
        if (this.$route.params.id) {
            this.loading.isActive = true;
            this.$store.dispatch("frontendOrder/show", this.$route.params.id).then(res => {
                if (this.$route.query.status === "success") {
                    pixelService.trackPurchase(res.data.data);
                    trackOrderCompletedOnce({
                        id: res.data.data.id,
                        total: res.data.data.total,
                        currency_code: res.data.data.currency_code,
                    });
                }

                if (this.$route.query.status === "success" && this.setting.whatsapp_status === activityEnum.ENABLE && this.setting.whatsapp_checkout_status === activityEnum.ENABLE && this.setting.whatsapp_number) {
                    this.modal = false;
                    this.handleClick(res.data.data, res.data.data.outlet_address);
                } else {
                    this.modal = true;
                }
                this.loading.isActive = false;
            }).catch((error) => {
                this.loading.isActive = false;
            });
        }
    },
    methods: {
        addressTitle: function (kind) {
            return this.$t(addressKindTitleKey(kind));
        },
        addressSectionClass: function (kind) {
            if (kind === 'both') return 'border border-[#ABEFC6]';
            if (kind === 'billing') return 'border border-[#F9DBAF]';
            return 'border border-[#A5F0FC]';
        },
        addressHeaderClass: function (kind) {
            if (kind === 'both') return 'border-[#ABEFC6] bg-gradient-to-r from-[#ECFDF3] to-white';
            if (kind === 'billing') return 'border-[#F9DBAF] bg-gradient-to-r from-[#FFFAEB] to-white';
            return 'border-[#A5F0FC] bg-gradient-to-r from-[#ECFEFF] to-white';
        },
        addressTitleClass: function (kind) {
            if (kind === 'both') return 'text-[#067647]';
            if (kind === 'billing') return 'text-[#B54708]';
            return 'text-[#0E7490]';
        },
        addressIconBg: function (kind) {
            if (kind === 'both') return 'bg-[#12B76A]';
            if (kind === 'billing') return 'bg-[#DC6803]';
            return 'bg-[#0891B2]';
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        copyTrackingId: function () {
            const tracking = String(this.order?.postex_tracking_number || '').trim();
            if (!tracking) {
                return;
            }
            const done = () => alertService.success(this.$t('message.copied') || 'Copied');
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(tracking).then(done).catch(() => {
                    alertService.error(this.$t('message.failed') || 'Failed to copy');
                });
                return;
            }
            try {
                const input = document.createElement('input');
                input.value = tracking;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                done();
            } catch (e) {
                alertService.error(this.$t('message.failed') || 'Failed to copy');
            }
        },
        reset: function () {
            if (this.cart.length > 0 && Object.keys(this.paymentMethod).length > 0 && this.paymentMethod.slug === 'credit') {
                this.$store.dispatch("profile").then().catch();
            }
            this.$store.dispatch("frontendCart/resetCart").then().catch();
        },
        orderStatusClass: function (status) {
            return appService.orderStatusClass(status);
        },
        changeStatus: function (status) {
            appService.cancelOrder().then((res) => {
                try {
                    this.loading.isActive = true;
                    this.$store.dispatch("frontendOrder/changeStatus", {
                        id: this.$route.params.id,
                        status: status,
                    }).then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(
                            1,
                            this.$t("label.status")
                        );
                    }).catch((err) => {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    });
                } catch (err) {
                    this.loading.isActive = false;
                    alertService.error(err.response.data.message);
                }
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        handleClick(order, outletAddress) {
            let text = `
            ${this.setting.company_name}
            ****************************************************
            ${this.$t('label.order_id')}           : #${order.order_serial_no}
            ${this.$t("label.order_type")}         : ${this.enums.orderTypeEnumArray[order.order_type]}
            ${this.$t('label.order_date')}         : ${order.order_date}${order.order_time}
            ${this.$t('label.order_status')}       : ${this.enums.orderStatusEnumArray[order.status]}
            ${this.$t("label.payment_status")}     : ${this.enums.paymentStatusEnumArray[order.payment_status]}
            ${this.$t("label.payment_method")}     : ${order.payment_method_name}

            `;

            text += `
            --------------------------
            ${this.$t('label.order_details')}
            --------------------------
            `;
            for (let i = 0; i < order.order_products.length; i++) {
                const item = order.order_products[i];

                text += `
            ${i + 1})  ${item.product_name}
            `;

                text += `
                --------------------------
                ${this.$t('label.variation')}     : ${item.variation_names}
                ${this.$t('label.price')}         : ${item.currency_price}
                ${this.$t('label.quantity')}      : ${item.quantity}
                ${this.$t('label.discount')}      : ${item.discount_currency_price}
                --------------------------
                ${this.$t('label.total')}         : ${item.subtotal_currency_price}
                --------------------------
                                `;

            }


            text += `
            ${this.$t('label.subtotal')}             : ${order.subtotal_currency_price}
            ${this.$t('label.tax_fee')}              : ${order.tax_currency_price}
            ${this.$t('label.shipping_charge')}      : ${order.shipping_charge_currency_price}
            ${this.$t('label.discount')}             : ${order.discount_currency_price}
            --------------------------
            ${this.$t('label.total')}                : ${order.total_currency_price}
            --------------------------
            `;
            if (order.order_type === this.enums.orderTypeEnum.DELIVERY) {
                const display = getDisplayOrderAddresses(order.order_address || [], addressTypeEnum);
                for (let i = 0; i < display.length; i++) {
                    const entry = display[i];
                    const address = entry.address;
                    const address_type = this.$t(addressKindTitleKey(entry.kind));
                    text += `
            ${address_type}
            --------------------------
            ${this.$t('label.name')}       : ${address.full_name}
            ${this.$t('label.phone')}      : ${address.country_code}${address.phone}
            ${this.$t('label.email')}      : ${address.email}
            ${this.$t('label.address')}    : ${address.address}
                            ${address.city} ${address.state} ${address.country} ${address.zip_code}
            `;
                }
            }
            else {
                text += `
            ${this.$t('label.pick_up_address')}
            --------------------------
            ${this.$t('label.name')}        : ${outletAddress.name}
            ${this.$t('label.phone')}       : ${outletAddress.country_code}${outletAddress.phone}
            ${this.$t('label.email')}       : ${outletAddress.email}
            ${this.$t('label.address')}     : ${outletAddress.address}
                            ${outletAddress.city} ${outletAddress.state} ${outletAddress.country} ${outletAddress.zip_code}
            `;
            }
            text = encodeURIComponent(text);

            this.reset();

            if (env.DEMO === "true" || env.DEMO === true || env.DEMO === "1" || env.DEMO === 1) {
                window.open("https://api.whatsapp.com/send?phone=" + this.setting.whatsapp_calling_code + '' + this.setting.whatsapp_number + "&text=" + text, "_blank");
            } else {
                window.location = "https://api.whatsapp.com/send?phone=" + this.setting.whatsapp_calling_code + '' + this.setting.whatsapp_number + "&text=" + text;
            }
        }
    },
}
</script>
