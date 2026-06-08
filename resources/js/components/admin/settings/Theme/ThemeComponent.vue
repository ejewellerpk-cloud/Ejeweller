<template>
    <LoadingComponent :props="loading" />

    <div id="theme" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.theme") }}</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save" class="w-full d-block">
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6">
                        <label for="theme_logo" class="db-field-title">
                            {{ $t("label.logo") }} (128px,43px)
                        </label>
                        <input @change="changeLogo" v-bind:class="errors.theme_logo ? 'invalid' : ''" id="theme_logo"
                            type="file" class="db-field-control" ref="themeLogoProperty"
                            accept="image/png, image/jpeg, image/jpg" />
                        <div class="mt-2">
                            <ImageUploadSourceButtons @selected="(asset) => handleThemeImageSelected('logo', asset)" @loading="loading.isActive = $event" />
                        </div>
                        <small class="db-field-alert" v-if="errors.theme_logo">{{
                            errors.theme_logo[0]
                        }}</small>
                        <img class="w-[150px] h-[120px] object-fill rounded-lg mt-2" alt="logo" v-if="theme_logo_reader"
                            :src="theme_logo_reader" />
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="fav_icon" class="db-field-title">
                            {{ $t("label.fav_icon") }} (120px,120px)
                        </label>
                        <input @change="changeFavIcon" v-bind:class="errors.theme_favicon_logo ? 'invalid' : ''"
                            id="fav_icon" type="file" class="db-field-control" ref="themeFaviconLogoProperty"
                            accept="image/png, image/jpeg, image/jpg" />
                        <div class="mt-2">
                            <ImageUploadSourceButtons @selected="(asset) => handleThemeImageSelected('favicon', asset)" @loading="loading.isActive = $event" />
                        </div>
                        <small class="db-field-alert" v-if="errors.theme_favicon_logo">{{
                            errors.theme_favicon_logo[0]
                        }}</small>

                        <img class="w-[120px] h-[120px] object-fill rounded-lg mt-2" alt="logo"
                            v-if="theme_favicon_logo_reader" :src="theme_favicon_logo_reader" />
                    </div>
                    <div class="form-col-12 sm:form-col-6">
                        <label for="footer_logo" class="db-field-title">
                            {{ $t("label.footer_logo") }} (144px,48px)
                        </label>
                        <input @change="changeFooterLogo" v-bind:class="errors.theme_footer_logo ? 'invalid' : ''"
                            id="fav_icon" type="file" class="db-field-control" ref="themeFooterLogoProperty"
                            accept="image/png, image/jpeg, image/jpg" />
                        <div class="mt-2">
                            <ImageUploadSourceButtons @selected="(asset) => handleThemeImageSelected('footer', asset)" @loading="loading.isActive = $event" />
                        </div>
                        <small class="db-field-alert" v-if="errors.theme_footer_logo">{{
                            errors.theme_footer_logo[0]
                        }}</small>

                        <img class="w-[150px] h-[120px] object-fill rounded-lg mt-2" alt="logo"
                            v-if="theme_footer_logo_reader" :src="theme_footer_logo_reader" />
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
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import ImageUploadSourceButtons from "../../media/ImageUploadSourceButtons";
import alertService from "../../../../services/alertService";
import { assetToFile } from "../../../../services/imageUploadService";

export default {
    name: "ThemeComponent",
    components: { LoadingComponent, ImageUploadSourceButtons },
    data() {
        return {
            loading: {
                isActive: false,
            },
            theme_logo: "",
            theme_logo_reader: "",
            theme_favicon_logo: "",
            theme_favicon_logo_reader: "",
            theme_footer_logo: "",
            theme_footer_logo_reader: "",
            errors: {},
        };
    },
    mounted() {
        this.list();
    },
    methods: {
        changeLogo: function (e) {
            this.theme_logo = e.target.files[0];
        },
        changeFavIcon: function (e) {
            this.theme_favicon_logo = e.target.files[0];
        },
        changeFooterLogo: function (e) {
            this.theme_footer_logo = e.target.files[0];
        },
        async handleThemeImageSelected(field, asset) {
            const items = Array.isArray(asset) ? asset : [asset];
            if (!items.length) {
                return;
            }

            try {
                this.loading.isActive = true;
                const file = await assetToFile(items[0]);

                if (field === 'logo') {
                    this.theme_logo = file;
                    this.theme_logo_reader = items[0].url;
                    if (this.$refs.themeLogoProperty) {
                        this.$refs.themeLogoProperty.value = null;
                    }
                } else if (field === 'favicon') {
                    this.theme_favicon_logo = file;
                    this.theme_favicon_logo_reader = items[0].url;
                    if (this.$refs.themeFaviconLogoProperty) {
                        this.$refs.themeFaviconLogoProperty.value = null;
                    }
                } else if (field === 'footer') {
                    this.theme_footer_logo = file;
                    this.theme_footer_logo_reader = items[0].url;
                    if (this.$refs.themeFooterLogoProperty) {
                        this.$refs.themeFooterLogoProperty.value = null;
                    }
                }
            } catch (error) {
                alertService.error("Failed to load image");
            } finally {
                this.loading.isActive = false;
            }
        },
        list: function () {
            this.loading.isActive = true;
            this.$store
                .dispatch("theme/lists")
                .then((res) => {
                    this.theme_logo_reader = res.data.data.theme_logo;
                    this.theme_favicon_logo_reader = res.data.data.theme_favicon_logo;
                    this.theme_footer_logo_reader = res.data.data.theme_footer_logo;
                    this.loading.isActive = false;
                })
                .catch((err) => {
                    this.loading.isActive = false;
                });
        },
        save: function () {
            try {
                const fd = new FormData();
                if (this.theme_logo) {
                    fd.append("theme_logo", this.theme_logo);
                }
                if (this.theme_favicon_logo) {
                    fd.append("theme_favicon_logo", this.theme_favicon_logo);
                }
                if (this.theme_footer_logo) {
                    fd.append("theme_footer_logo", this.theme_footer_logo);
                }
                this.loading.isActive = true;
                this.$store
                    .dispatch("theme/save", {
                        form: fd,
                    })
                    .then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(1, this.$t("menu.theme"));
                        this.list();
                        this.theme_logo = "";
                        this.theme_favicon_logo = "";
                        this.theme_footer_logo = "";
                        this.errors = {};
                        this.$refs.themeLogoProperty.value = null;
                        this.$refs.themeFaviconLogoProperty.value = null;
                        this.$refs.themeFooterLogoProperty.value = null;
                    })
                    .catch((err) => {
                        this.loading.isActive = false;
                        this.errors = err.response.data.errors;
                    });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
    },
};
</script>
