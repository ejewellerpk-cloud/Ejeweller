<template>
    <button @click="openSettingMenu($event)" type="button" class="flex items-center justify-center w-full gap-2 p-2 text-white rounded settings-btn md:hidden bg-primary">
        <span class="capitalize">{{ $t('menu.settings_menu') }}</span>
        <i class="text-sm icon fa-solid fa-chevron-down"></i>
    </button>
    <div class="h-0 overflow-hidden font-medium transition-all duration-300 md:h-auto md:overflow-auto">
        <nav class="p-3 db-card">
            <router-link :to="{ name: 'admin.settings.company' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-company"></i>
                {{ $t("menu.company") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.site' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-site"></i>
                {{ $t("menu.site") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.mail' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-mail"></i>
                {{ $t("menu.mail") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.locationSetup' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-location-setup"></i>
                {{ $t("menu.location_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.shippingSetup' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-truck-check"></i>
                {{ $t("menu.shipping_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.otp' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-otp"></i>
                {{ $t("menu.otp") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.notification' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-notification"></i>
                {{ $t("menu.notification") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.notificationAlert' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-notification-alert"></i>
                {{ $t("menu.notification_alert") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.socialMedia' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-social"></i>
                {{ $t("menu.social_media") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.cookies' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-cookies"></i>
                {{ $t("menu.cookies") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.analytic' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-analytic"></i>
                {{ $t("menu.analytics") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.theme' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-theme"></i>
                {{ $t("menu.theme") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.topBar' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-promotion"></i>
                {{ $t("menu.top_bar") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.slider' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-sliders"></i>
                {{ $t("menu.sliders") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.currency' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-currencies"></i>
                {{ $t("menu.currencies") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.productCategory' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-item-categories"></i>
                {{ $t("menu.product_categories") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.productAttribute' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-item-attributes"></i>
                {{ $t("menu.product_attributes") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.productBrand' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-brand"></i>
                {{ $t("menu.product_brands") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.returnReason' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-order-setup"></i>
                {{ $t("menu.return_reasons") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.supplier' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-supplier"></i>
                {{ $t("menu.suppliers") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.outlet' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-branches"></i>
                {{ $t("menu.outlets") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.benefit' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-benefits"></i>
                {{ $t("menu.benefits") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.unit' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-unit"></i>
                {{ $t("menu.units") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.tax' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-taxes"></i>
                {{ $t("menu.taxes") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.page' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-pages"></i>
                {{ $t("menu.pages") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.role' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-role-permission"></i>
                {{ $t("menu.role_permissions") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.language' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-global"></i>
                {{ $t("menu.languages") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.aiAgent' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-ai-agent"></i>
                {{ $t("menu.ai_agent") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.smsGateway' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-sms"></i>
                {{ $t("menu.sms_gateway") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.paymentGateway' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-payment-gateway"></i>
                {{ $t("menu.payment_gateway") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.pwa' }" class="db-tab-btn">
                <i class="text-sm lab lab-monitor-mobbile"></i>
                {{ $t("menu.progressive_web_app") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.whatsapp' }" class="db-tab-btn">
                <i class="text-sm lab lab-whatsapp"></i>
                {{ $t("menu.whatsapp_order_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.socialLogin' }" class="db-tab-btn">
                <i class="text-sm lab lab-line-social"></i>
                {{ $t("menu.social_login") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.license' }" class="db-tab-btn">
                <i class="text-sm lab-line-license-code"></i>
                {{ $t("menu.license") }}
            </router-link>
        </nav>
    </div>
</template>

<script>
import appService from "../../../services/appService";
export default {
    name: "MenuComponent",

    methods: {
        openSettingMenu: function (event) {
            return appService.openSettingMenu(event);
        },
 }
};
</script>
