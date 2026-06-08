<template>
    <LoadingComponent :props="loading" />

    <div id="related-products-carousel" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t('menu.related_products_carousel') }}</h3>
        </div>
        <div class="db-card-body">
            <p class="text-sm text-gray-500 mb-5">
                {{ $t('message.related_products_carousel_hint') }}
            </p>

            <form @submit.prevent="save">
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title required">{{ $t('label.carousel_status') }}</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input
                                        v-model="form.related_products_carousel_status"
                                        :value="enums.activityEnum.ENABLE"
                                        id="related_carousel_enable"
                                        type="radio"
                                        class="custom-radio-field"
                                    />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="related_carousel_enable" class="db-field-label">
                                    {{ $t('label.enable') }}
                                </label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input
                                        v-model="form.related_products_carousel_status"
                                        :value="enums.activityEnum.DISABLE"
                                        id="related_carousel_disable"
                                        type="radio"
                                        class="custom-radio-field"
                                    />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="related_carousel_disable" class="db-field-label">
                                    {{ $t('label.disable') }}
                                </label>
                            </div>
                        </div>
                        <small class="db-field-alert" v-if="errors.related_products_carousel_status">
                            {{ errors.related_products_carousel_status[0] }}
                        </small>
                    </div>

                    <div
                        v-if="form.related_products_carousel_status === enums.activityEnum.ENABLE"
                        class="form-col-12 sm:form-col-6"
                    >
                        <label for="related_products_carousel_speed" class="db-field-title required">
                            {{ $t('label.carousel_autoplay_speed') }}
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model.number="form.related_products_carousel_speed"
                                id="related_products_carousel_speed"
                                type="range"
                                min="2000"
                                max="10000"
                                step="200"
                                class="w-full accent-primary"
                            />
                            <span class="text-sm font-semibold text-heading whitespace-nowrap min-w-[4.5rem] text-right">
                                {{ (form.related_products_carousel_speed / 1000).toFixed(1) }}s
                            </span>
                        </div>
                        <small class="text-xs text-gray-400 mt-1 block">
                            {{ $t('message.carousel_speed_hint') }}
                        </small>
                        <small class="db-field-alert" v-if="errors.related_products_carousel_speed">
                            {{ errors.related_products_carousel_speed[0] }}
                        </small>
                    </div>

                    <div class="form-col-12 mt-4">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t('button.save') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from '../../components/LoadingComponent';
import alertService from '../../../../services/alertService';
import activityEnum from '../../../../enums/modules/activityEnum';

export default {
    name: 'RelatedProductsCarouselComponent',
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            enums: {
                activityEnum,
            },
            form: {
                related_products_carousel_status: activityEnum.ENABLE,
                related_products_carousel_speed: 3800,
            },
            errors: {},
        };
    },
    mounted() {
        this.list();
    },
    methods: {
        list() {
            this.loading.isActive = true;
            this.$store
                .dispatch('relatedProductsCarousel/lists')
                .then((res) => {
                    const data = res.data.data || {};
                    if (data.related_products_carousel_status !== undefined) {
                        this.form.related_products_carousel_status = Number(data.related_products_carousel_status);
                    }
                    if (data.related_products_carousel_speed !== undefined) {
                        this.form.related_products_carousel_speed = Number(data.related_products_carousel_speed);
                    }
                    this.loading.isActive = false;
                })
                .catch(() => {
                    this.loading.isActive = false;
                });
        },
        save() {
            this.loading.isActive = true;
            this.$store
                .dispatch('relatedProductsCarousel/save', {
                    related_products_carousel_status: Number(this.form.related_products_carousel_status),
                    related_products_carousel_speed: Number(this.form.related_products_carousel_speed),
                })
                .then(() => {
                    this.loading.isActive = false;
                    this.errors = {};
                    alertService.successFlip(1, this.$t('menu.related_products_carousel'));
                })
                .catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response?.data?.errors || {};
                });
        },
    },
};
</script>
