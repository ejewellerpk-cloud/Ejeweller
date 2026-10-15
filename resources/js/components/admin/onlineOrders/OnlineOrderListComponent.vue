<template>
    <LoadingComponent :props="loading" />
    <div class="col-12">
        <div class="db-card overflow-hidden">
            <div class="db-card-header border-none">
                <div>
                    <h3 class="db-card-title">{{ $t('menu.online_orders') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">
                        {{ $t('message.bulk_orders_hint') || 'Select orders to confirm, ship, print, mark paid, or delete in one go.' }}
                    </p>
                </div>
                <div class="db-card-filter">
                    <TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
                    <button type="button" class="db-card-filter-btn table-filter-btn" @click="showFilter = !showFilter">
                        <i class="lab lab-line-filter lab-font-size-14"></i>
                        <span>{{ $t("button.filter") }}</span>
                    </button>
                    <div class="dropdown-group">
                        <ExportComponent />
                        <div class="dropdown-list db-card-filter-dropdown-list">
                            <PrintComponent :props="printObj" />
                            <ExcelComponent :method="xls" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky bulk action bar -->
            <div
                v-if="permissionChecker('online-orders') && selectedOrderIds.length > 0"
                class="sticky top-0 z-20 border-y border-emerald-200/80 bg-gradient-to-r from-emerald-50 via-white to-indigo-50 px-3 sm:px-4 py-3 hidden-print"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-600 text-white text-xs font-bold px-3 py-1.5">
                            <i class="fa-solid fa-check-double text-[10px]"></i>
                            {{ selectedOrderIds.length }} {{ $t('label.selected') || 'selected' }}
                        </span>
                        <button
                            type="button"
                            class="text-xs font-semibold text-slate-600 hover:text-primary underline-offset-2 hover:underline"
                            :disabled="bulkBusy"
                            @click="selectAllPage"
                        >
                            {{ $t('button.select_page') || 'Select this page' }}
                        </button>
                        <button
                            type="button"
                            class="text-xs font-semibold text-slate-500 hover:text-danger"
                            :disabled="bulkBusy"
                            @click="clearSelection"
                        >
                            {{ $t('button.clear_selection') || 'Clear' }}
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 hidden md:block">
                        {{ $t('message.bulk_auto_book_note') || 'Confirm can auto-book PostEx if that setting is ON.' }}
                    </p>
                </div>

                <div class="flex flex-col gap-2.5">
                    <!-- Status -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 w-16 shrink-0">{{ $t('label.status') }}</span>
                        <button type="button" class="bulk-btn bg-emerald-600 hover:bg-emerald-700" :disabled="bulkBusy" @click="bulkSetStatus(enums.orderStatusEnum.CONFIRMED)">
                            <i class="fa-solid fa-check"></i> {{ $t('label.confirmed') }}
                        </button>
                        <button type="button" class="bulk-btn bg-sky-600 hover:bg-sky-700" :disabled="bulkBusy" @click="bulkSetStatus(enums.orderStatusEnum.ON_THE_WAY)">
                            <i class="fa-solid fa-truck-fast"></i> {{ $t('label.on_the_way') }}
                        </button>
                        <button type="button" class="bulk-btn bg-violet-600 hover:bg-violet-700" :disabled="bulkBusy" @click="bulkSetStatus(enums.orderStatusEnum.DELIVERED)">
                            <i class="fa-solid fa-box-open"></i> {{ $t('label.delivered') }}
                        </button>
                        <button type="button" class="bulk-btn bg-amber-500 hover:bg-amber-600" :disabled="bulkBusy" @click="bulkSetStatus(enums.orderStatusEnum.CANCELED)">
                            <i class="fa-solid fa-ban"></i> {{ $t('label.canceled') }}
                        </button>
                        <button type="button" class="bulk-btn bg-rose-600 hover:bg-rose-700" :disabled="bulkBusy" @click="bulkSetStatus(enums.orderStatusEnum.REJECTED)">
                            <i class="fa-solid fa-xmark"></i> {{ $t('label.rejected') }}
                        </button>
                    </div>

                    <!-- Payment -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 w-16 shrink-0">{{ $t('label.payment') }}</span>
                        <button type="button" class="bulk-btn bg-teal-600 hover:bg-teal-700" :disabled="bulkBusy" @click="bulkSetPayment(enums.paymentStatusEnum.PAID)">
                            <i class="fa-solid fa-money-bill"></i> {{ $t('label.paid') }}
                        </button>
                        <button type="button" class="bulk-btn bg-slate-500 hover:bg-slate-600" :disabled="bulkBusy" @click="bulkSetPayment(enums.paymentStatusEnum.UNPAID)">
                            <i class="fa-regular fa-circle-xmark"></i> {{ $t('label.unpaid') }}
                        </button>
                    </div>

                    <!-- Shipping / tools -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 w-16 shrink-0">{{ $t('label.action') }}</span>
                        <button type="button" class="bulk-btn bg-indigo-600 hover:bg-indigo-700" :disabled="bulkBusy" @click="bulkBookPostEx">
                            <i class="fa-solid fa-truck"></i> {{ $t('button.bulk_book_postex') }}
                        </button>
                        <button type="button" class="bulk-btn bg-primary hover:opacity-90" :disabled="bulkBusy" @click="printSelectedSlips">
                            <i class="lab lab-line-printer"></i> {{ $t('button.print_selected_slips') }}
                        </button>
                        <button type="button" class="bulk-btn bg-red-600 hover:bg-red-700" :disabled="bulkBusy" @click="bulkDeleteOrders">
                            <i class="fa-regular fa-trash-can"></i> {{ $t('button.delete') || 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>

            <ListFilterPanel :show="showFilter">
                <form class="p-4 sm:p-5 mb-5 w-full d-block" @submit.prevent="search">
                    <div class="row">
                        <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                            <label for="order_id" class="db-field-title after:hidden">{{ $t('label.order_id') }}</label>
                            <input id="order_id" v-model="props.search.order_serial_no" type="text"
                                class="db-field-control">
                        </div>

                        <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                            <label for="searchStatus" class="db-field-title after:hidden">
                                {{ $t('label.status') }}
                            </label>
                            <vue-select class="db-field-control f-b-custom-select" id="searchStatus"
                                v-model="props.search.status" :options="[
                                    { id: enums.orderStatusEnum.PENDING, name: $t('label.pending') },
                                    { id: enums.orderStatusEnum.CONFIRMED, name: $t('label.confirmed') },
                                    { id: enums.orderStatusEnum.ON_THE_WAY, name: $t('label.on_the_way') },
                                    { id: enums.orderStatusEnum.DELIVERED, name: $t('label.delivered') },
                                    { id: enums.orderStatusEnum.CANCELED, name: $t('label.canceled') },
                                    { id: enums.orderStatusEnum.REJECTED, name: $t('label.rejected') }
                                ]" label-by="name" value-by="id" :closeOnSelect="true" :searchable="true"
                                :clearOnClose="true" placeholder="--" search-placeholder="--" />
                        </div>

                        <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                            <label for="user_id" class="db-field-title">
                                {{ $t("label.customer") }}
                            </label>
                            <vue-select class="db-field-control f-b-custom-select" id="user_id"
                                v-model="props.search.user_id" :options="customers" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="true" :clearOnClose="true" placeholder="--"
                                search-placeholder="--" />
                        </div>

                        <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                            <label for="searchStartDate" class="db-field-title after:hidden">
                                {{ $t('label.date') }}
                            </label>
                            <Datepicker hideInputIcon autoApply :enableTimePicker="false" utc="false"
                                @update:modelValue="handleDate" v-model="modelValue" :range="true">
                            </Datepicker>
                        </div>

                        <div class="col-12">
                            <div class="flex flex-wrap gap-3 mt-4">
                                <button class="db-btn py-2 text-white bg-primary">
                                    <i class="lab lab-line-search lab-font-size-16"></i>
                                    <span>{{ $t('button.search') }}</span>
                                </button>
                                <button type="button" class="db-btn py-2 text-white bg-gray-600" @click="clear">
                                    <i class="lab lab-line-cross lab-font-size-22"></i>
                                    <span>{{ $t('button.clear') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </ListFilterPanel>

            <!-- Mobile cards -->
            <div class="md:hidden p-3 space-y-3" v-if="orders.length > 0">
                <article
                    v-for="order in orders"
                    :key="'card-' + order.id"
                    class="order-card rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm"
                    :class="{
                        'ring-2 ring-emerald-400/50 border-emerald-300 bg-emerald-50/40': selectedOrderIds.includes(order.id),
                        'order-has-note border-amber-300 bg-amber-50/70 ring-1 ring-amber-200/80': hasCustomerNote(order) && !selectedOrderIds.includes(order.id),
                        'order-has-note': hasCustomerNote(order) && selectedOrderIds.includes(order.id),
                    }"
                >
                    <div class="flex items-start gap-3">
                        <input
                            v-if="permissionChecker('online-orders')"
                            type="checkbox"
                            class="mt-1 w-4 h-4 accent-emerald-600 shrink-0"
                            :value="order.id"
                            v-model="selectedOrderIds"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <p class="font-bold text-heading text-sm truncate">{{ order.order_serial_no }}</p>
                                        <span
                                            v-if="hasCustomerNote(order)"
                                            class="shrink-0 inline-flex items-center gap-1 rounded-md bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                                            :title="order.note"
                                        >
                                            <i class="fa-solid fa-note-sticky"></i>
                                            {{ $t('label.note') || 'Note' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ order.user?.name || '—' }}</p>
                                </div>
                                <div class="relative shrink-0" v-if="permissionChecker('online-orders')">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-heading shadow-sm"
                                        :disabled="bulkBusy"
                                        @click.stop="toggleActionMenu(order.id)"
                                    >
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                        {{ $t('label.action') }}
                                    </button>
                                    <div
                                        v-if="openActionMenuId === order.id"
                                        class="order-action-menu absolute right-0 top-full mt-1 z-30 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
                                        @click.stop
                                    >
                                        <button
                                            v-if="canConfirm(order)"
                                            type="button"
                                            class="order-menu-item text-emerald-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.CONFIRMED))"
                                        >
                                            <i class="fa-solid fa-check w-4"></i> {{ $t('label.confirmed') }}
                                        </button>
                                        <button
                                            v-if="canShip(order)"
                                            type="button"
                                            class="order-menu-item text-sky-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.ON_THE_WAY))"
                                        >
                                            <i class="fa-solid fa-truck-fast w-4"></i> {{ $t('label.on_the_way') }}
                                        </button>
                                        <button
                                            v-if="canDeliver(order)"
                                            type="button"
                                            class="order-menu-item text-violet-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.DELIVERED))"
                                        >
                                            <i class="fa-solid fa-box-open w-4"></i> {{ $t('label.delivered') }}
                                        </button>
                                        <button
                                            v-if="canBookPostEx(order)"
                                            type="button"
                                            class="order-menu-item text-indigo-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowBookPostEx(order))"
                                        >
                                            <i class="fa-solid fa-truck w-4"></i> {{ $t('button.bulk_book_postex') }}
                                        </button>
                                        <button
                                            v-if="canMarkPaid(order)"
                                            type="button"
                                            class="order-menu-item text-teal-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetPayment(order, enums.paymentStatusEnum.PAID))"
                                        >
                                            <i class="fa-solid fa-money-bill w-4"></i> {{ $t('label.paid') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="order-menu-item text-primary"
                                            :disabled="slipPrintLoading || bulkBusy"
                                            @click="runRowAction(() => printSingleSlip(order.id))"
                                        >
                                            <i class="lab lab-line-printer w-4"></i> {{ $t('button.print_shipping_slip') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="order-menu-item text-indigo-700"
                                            @click="runRowAction(() => openTrackOrder(order))"
                                        >
                                            <i class="fa-solid fa-location-dot w-4"></i> {{ $t('label.track_order') || 'Track Order' }}
                                        </button>
                                        <router-link
                                            :to="{ name: 'admin.order.show', params: { id: order.id } }"
                                            class="order-menu-item text-slate-700"
                                            @click="closeActionMenu"
                                        >
                                            <i class="fa-regular fa-eye w-4"></i> {{ $t('button.view') || 'View' }}
                                        </router-link>
                                        <button
                                            type="button"
                                            class="order-menu-item text-red-600"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => destroy(order.id))"
                                        >
                                            <i class="fa-regular fa-trash-can w-4"></i> {{ $t('button.delete') || 'Delete' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="db-table-badge" :class="orderStatusClass(order.status)">
                                    {{ enums.orderStatusEnumArray[order.status] }}
                                </span>
                                <span
                                    class="db-table-badge"
                                    :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#2AC769] bg-[#E2FFEE]' : 'text-[#FB4E4E] bg-[#FFE8E8]'"
                                >
                                    {{ enums.paymentStatusEnumArray[order.payment_status] }}
                                </span>
                                <span :class="statusClass(order.order_type)" class="text-[11px]">
                                    {{ enums.orderTypeEnumArray[order.order_type] }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-slate-400 uppercase tracking-wide text-[10px] font-semibold">{{ $t('label.amount') }}</p>
                                    <p class="font-semibold text-heading mt-0.5">{{ order.total_amount_price }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-slate-400 uppercase tracking-wide text-[10px] font-semibold">{{ $t('label.date') }}</p>
                                    <p class="font-medium text-slate-600 mt-0.5">{{ order.order_datetime }}</p>
                                </div>
                            </div>
                            <p
                                v-if="order.postex_tracking_number"
                                class="mt-2 text-[11px] text-indigo-600 font-medium break-all"
                            >
                                {{ $t('label.tracking_no') }}: {{ order.postex_tracking_number }}
                            </p>
                            <p
                                v-if="hasCustomerNote(order)"
                                class="mt-2 text-[11px] text-amber-800 font-medium leading-snug line-clamp-2"
                            >
                                <i class="fa-solid fa-note-sticky mr-1 opacity-80"></i>{{ order.note }}
                            </p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="md:hidden p-6 text-center" v-else>
                <div class="max-w-[220px] mx-auto">
                    <img class="w-full h-full" :src="ENV.API_URL+'/images/default/not-found/not_found.png'" alt="Not Found">
                </div>
                <span class="d-block mt-3 text-base text-slate-500">{{ $t('message.no_data_found') }}</span>
            </div>

            <!-- Desktop table -->
            <div class="db-table-responsive hidden md:block">
                <table class="db-table stripe" id="print">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th hidden-print w-10" v-if="permissionChecker('online-orders')">
                                <input
                                    type="checkbox"
                                    class="w-4 h-4 accent-emerald-600"
                                    :checked="allPageSelected"
                                    @change="toggleSelectAllPage"
                                />
                            </th>
                            <th class="db-table-head-th">{{ $t('label.order_id') }}</th>
                            <th class="db-table-head-th">{{ $t('label.order_type') }}</th>
                            <th class="db-table-head-th">{{ $t('label.customer') }}</th>
                            <th class="db-table-head-th">{{ $t('label.amount') }}</th>
                            <th class="db-table-head-th">{{ $t('label.date') }}</th>
                            <th class="db-table-head-th">{{ $t('label.status') }}</th>
                            <th class="db-table-head-th">{{ $t('label.payment') }}</th>
                            <th class="db-table-head-th hidden-print" v-if="permissionChecker('online-orders')">
                                {{ $t('label.action') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body" v-if="orders.length > 0">
                        <tr
                            class="db-table-body-tr"
                            :class="{
                                'bg-emerald-50/60': selectedOrderIds.includes(order.id),
                                'order-has-note bg-amber-50/80': hasCustomerNote(order) && !selectedOrderIds.includes(order.id),
                                'order-has-note': hasCustomerNote(order) && selectedOrderIds.includes(order.id),
                            }"
                            v-for="order in orders"
                            :key="order.id"
                        >
                            <td class="db-table-body-td hidden-print" v-if="permissionChecker('online-orders')">
                                <input
                                    type="checkbox"
                                    class="w-4 h-4 accent-emerald-600"
                                    :value="order.id"
                                    v-model="selectedOrderIds"
                                />
                            </td>
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <div class="font-semibold">{{ order.order_serial_no }}</div>
                                    <span
                                        v-if="hasCustomerNote(order)"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                                        :title="order.note"
                                    >
                                        <i class="fa-solid fa-note-sticky"></i>
                                        {{ $t('label.note') || 'Note' }}
                                    </span>
                                </div>
                                <div
                                    v-if="order.postex_tracking_number"
                                    class="mt-0.5 text-[11px] text-indigo-600 font-medium break-all"
                                >
                                    {{ $t('label.tracking_no') }}: {{ order.postex_tracking_number }}
                                </div>
                                <div
                                    v-if="hasCustomerNote(order)"
                                    class="mt-0.5 text-[11px] text-amber-800 font-medium leading-snug line-clamp-2 max-w-xs"
                                    :title="order.note"
                                >
                                    {{ order.note }}
                                </div>
                            </td>
                            <td class="db-table-body-td">
                                <span :class="statusClass(order.order_type)">
                                    {{ enums.orderTypeEnumArray[order.order_type] }}
                                </span>
                            </td>
                            <td class="db-table-body-td">
                                {{ textShortener(order.user?.name, 20) }}
                            </td>
                            <td class="db-table-body-td">{{ order.total_amount_price }}</td>
                            <td class="db-table-body-td">
                                {{ order.order_datetime }}
                            </td>
                            <td class="db-table-body-td">
                                <span class="db-table-badge" :class="orderStatusClass(order.status)">
                                    {{ enums.orderStatusEnumArray[order.status] }}
                                </span>
                            </td>
                            <td class="db-table-body-td">
                                <span
                                    class="db-table-badge"
                                    :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#2AC769] bg-[#E2FFEE]' : 'text-[#FB4E4E] bg-[#FFE8E8]'"
                                >
                                    {{ enums.paymentStatusEnumArray[order.payment_status] }}
                                </span>
                            </td>
                            <td class="db-table-body-td hidden-print" v-if="permissionChecker('online-orders')">
                                <div class="relative inline-block">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-heading shadow-sm hover:bg-slate-50"
                                        :disabled="bulkBusy"
                                        @click.stop="toggleActionMenu(order.id)"
                                    >
                                        <i class="fa-solid fa-ellipsis"></i>
                                        {{ $t('label.action') }}
                                    </button>
                                    <div
                                        v-if="openActionMenuId === order.id"
                                        class="order-action-menu absolute right-0 top-full mt-1 z-30 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
                                        @click.stop
                                    >
                                        <button
                                            v-if="canConfirm(order)"
                                            type="button"
                                            class="order-menu-item text-emerald-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.CONFIRMED))"
                                        >
                                            <i class="fa-solid fa-check w-4"></i> {{ $t('label.confirmed') }}
                                        </button>
                                        <button
                                            v-if="canShip(order)"
                                            type="button"
                                            class="order-menu-item text-sky-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.ON_THE_WAY))"
                                        >
                                            <i class="fa-solid fa-truck-fast w-4"></i> {{ $t('label.on_the_way') }}
                                        </button>
                                        <button
                                            v-if="canDeliver(order)"
                                            type="button"
                                            class="order-menu-item text-violet-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetStatus(order, enums.orderStatusEnum.DELIVERED))"
                                        >
                                            <i class="fa-solid fa-box-open w-4"></i> {{ $t('label.delivered') }}
                                        </button>
                                        <button
                                            v-if="canBookPostEx(order)"
                                            type="button"
                                            class="order-menu-item text-indigo-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowBookPostEx(order))"
                                        >
                                            <i class="fa-solid fa-truck w-4"></i> {{ $t('button.bulk_book_postex') }}
                                        </button>
                                        <button
                                            v-if="canMarkPaid(order)"
                                            type="button"
                                            class="order-menu-item text-teal-700"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => rowSetPayment(order, enums.paymentStatusEnum.PAID))"
                                        >
                                            <i class="fa-solid fa-money-bill w-4"></i> {{ $t('label.paid') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="order-menu-item text-primary"
                                            :disabled="slipPrintLoading || bulkBusy"
                                            @click="runRowAction(() => printSingleSlip(order.id))"
                                        >
                                            <i class="lab lab-line-printer w-4"></i> {{ $t('button.print_shipping_slip') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="order-menu-item text-indigo-700"
                                            @click="runRowAction(() => openTrackOrder(order))"
                                        >
                                            <i class="fa-solid fa-location-dot w-4"></i> {{ $t('label.track_order') || 'Track Order' }}
                                        </button>
                                        <router-link
                                            :to="{ name: 'admin.order.show', params: { id: order.id } }"
                                            class="order-menu-item text-slate-700"
                                            @click="closeActionMenu"
                                        >
                                            <i class="fa-regular fa-eye w-4"></i> {{ $t('button.view') || 'View' }}
                                        </router-link>
                                        <button
                                            type="button"
                                            class="order-menu-item text-red-600"
                                            :disabled="bulkBusy"
                                            @click="runRowAction(() => destroy(order.id))"
                                        >
                                            <i class="fa-regular fa-trash-can w-4"></i> {{ $t('button.delete') || 'Delete' }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="db-table-body" v-else>
                        <tr class="db-table-body-tr">
                            <td class="db-table-body-td text-center" :colspan="permissionChecker('online-orders') ? 9 : 8">
                                <div class="p-4">
                                    <div class="max-w-[300px] mx-auto mt-2">
                                        <img class="w-full h-full" :src="ENV.API_URL+'/images/default/not-found/not_found.png'" alt="Not Found">
                                    </div>
                                    <span class="d-block mt-3 text-lg">{{ $t('message.no_data_found') }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6" v-if="orders.length > 0">
                <PaginationSMBox :pagination="pagination" :method="list" />
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <PaginationTextComponent :props="{ page: paginationPage }" />
                    <PaginationBox :pagination="pagination" :method="list" />
                </div>
            </div>
        </div>
    </div>

    <OnlineOrderShippingSlipPrintComponent ref="shippingSlipPrint" />
</template>
<script>
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import PaginationTextComponent from "../components/pagination/PaginationTextComponent";
import PaginationBox from "../components/pagination/PaginationBox";
import PaginationSMBox from "../components/pagination/PaginationSMBox";
import appService from "../../../services/appService";
import orderStatusEnum from "../../../enums/modules/orderStatusEnum";
import orderTypeEnum from "../../../enums/modules/orderTypeEnum";
import paymentStatusEnum from "../../../enums/modules/paymentStatusEnum";
import TableLimitComponent from "../components/TableLimitComponent";
import ListFilterPanel from "../components/ListFilterPanel";
import ExportComponent from "../components/buttons/export/ExportComponent";
import PrintComponent from "../components/buttons/export/PrintComponent";
import ExcelComponent from "../components/buttons/export/ExcelComponent";
import statusEnum from "../../../enums/modules/statusEnum";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import ENV from "../../../config/env";
import axios from "axios";
import OnlineOrderShippingSlipPrintComponent from "./OnlineOrderShippingSlipPrintComponent.vue";

export default {
    name: "OnlineOrderListComponent",
    components: {
        TableLimitComponent,
        PaginationSMBox,
        PaginationBox,
        PaginationTextComponent,
        LoadingComponent,
        ListFilterPanel,
        ExportComponent,
        PrintComponent,
        ExcelComponent,
        Datepicker,
        OnlineOrderShippingSlipPrintComponent
    },
    data() {
        return {
            loading: {
                isActive: false
            },
            showFilter: false,
            enums: {
                orderStatusEnum: orderStatusEnum,
                orderTypeEnum: orderTypeEnum,
                paymentStatusEnum: paymentStatusEnum,
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
                    [paymentStatusEnum.UNPAID]: this.$t("label.unpaid"),
                },
                orderTypeEnumArray: {
                    [orderTypeEnum.DELIVERY]: this.$t("label.delivery"),
                    [orderTypeEnum.PICK_UP]: this.$t("label.pick_up")
                }
            },
            printLoading: true,
            printObj: {
                id: "print",
                popTitle: this.$t("menu.online_orders"),
            },
            props: {
                search: {
                    paginate: 1,
                    page: 1,
                    per_page: 10,
                    order_column: 'id',
                    order_by: "desc",
                    order_serial_no: "",
                    user_id: null,
                    excepts: orderTypeEnum.POS,
                    status: null,
                    active: statusEnum.ACTIVE,
                    from_date: "",
                    to_date: "",
                }
            },
            modelValue: null,
            ENV: ENV,
            selectedOrderIds: [],
            slipPrintLoading: false,
            bulkBusy: false,
            openActionMenuId: null,
        }
    },
    mounted() {
        this.list();
        this.$store.dispatch('user/lists', {
            order_column: 'id',
            order_type: 'asc',
            status: statusEnum.ACTIVE
        });
        document.addEventListener('click', this.closeActionMenu);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeActionMenu);
    },
    computed: {
        orders: function () {
            return this.$store.getters['onlineOrder/lists'];
        },
        customers: function () {
            return this.$store.getters['user/lists'];
        },
        pagination: function () {
            return this.$store.getters['onlineOrder/pagination'];
        },
        paginationPage: function () {
            return this.$store.getters['onlineOrder/page'];
        },
        allPageSelected: function () {
            if (!this.orders.length) {
                return false;
            }
            return this.orders.every((order) => this.selectedOrderIds.includes(order.id));
        },
    },
    methods: {
        permissionChecker(e) {
            return appService.permissionChecker(e);
        },
        statusClass: function (status) {
            return appService.statusClass(status);
        },
        orderStatusClass: function (status) {
            return appService.orderStatusClass(status);
        },
        textShortener: function (text, number = 30) {
            return appService.textShortener(text, number);
        },
        orderStatusInt(order) {
            return parseInt(order?.status, 10);
        },
        orderPaymentInt(order) {
            return parseInt(order?.payment_status, 10);
        },
        canConfirm(order) {
            return this.orderStatusInt(order) === orderStatusEnum.PENDING;
        },
        canShip(order) {
            return this.orderStatusInt(order) === orderStatusEnum.CONFIRMED;
        },
        canDeliver(order) {
            return this.orderStatusInt(order) === orderStatusEnum.ON_THE_WAY;
        },
        canBookPostEx(order) {
            const status = this.orderStatusInt(order);
            return !order?.postex_tracking_number
                && status !== orderStatusEnum.CANCELED
                && status !== orderStatusEnum.REJECTED;
        },
        canMarkPaid(order) {
            return this.orderPaymentInt(order) !== paymentStatusEnum.PAID;
        },
        hasCustomerNote(order) {
            return String(order?.note || '').trim() !== '';
        },
        toggleActionMenu(orderId) {
            this.openActionMenuId = this.openActionMenuId === orderId ? null : orderId;
        },
        closeActionMenu() {
            this.openActionMenuId = null;
        },
        runRowAction(fn) {
            this.closeActionMenu();
            if (typeof fn === 'function') {
                fn();
            }
        },
        openTrackOrder(order) {
            const ref = String(order?.order_serial_no || order?.postex_tracking_number || '').trim();
            if (!ref) {
                alertService.error(this.$t('message.no_data_found') || 'Order reference not found');
                return;
            }
            const base = String(ENV.API_URL || window.location.origin || '').replace(/\/$/, '');
            window.open(`${base}/track-order?order=${encodeURIComponent(ref)}`, '_blank', 'noopener,noreferrer');
        },
        search: function () {
            this.list();
        },
        handleDate: function (e) {
            if (e) {
                this.props.search.from_date = e[0];
                this.props.search.to_date = e[1];
            } else {
                this.props.search.from_date = null;
                this.props.search.to_date = null;
            }
        },
        clear: function () {
            this.props.search.paginate = 1;
            this.props.search.page = 1;
            this.props.search.order_by = "desc";
            this.props.search.order_serial_no = "";
            this.props.search.status = null;
            this.props.search.excepts = orderTypeEnum.POS;
            this.props.search.from_date = null;
            this.props.search.to_date = null;
            this.props.search.user_id = null;
            this.modelValue = null;
            this.list();
        },
        list: function (page = 1) {
            this.loading.isActive = true;
            this.props.search.page = page;
            this.$store.dispatch('onlineOrder/lists', this.props.search).then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        toggleSelectAllPage: function (event) {
            const pageIds = this.orders.map((order) => order.id);
            if (event.target.checked) {
                this.selectedOrderIds = [...new Set([...this.selectedOrderIds, ...pageIds])];
            } else {
                this.selectedOrderIds = this.selectedOrderIds.filter((id) => !pageIds.includes(id));
            }
        },
        selectAllPage: function () {
            const pageIds = this.orders.map((order) => order.id);
            this.selectedOrderIds = [...new Set([...this.selectedOrderIds, ...pageIds])];
        },
        clearSelection: function () {
            this.selectedOrderIds = [];
        },
        handleBulkResult: function (res, fallbackOk) {
            const data = res.data?.data || {};
            const success = data.success || 0;
            const failed = data.failed || 0;
            const firstError = (data.results || []).find((row) => !row.ok && row.message);
            if (success > 0) {
                alertService.success(res.data?.message || `${fallbackOk} ${success}. Failed: ${failed}.`);
                if (firstError && failed > 0) {
                    alertService.error(firstError.message);
                }
            } else {
                alertService.error(firstError?.message || res.data?.message || this.$t('message.something_wrong'));
            }
            this.selectedOrderIds = [];
            this.list(this.props.search.page);
        },
        rowSetStatus: function (order, status) {
            if (!order?.id || !status) {
                return;
            }
            const needsReason = status === orderStatusEnum.CANCELED || status === orderStatusEnum.REJECTED;
            let reason = null;
            if (needsReason) {
                reason = window.prompt(this.$t('label.reason') || 'Reason');
                if (!reason || !String(reason).trim()) {
                    alertService.error(this.$t('message.reason_required') || 'Reason is required.');
                    return;
                }
                reason = String(reason).trim();
            }
            this.bulkBusy = true;
            this.loading.isActive = true;
            axios.post(`admin/online-order/change-status/${order.id}`, {
                status: status,
                reason: reason,
            }).then(() => {
                alertService.successFlip(1, this.$t('label.status'));
                this.list(this.props.search.page);
            }).catch((err) => {
                alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
            }).finally(() => {
                this.bulkBusy = false;
                this.loading.isActive = false;
            });
        },
        rowSetPayment: function (order, paymentStatus) {
            if (!order?.id || !paymentStatus) {
                return;
            }
            this.bulkBusy = true;
            this.loading.isActive = true;
            axios.post(`admin/online-order/change-payment-status/${order.id}`, {
                payment_status: paymentStatus,
            }).then(() => {
                alertService.successFlip(1, this.$t('label.payment_status'));
                this.list(this.props.search.page);
            }).catch((err) => {
                alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
            }).finally(() => {
                this.bulkBusy = false;
                this.loading.isActive = false;
            });
        },
        rowBookPostEx: function (order) {
            if (!order?.id) {
                return;
            }
            appService.submitConfirmation().then(() => {
                this.bulkBusy = true;
                this.loading.isActive = true;
                axios.post(`admin/online-order/${order.id}/postex/create`)
                    .then((res) => {
                        alertService.success(res.data?.message || this.$t('message.postex_booked') || 'PostEx booked');
                        this.list(this.props.search.page);
                    })
                    .catch((err) => {
                        alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
                    })
                    .finally(() => {
                        this.bulkBusy = false;
                        this.loading.isActive = false;
                    });
            }).catch(() => {});
        },
        printSingleSlip: function (orderId) {
            this.runSlipPrint([orderId]);
        },
        printSelectedSlips: function () {
            this.runSlipPrint(this.selectedOrderIds);
        },
        runSlipPrint: async function (orderIds) {
            if (!orderIds.length) {
                return;
            }
            this.slipPrintLoading = true;
            this.bulkBusy = true;
            this.loading.isActive = true;
            try {
                await this.$refs.shippingSlipPrint.printOrders(orderIds);
            } catch (err) {
                alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
            } finally {
                this.slipPrintLoading = false;
                this.bulkBusy = false;
                this.loading.isActive = false;
            }
        },
        bulkBookPostEx: function () {
            if (!this.selectedOrderIds.length) {
                return;
            }
            appService.submitConfirmation().then(() => {
                this.bulkBusy = true;
                this.loading.isActive = true;
                axios.post('admin/online-order/postex/bulk-create', {
                    ids: this.selectedOrderIds,
                }).then((res) => {
                    this.handleBulkResult(res, 'Booked');
                }).catch((err) => {
                    alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
                }).finally(() => {
                    this.bulkBusy = false;
                    this.loading.isActive = false;
                });
            }).catch(() => {});
        },
        bulkSetStatus: function (status) {
            if (!this.selectedOrderIds.length || !status) {
                return;
            }

            const needsReason = status === orderStatusEnum.CANCELED || status === orderStatusEnum.REJECTED;
            let reason = null;
            if (needsReason) {
                reason = window.prompt(this.$t('label.reason') || 'Reason');
                if (!reason || !String(reason).trim()) {
                    alertService.error(this.$t('message.reason_required') || 'Reason is required.');
                    return;
                }
                reason = String(reason).trim();
            }

            appService.submitConfirmation().then(() => {
                this.bulkBusy = true;
                this.loading.isActive = true;
                axios.post('admin/online-order/bulk-change-status', {
                    ids: this.selectedOrderIds,
                    status: status,
                    reason: reason,
                }).then((res) => {
                    this.handleBulkResult(res, 'Updated');
                }).catch((err) => {
                    alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
                }).finally(() => {
                    this.bulkBusy = false;
                    this.loading.isActive = false;
                });
            }).catch(() => {});
        },
        bulkSetPayment: function (paymentStatus) {
            if (!this.selectedOrderIds.length || !paymentStatus) {
                return;
            }
            appService.submitConfirmation().then(() => {
                this.bulkBusy = true;
                this.loading.isActive = true;
                axios.post('admin/online-order/bulk-change-payment-status', {
                    ids: this.selectedOrderIds,
                    payment_status: paymentStatus,
                }).then((res) => {
                    this.handleBulkResult(res, 'Payment updated');
                }).catch((err) => {
                    alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
                }).finally(() => {
                    this.bulkBusy = false;
                    this.loading.isActive = false;
                });
            }).catch(() => {});
        },
        bulkDeleteOrders: function () {
            if (!this.selectedOrderIds.length) {
                return;
            }
            appService.destroyConfirmation().then(() => {
                this.bulkBusy = true;
                this.loading.isActive = true;
                axios.post('admin/online-order/bulk-delete', {
                    ids: this.selectedOrderIds,
                }).then((res) => {
                    this.handleBulkResult(res, 'Deleted');
                }).catch((err) => {
                    alertService.error(err?.response?.data?.message || err?.message || this.$t('message.something_wrong'));
                }).finally(() => {
                    this.bulkBusy = false;
                    this.loading.isActive = false;
                });
            }).catch(() => {});
        },
        xls: function () {
            this.loading.isActive = true;
            this.$store.dispatch("onlineOrder/export", this.props.search).then((res) => {
                this.loading.isActive = false;
                const blob = new Blob([res.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = this.$t("menu.online_orders");
                link.click();
                URL.revokeObjectURL(link.href);
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response.data.message);
            });
        },
        destroy: function (id) {
            appService.destroyConfirmation().then((res) => {
                try {
                    this.loading.isActive = true;
                    this.$store.dispatch('onlineOrder/destroy', { id: id, search: this.props.search }).then((res) => {
                        this.loading.isActive = false;
                        this.selectedOrderIds = this.selectedOrderIds.filter((oid) => oid !== id);
                        alertService.successFlip(null, this.$t('menu.online_orders'));
                    }).catch((err) => {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    });
                } catch (e) {
                    this.loading.isActive = false;
                    alertService.error(e.response.data.message);
                }
            }).catch((err) => {
                this.loading.isActive = false;
            })
        }
    }
}
</script>

<style scoped>
.dp__main:has(.box) .dp__input {
    border-color: inherit;
}

@media print {
    .hidden-print {
        display: none !important;
    }
}

.db-table-action.print i {
    @apply text-primary bg-primary/10 hover:bg-primary/20;
}

.bulk-btn {
    @apply inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-50 disabled:cursor-not-allowed;
}

.order-menu-item {
    @apply flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs font-semibold hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

.order-action-menu {
    max-height: min(70vh, 360px);
    overflow-y: auto;
}

.order-has-note {
    box-shadow: inset 3px 0 0 #f59e0b;
}
</style>
