<template>
    <div ref="printRoot" class="shipping-slip-print-root" v-show="slipPages.length > 0">
        <div
            v-for="(page, pageIndex) in slipPages"
            :key="'page-' + pageIndex"
            class="shipping-slip-page"
        >
            <div
                v-for="(slip, slotIndex) in page"
                :key="'slot-' + pageIndex + '-' + slotIndex"
                class="shipping-slip-slot"
                :class="{ 'shipping-slip-slot--empty': !slip }"
            >
                <OnlineOrderShippingSlipComponent v-if="slip" :slip="slip" />
            </div>
        </div>
    </div>
</template>

<script>
import alertService from "../../../services/alertService";
import OnlineOrderShippingSlipComponent from "./OnlineOrderShippingSlipComponent.vue";
import {
    buildShippingSlip,
    chunkSlips,
    openPrintWindow,
    preloadImages,
} from "../../../services/onlineOrderShippingSlip";

export default {
    name: "OnlineOrderShippingSlipPrintComponent",
    components: {
        OnlineOrderShippingSlipComponent,
    },
    data() {
        return {
            slipPages: [],
        };
    },
    mounted() {
        this.$store.dispatch("company/lists").catch(() => {});
    },
    computed: {
        company() {
            return this.$store.getters["company/lists"] || {};
        },
    },
    methods: {
        async printOrders(orderIds) {
            const ids = [...new Set((orderIds || []).filter(Boolean))];
            if (!ids.length) {
                return false;
            }

            const slips = [];
            for (const id of ids) {
                const res = await this.$store.dispatch("onlineOrder/show", id);
                slips.push(buildShippingSlip(res.data.data, this.company));
            }

            if (!slips.length) {
                alertService.error(this.$t("message.no_data_found"));
                return false;
            }

            await preloadImages(slips.map((s) => s.qrCodeUrl));

            this.slipPages = chunkSlips(slips, 3);
            await this.$nextTick();
            await new Promise((r) => setTimeout(r, 350));

            const root = this.$refs.printRoot;
            const html = root?.innerHTML?.trim();
            if (!html) {
                alertService.error(this.$t("message.something_wrong"));
                return false;
            }

            const opened = openPrintWindow(html, this.$t("menu.online_orders"));
            if (!opened) {
                alertService.error("Please allow pop-ups to print shipping slips.");
                return false;
            }

            return true;
        },
        clear() {
            this.slipPages = [];
        },
    },
};
</script>

<style scoped>
.shipping-slip-print-root {
    position: fixed;
    left: 0;
    top: 0;
    width: 210mm;
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
    z-index: -1;
}
</style>
