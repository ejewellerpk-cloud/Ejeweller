<template>
    <button
        ref="printTrigger"
        type="button"
        class="hidden"
        v-print="printObj"
        aria-hidden="true"
        tabindex="-1"
    ></button>

    <div id="shippingSlipPrintArea" class="shipping-slip-print-area" :class="{ 'is-ready': slipsReady }">
        <div
            v-for="(page, pageIndex) in slipPages"
            :key="'page-' + pageIndex"
            class="shipping-slip-page"
        >
            <div
                v-for="(slip, slotIndex) in page"
                :key="'slot-' + pageIndex + '-' + slotIndex"
                class="shipping-slip-slot"
            >
                <OnlineOrderShippingSlipComponent v-if="slip" :slip="slip" />
            </div>
        </div>
    </div>
</template>

<script>
import print from "vue3-print-nb";
import OnlineOrderShippingSlipComponent from "./OnlineOrderShippingSlipComponent.vue";
import {
    buildShippingSlip,
    chunkSlips,
    preloadImages,
} from "../../../services/onlineOrderShippingSlip";

export default {
    name: "OnlineOrderShippingSlipPrintComponent",
    components: {
        OnlineOrderShippingSlipComponent,
    },
    directives: {
        print,
    },
    data() {
        return {
            slipPages: [],
            slipsReady: false,
            printObj: {
                id: "shippingSlipPrintArea",
                popTitle: this.$t("menu.online_orders"),
                extraCss:
                    "https://fonts.googleapis.com/css2?family=Arial&display=swap",
            },
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
                const order = res.data.data;
                slips.push(buildShippingSlip(order, this.company));
            }

            await preloadImages(slips.map((s) => s.qrCodeUrl));

            this.slipPages = chunkSlips(slips, 3);
            this.slipsReady = true;

            await this.$nextTick();
            await new Promise((r) => setTimeout(r, 150));
            this.$refs.printTrigger?.click();
            return true;
        },
        clear() {
            this.slipPages = [];
            this.slipsReady = false;
        },
    },
};
</script>

<style>
.shipping-slip-print-area {
    position: fixed;
    left: -9999px;
    top: 0;
    width: 210mm;
    pointer-events: none;
    z-index: -1;
}

@media print {
    body * {
        visibility: hidden;
    }

    #shippingSlipPrintArea,
    #shippingSlipPrintArea * {
        visibility: visible;
    }

    #shippingSlipPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        pointer-events: auto;
        z-index: 99999;
    }

    .shipping-slip-page {
        width: 210mm;
        height: 297mm;
        page-break-after: always;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-sizing: border-box;
        padding: 3mm 4mm;
        gap: 2mm;
    }

    .shipping-slip-page:last-child {
        page-break-after: auto;
    }

    .shipping-slip-slot {
        flex: 0 0 32%;
        max-height: 32%;
        min-height: 32%;
        box-sizing: border-box;
        overflow: hidden;
    }

    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>
