<template>
    <LoadingComponent :props="loading" />

    <div id="product-page" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.product_page") }}</h3>
        </div>
        <div class="db-card-body">
            <p class="text-sm text-gray-500 mb-5">{{ $t("message.product_page_hint") }}</p>

            <form @submit.prevent="save" class="w-full d-block">
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6">
                        <div class="flex items-center justify-between">
                            <label class="db-field-title mb-0">{{ $t("label.related_products_section") }}</label>
                            <div class="custom-switch">
                                <input
                                    id="product_page_related_status"
                                    type="checkbox"
                                    :checked="isEnabled(form.product_page_related_status)"
                                    @change="form.product_page_related_status = toggleActivity(form.product_page_related_status)"
                                />
                                <label for="product_page_related_status">
                                    {{ isEnabled(form.product_page_related_status) ? $t("label.on") : $t("label.off") }}
                                </label>
                            </div>
                        </div>
                        <small v-if="errors.product_page_related_status" class="db-field-alert">
                            {{ errors.product_page_related_status[0] }}
                        </small>
                    </div>

                    <template v-if="isEnabled(form.product_page_related_status)">
                        <div class="form-col-12 sm:form-col-6">
                            <div class="flex items-center justify-between">
                                <label class="db-field-title mb-0">{{ $t("label.auto_scroll") }}</label>
                                <div class="custom-switch">
                                    <input
                                        id="product_page_related_autoscroll"
                                        type="checkbox"
                                        :checked="isEnabled(form.product_page_related_autoscroll)"
                                        @change="form.product_page_related_autoscroll = toggleActivity(form.product_page_related_autoscroll)"
                                    />
                                    <label for="product_page_related_autoscroll">
                                        {{ isEnabled(form.product_page_related_autoscroll) ? $t("label.on") : $t("label.off") }}
                                    </label>
                                </div>
                            </div>
                            <small v-if="errors.product_page_related_autoscroll" class="db-field-alert">
                                {{ errors.product_page_related_autoscroll[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <div class="flex items-center justify-between">
                                <label class="db-field-title mb-0">{{ $t("label.touch_interaction") }}</label>
                                <div class="custom-switch">
                                    <input
                                        id="product_page_related_touch"
                                        type="checkbox"
                                        :checked="isEnabled(form.product_page_related_touch)"
                                        @change="form.product_page_related_touch = toggleActivity(form.product_page_related_touch)"
                                    />
                                    <label for="product_page_related_touch">
                                        {{ isEnabled(form.product_page_related_touch) ? $t("label.on") : $t("label.off") }}
                                    </label>
                                </div>
                            </div>
                            <small v-if="errors.product_page_related_touch" class="db-field-alert">
                                {{ errors.product_page_related_touch[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title">{{ $t("label.scroll_direction") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input
                                            type="radio"
                                            v-model="form.product_page_related_direction"
                                            id="product_page_direction_rtl"
                                            value="rtl"
                                            class="custom-radio-field"
                                        />
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="product_page_direction_rtl" class="db-field-label">
                                        {{ $t("label.right_to_left") }}
                                    </label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input
                                            type="radio"
                                            v-model="form.product_page_related_direction"
                                            id="product_page_direction_ltr"
                                            value="ltr"
                                            class="custom-radio-field"
                                        />
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="product_page_direction_ltr" class="db-field-label">
                                        {{ $t("label.left_to_right") }}
                                    </label>
                                </div>
                            </div>
                            <small v-if="errors.product_page_related_direction" class="db-field-alert">
                                {{ errors.product_page_related_direction[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="product_page_related_speed" class="db-field-title">
                                {{ $t("label.scroll_speed") }}
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="form.product_page_related_speed"
                                    id="product_page_related_speed"
                                    type="range"
                                    min="2000"
                                    max="10000"
                                    step="200"
                                    class="w-full accent-primary"
                                />
                                <span class="text-sm font-semibold text-heading whitespace-nowrap min-w-[4.5rem] text-right">
                                    {{ (form.product_page_related_speed / 1000).toFixed(1) }}s
                                </span>
                            </div>
                            <small class="text-xs text-gray-400 mt-1 block">{{ $t("message.carousel_speed_hint") }}</small>
                            <small v-if="errors.product_page_related_speed" class="db-field-alert">
                                {{ errors.product_page_related_speed[0] }}
                            </small>
                        </div>
                    </template>

                    <div class="form-col-12 mt-4">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import activityEnum from "../../../../enums/modules/activityEnum";

export default {
    name: "ProductPageComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: { isActive: false },
            enums: { activityEnum },
            form: {
                product_page_related_status: activityEnum.ENABLE,
                product_page_related_autoscroll: activityEnum.ENABLE,
                product_page_related_speed: 3800,
                product_page_related_touch: activityEnum.ENABLE,
                product_page_related_direction: "rtl",
            },
            errors: {},
        };
    },
    mounted() {
        this.list();
    },
    methods: {
        isEnabled(value) {
            return Number(value) === activityEnum.ENABLE;
        },
        toggleActivity(value) {
            return this.isEnabled(value) ? activityEnum.DISABLE : activityEnum.ENABLE;
        },
        list() {
            this.loading.isActive = true;
            this.$store
                .dispatch("productPage/lists")
                .then((res) => {
                    const data = res.data.data || {};
                    Object.keys(this.form).forEach((key) => {
                        if (data[key] !== undefined) {
                            this.form[key] =
                                key === "product_page_related_direction"
                                    ? data[key]
                                    : Number(data[key]);
                        }
                    });
                    this.loading.isActive = false;
                })
                .catch(() => {
                    this.loading.isActive = false;
                });
        },
        save() {
            this.loading.isActive = true;
            const payload = {
                product_page_related_status: Number(this.form.product_page_related_status),
                product_page_related_autoscroll: Number(this.form.product_page_related_autoscroll),
                product_page_related_speed: Number(this.form.product_page_related_speed),
                product_page_related_touch: Number(this.form.product_page_related_touch),
                product_page_related_direction: this.form.product_page_related_direction,
            };

            this.$store
                .dispatch("productPage/save", payload)
                .then(() => {
                    this.loading.isActive = false;
                    this.errors = {};
                    alertService.successFlip(1, this.$t("menu.product_page"));
                })
                .catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err?.response?.data?.errors || {};
                });
        },
    },
};
</script>
