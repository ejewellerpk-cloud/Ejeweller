<template>
    <LoadingComponent :props="loading" />
    <div id="payment" class="db-tab-div active">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 mb-5">
            <button @click="selectActive(index)"
                class="db-tab-sub-btn w-full flex items-center gap-3 h-10 px-4 rounded-lg transition bg-white hover:text-primary hover:bg-primary/5"
                :data-tab="'#' + paymentGateway.slug" v-for="(paymentGateway, index) in paymentGateways.slice(0, 3)"
                :key="'tab-' + paymentGateway.id" :class="index === selectIndex ? 'active' : ''">
                <span class="capitalize whitespace-nowrap text-[15px]">
                    {{ paymentGateway.name }}
                </span>
            </button>

            <div v-if="paymentGateways.length > 3" class="dropdown-group w-full">
                <button
                    class="dropdown-btn w-full flex items-center gap-3 h-10 px-4 rounded-lg transition bg-white hover:text-primary hover:bg-primary/5">
                    <i class="fa-solid fa-circle-chevron-down text-sm"></i>
                    <span class="capitalize whitespace-nowrap text-[15px]"> {{ $t('label.more_gateway') }}</span>
                </button>
                <div class="w-full dropdown-list absolute top-[42px] right-0 z-30 p-2 rounded-md bg-white shadow-lg">
                    <button @click="selectActive(index + 3)"
                        class="db-tab-sub-btn w-full flex items-center whitespace-nowrap justify-start my-0.5 gap-2.5 pl-3 pr-6 py-1.5 text-sm rounded-md capitalize transition text-gray-500 hover:text-primary hover:bg-primary/5"
                        :data-tab="'#' + paymentGateway.slug"
                        v-for="(paymentGateway, index) in paymentGateways.slice(3, paymentGateways.length)"
                        :key="'more-' + paymentGateway.id" :class="index + 3 === selectIndex ? 'active' : ''">
                        {{ paymentGateway.name }}
                    </button>
                </div>
            </div>
        </div>
        <div :id="'gateway-card-' + paymentGateway.slug" class="db-card db-tab-sub-div"
            v-for="(paymentGateway, index) in paymentGateways" :key="'card-' + paymentGateway.id"
            :class="index === selectIndex ? 'active' : ''">
            <div class="db-card-header">
                <h3 class="db-card-title">{{ paymentGateway.name }}</h3>
            </div>
            <div class="db-card-body">
                <form v-if="forms[paymentGateway.slug]" @submit.prevent="save(index)" class="w-full d-block">
                    <div class="form-row">
                        <div class="form-col-12 sm:form-col-6"
                            v-for="paymentGatewayOption in uniqueOptions(paymentGateway.options)"
                            :key="paymentGateway.id + '-' + paymentGatewayOption.option">
                            <label :for="'opt-' + paymentGateway.slug + '-' + paymentGatewayOption.option"
                                class="db-field-title">
                                {{ $t("label." + paymentGatewayOption.option) }}
                            </label>
                            <input v-if="isTextOption(paymentGatewayOption)" type="text"
                                v-model="forms[paymentGateway.slug][paymentGatewayOption.option]"
                                v-bind:class="errors[paymentGatewayOption.option] ? 'invalid' : ''"
                                :id="'opt-' + paymentGateway.slug + '-' + paymentGatewayOption.option"
                                class="db-field-control" />

                            <select v-else class="db-field-control"
                                v-model="forms[paymentGateway.slug][paymentGatewayOption.option]"
                                :id="'opt-' + paymentGateway.slug + '-' + paymentGatewayOption.option"
                                v-bind:class="errors[paymentGatewayOption.option] ? 'invalid' : ''">
                                <option v-for="(activity, activityKey) in activitiesMap(paymentGatewayOption)"
                                    :key="paymentGatewayOption.option + '-' + activityKey" :value="String(activityKey)">
                                    {{ $t("label." + activity) }}
                                </option>
                            </select>

                            <small class="db-field-alert" v-if="errors[paymentGatewayOption.option]">{{
                                errors[paymentGatewayOption.option][0]
                            }}</small>
                        </div>
                        <div class="form-col-12">
                            <button type="submit" class="db-btn text-white bg-primary">
                                <i class="lab lab-fill-save"></i>
                                <span>{{ $t("button.save") }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import inputTypeEnum from "../../../../enums/modules/inputTypeEnum";

export default {
    name: "PaymentGatewayComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            search: {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                excepts: "1|2"
            },
            selectIndex: 0,
            enums: {
                inputTypeEnum: inputTypeEnum
            },
            errors: {},
            forms: {},
        };
    },
    computed: {
        paymentGateways: function () {
            const list = this.$store.getters["paymentGateway/lists"] || [];
            const swich = list.filter((g) => g.slug === "swich");
            const paypal = list.filter((g) => g.slug === "paypal");
            const rest = list.filter((g) => g.slug !== "swich" && g.slug !== "paypal");
            return [...swich, ...rest, ...paypal];
        },
    },
    watch: {
        paymentGateways: {
            immediate: true,
            handler() {
                this.syncForms(true);
            },
        },
    },
    mounted() {
        try {
            this.loading.isActive = true;
            this.$store.dispatch("paymentGateway/lists", this.search).then(() => {
                this.syncForms(true);
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        } catch (err) {
            this.loading.isActive = false;
            alertService.error(err);
        }
    },
    methods: {
        isTextOption(option) {
            return Number(option.type) === this.enums.inputTypeEnum.TEXT;
        },
        activitiesMap(option) {
            let activities = option?.activities;
            if (typeof activities === "string" && activities) {
                try {
                    activities = JSON.parse(activities);
                } catch (e) {
                    activities = null;
                }
            }
            if (activities && typeof activities === "object" && !Array.isArray(activities) && Object.keys(activities).length) {
                return activities;
            }
            if (String(option?.option || "").endsWith("_mode")) {
                return { 5: "sandbox", 10: "live" };
            }
            return { 5: "enable", 10: "disable" };
        },
        uniqueOptions(options) {
            const list = Array.isArray(options) ? options : [];
            const byName = {};
            list.forEach((option) => {
                if (!option || !option.option) {
                    return;
                }
                const current = byName[option.option];
                if (!current) {
                    byName[option.option] = option;
                    return;
                }
                const currentFilled = Boolean(current.value);
                const optionFilled = Boolean(option.value);
                if (optionFilled && !currentFilled) {
                    byName[option.option] = option;
                    return;
                }
                if (optionFilled === currentFilled && Number(option.id) > Number(current.id)) {
                    byName[option.option] = option;
                }
            });
            return Object.values(byName);
        },
        syncForms(force = false) {
            this.paymentGateways.forEach((gateway) => {
                if (!this.forms[gateway.slug]) {
                    this.forms[gateway.slug] = {};
                }
                this.uniqueOptions(gateway.options).forEach((option) => {
                    if (force || this.forms[gateway.slug][option.option] === undefined) {
                        this.forms[gateway.slug][option.option] = option.value == null ? "" : String(option.value);
                    }
                });
            });
        },
        save: function (index) {
            try {
                const gateway = this.paymentGateways[index];
                if (!gateway) {
                    return;
                }
                const form = {
                    payment_type: gateway.slug,
                    ...(this.forms[gateway.slug] || {}),
                };

                this.loading.isActive = true;
                this.$store.dispatch("paymentGateway/save", { form: form, search: this.search }).then((res) => {
                    this.loading.isActive = false;
                    alertService.successFlip(res.config.method === "put" ?? 0, this.$t("menu.payment_gateway"));
                    this.errors = {};
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response?.data?.errors || {};
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
        selectActive: function (index) {
            this.selectIndex = index;
        }
    }
};
</script>
