<template>
    <LoadingComponent :props="loading" />
    <div class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.progressive_web_app") }}</h3>
        </div>
        <div class="mb-4 bg-red-100 p-2 pl-4 ">
            <h2 class="mb-1">{{ $t('label.reminder') }}</h2>
            <p>{{ $t('message.pwa_image_remainder') }}</p>
            <p class="mt-1 text-sm">{{ $t('message.pwa_icon_upload_hint') }}</p>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save">
                <div class="form-row">
                    <div class="form-col-12  sm:form-col-5">
                        <label for="splash" class="db-field-title required">
                            {{ $t("label.splash") }} (2048px,2732px)
                        </label>
                        <input @change="changeSplash" v-bind:class="errors.pwa_splash ? 'invalid' : ''" id="splash"
                            type="file" class="db-field-control" ref="splashProperty"
                            accept="image/png, image/jpeg, image/jpg" />
                        <div class="mt-2">
                            <ImageUploadSourceButtons @selected="(asset) => handlePwaImageSelected('splash', asset)" @loading="loading.isActive = $event" />
                        </div>
                        <small class="db-field-alert" v-if="errors.pwa_splash">{{
                            errors.pwa_splash[0]
                        }}</small>
                    </div>
                    <div class="form-col-12  sm:form-col-5">
                        <label for="icon" class="db-field-title required">
                            {{ $t("label.icon") }} (512×512 px, square)
                        </label>
                        <input @change="changeIcon" v-bind:class="errors.pwa_icon ? 'invalid' : ''" id="icon" type="file"
                            class="db-field-control" ref="iconProperty" accept="image/png, image/jpeg, image/jpg" />
                        <div class="mt-2">
                            <ImageUploadSourceButtons @selected="(asset) => handlePwaImageSelected('icon', asset)" @loading="loading.isActive = $event" />
                        </div>
                        <small class="db-field-alert" v-if="errors.pwa_icon">{{
                            errors.pwa_icon[0]
                        }}</small>
                    </div>

                    <div class="form-col-2 sm:form-col-2 mt-6">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="row mt-4">
                <div class="col-6 sm:col-3">
                    <h3 class="text-lg font-medium capitalize mb-2 text-paragraph">
                        {{ $t("label.splash") }}
                    </h3>
                    <img class="db-image pwa-splash-preview" alt="splash" :src="pwa.splash" />
                </div>
                <div class="col-6 sm:col-3">
                    <h3 class="text-lg font-medium capitalize mb-2 text-paragraph">
                        {{ $t("label.icon") }}
                    </h3>
                    <img class="pwa-icon-preview" alt="icon" :src="pwa.icon" />
                </div>
            </div>

        </div>
    </div>
</template>
<script lang="js">
import LoadingComponent from "../../components/LoadingComponent";
import ImageUploadSourceButtons from "../../media/ImageUploadSourceButtons";
import alertService from "../../../../services/alertService";
import { assetToFile } from "../../../../services/imageUploadService";
export default {
    name: 'PWAComponent',
    components: { LoadingComponent, ImageUploadSourceButtons },
    data() {
        return {
            loading: {
                isActive: false,
            },
            splash: "",
            icon: "",
            errors: {},
        }
    },
    mounted() {
        this.$store.dispatch("pwa/lists");
    },
    computed: {
        pwa: function () {
            return this.$store.getters["pwa/lists"];
        }
    },
    methods: {
        changeSplash: function (e) {
            this.splash = e.target.files[0];
        },
        changeIcon: function (e) {
            this.icon = e.target.files[0];
        },
        async handlePwaImageSelected(field, asset) {
            const items = Array.isArray(asset) ? asset : [asset];
            if (!items.length) {
                return;
            }

            try {
                this.loading.isActive = true;
                const file = await assetToFile(items[0]);

                if (field === 'splash') {
                    this.splash = file;
                    if (this.$refs.splashProperty) {
                        this.$refs.splashProperty.value = null;
                    }
                } else {
                    this.icon = file;
                    if (this.$refs.iconProperty) {
                        this.$refs.iconProperty.value = null;
                    }
                }
            } catch (error) {
                alertService.error("Failed to load image");
            } finally {
                this.loading.isActive = false;
            }
        },
        save: function () {
            try {
                const form = new FormData();
                form.append('pwa_splash', this.splash);
                form.append('pwa_icon', this.icon)

                this.loading.isActive = true;
                this.$store
                    .dispatch("pwa/save", {
                        form: form,
                    })
                    .then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(1, this.$t("menu.progressive_web_app"));
                        this.errors = {};
                        this.$refs.splashProperty.value = null;
                        this.$refs.iconProperty.value = null;
                    })
                    .catch((err) => {
                        this.loading.isActive = false;
                        this.errors = (err.response && err.response.data && err.response.data.errors) ? err.response.data.errors : {};
                        if (err.response && err.response.data) {
                            if (typeof err.response.data.errors === 'object') {
                                Object.values(err.response.data.errors).forEach((error) => {
                                    alertService.error(error[0]);
                                });
                            } else if (err.response.data.message) {
                                alertService.error(err.response.data.message);
                            }
                        }
                    });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        }
    }
}
</script>

<style scoped>
.pwa-icon-preview {
    width: 128px;
    height: 128px;
    object-fit: contain;
    border-radius: 10px;
    border: 5px solid #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    background: #f3f4f6;
}

.pwa-splash-preview {
    max-width: 200px;
    max-height: 267px;
    width: auto;
    height: auto;
    object-fit: contain;
}
</style>