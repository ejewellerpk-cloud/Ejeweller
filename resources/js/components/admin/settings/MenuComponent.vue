<template>
    <button @click="openSettingMenu($event)" type="button" class="flex items-center justify-center w-full gap-2 p-2 text-white rounded settings-btn md:hidden bg-primary">
        <span class="capitalize">{{ $t('menu.settings_menu') }}</span>
        <i class="text-sm icon fa-solid fa-chevron-down"></i>
    </button>
    <div class="h-0 overflow-hidden font-medium transition-all duration-300 md:h-auto md:overflow-auto">
        <nav class="p-3 db-card settings-nav" @click.stop>
            <router-link :to="{ name: 'admin.settings.company' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-company"></i>
                {{ $t("menu.company") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.site' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-site"></i>
                {{ $t("menu.site") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.mail' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-mail"></i>
                {{ $t("menu.mail") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.locationSetup' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-location-setup"></i>
                {{ $t("menu.location_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.shippingSetup' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-truck-check"></i>
                {{ $t("menu.shipping_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.otp' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-otp"></i>
                {{ $t("menu.otp") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.notification' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-notification"></i>
                {{ $t("menu.notification") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.notificationAlert' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-notification-alert"></i>
                {{ $t("menu.notification_alert") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.socialMedia' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-social"></i>
                {{ $t("menu.social_media") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.cookies' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-cookies"></i>
                {{ $t("menu.cookies") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.analytic' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-analytic"></i>
                {{ $t("menu.analytics") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.theme' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-theme"></i>
                {{ $t("menu.theme") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.topBar' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-promotion"></i>
                {{ $t("menu.top_bar") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.productPage' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-product"></i>
                {{ $t("menu.product_page") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.slider' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-sliders"></i>
                {{ $t("menu.sliders") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.currency' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-currencies"></i>
                {{ $t("menu.currencies") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.returnReason' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-order-setup"></i>
                {{ $t("menu.return_reasons") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.supplier' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-supplier"></i>
                {{ $t("menu.suppliers") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.outlet' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-branches"></i>
                {{ $t("menu.outlets") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.benefit' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-benefits"></i>
                {{ $t("menu.benefits") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.page' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-pages"></i>
                {{ $t("menu.pages") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.role' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-role-permission"></i>
                {{ $t("menu.role_permissions") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.language' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-global"></i>
                {{ $t("menu.languages") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.aiAgent' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-ai-agent"></i>
                {{ $t("menu.ai_agent") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.smsGateway' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-sms"></i>
                {{ $t("menu.sms_gateway") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.paymentGateway' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-payment-gateway"></i>
                {{ $t("menu.payment_gateway") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.pwa' }" class="settings-menu-link">
                <i class="text-sm lab lab-monitor-mobbile"></i>
                {{ $t("menu.progressive_web_app") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.whatsapp' }" class="settings-menu-link">
                <i class="text-sm lab lab-whatsapp"></i>
                {{ $t("menu.whatsapp_order_setup") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.postex' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-truck-check"></i>
                {{ $t("menu.postex_cod") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.socialLogin' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-social"></i>
                {{ $t("menu.social_login") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.facebookCapi' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-social"></i>
                Facebook CAPI
            </router-link>
            <router-link :to="{ name: 'admin.settings.intelligenceAnalytics' }" class="settings-menu-link">
                <i class="text-sm lab lab-line-key"></i>
                {{ $t("menu.intelligence_analytics") }}
            </router-link>
            <router-link :to="{ name: 'admin.settings.license' }" class="settings-menu-link">
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
