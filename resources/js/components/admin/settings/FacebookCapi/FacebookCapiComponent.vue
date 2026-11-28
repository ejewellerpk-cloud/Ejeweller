<template>
    <LoadingComponent :props="loading" />
    <div class="db-card">
        <div class="db-card-header">
            <h3 class="db-card-title">Facebook Pixel & CAPI Settings</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save">
                <div class="row">
                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="site_facebook_pixel_id">
                            Facebook Pixel ID
                        </label>
                        <input v-model="form.site_facebook_pixel_id" type="text" id="site_facebook_pixel_id"
                            class="db-field-control" />
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="site_facebook_capi_token">
                            Facebook CAPI Token
                        </label>
                        <input v-model="form.site_facebook_capi_token" type="text" id="site_facebook_capi_token"
                            class="db-field-control" />
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="facebook_capi_status_enable">Facebook CAPI Status</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.activityEnum.ENABLE" v-model="form.site_facebook_capi_status"
                                        id="facebook_capi_status_enable" type="radio" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="facebook_capi_status_enable" class="db-field-label">
                                    {{ $t("label.enable") }}
                                </label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.activityEnum.DISABLE" v-model="form.site_facebook_capi_status"
                                        type="radio" id="facebook_capi_status_disable" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="facebook_capi_status_disable" class="db-field-label">
                                    {{ $t("label.disable") }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="site_meta_ad_account_id">
                            Meta Ad Account ID (optional)
                        </label>
                        <input v-model="form.site_meta_ad_account_id" type="text" id="site_meta_ad_account_id"
                            class="db-field-control" placeholder="act_123456789" />
                        <small class="text-gray-500">For Custom Audience sync from Campaigns → Audiences</small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="site_meta_page_id">
                            Meta Page ID (optional)
                        </label>
                        <input v-model="form.site_meta_page_id" type="text" id="site_meta_page_id"
                            class="db-field-control" placeholder="123456789012345" />
                        <small class="text-gray-500">Needed to auto-create ads from product images when you Push to Meta</small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title" for="site_meta_marketing_token">
                            Meta Marketing API Token (optional)
                        </label>
                        <input v-model="form.site_meta_marketing_token" type="text" id="site_meta_marketing_token"
                            class="db-field-control" />
                        <small class="text-gray-500">Needs ads_management permission. CSV export works without this.</small>
                    </div>

                    <div class="form-col-12" v-if="metaStatusMessage">
                        <div class="rounded-lg border px-3 py-2 text-sm"
                            :class="metaStatusOk ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700'">
                            {{ metaStatusMessage }}
                        </div>
                    </div>

                    <div class="form-col-12 flex flex-wrap items-center gap-3">
                        <button type="submit" class="text-white db-btn bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                        <button type="button" class="db-btn border" @click="testMetaConnection">
                            Test Meta connection
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import activityEnum from "../../../../enums/modules/activityEnum";
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";

export default {
    name: "FacebookCapiComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false
            },
            form: {
                site_date_format: null,
                site_time_format: null,
                site_default_timezone: null,
                site_default_currency: null,
                site_default_ai_agent: null,
                site_default_currency_symbol: null,
                site_default_language: null,
                site_language_switch: null,
                site_app_debug: null,
                site_currency_position: null,
                site_email_verification: null,
                site_phone_verification: null,
                site_digit_after_decimal_point: null,
                site_cash_on_delivery: null,
                site_android_app_link: null,
                site_ios_app_link: null,
                site_copyright: null,
                site_online_payment_gateway: null,
                site_default_sms_gateway: null,
                site_is_return_product_price_add_to_credit: null,
                site_whatsapp_status: null,
                site_facebook_pixel_id: null,
                site_facebook_capi_token: null,
                site_facebook_capi_status: null,
                site_meta_ad_account_id: null,
                site_meta_page_id: null,
                site_meta_marketing_token: null,
                site_guest_checkout: null,
            },
            enums: {
                activityEnum: activityEnum,
            },
            metaStatusOk: false,
            metaStatusMessage: "",
        }
    },
    mounted() {
        this.list();
    },
    methods: {
        list: function () {
            this.loading.isActive = true;
            this.$store.dispatch('site/lists').then(res => {
                this.form = {
                    site_date_format: res.data.data.site_date_format,
                    site_time_format: res.data.data.site_time_format,
                    site_default_timezone: res.data.data.site_default_timezone,
                    site_default_currency: res.data.data.site_default_currency ? Number(res.data.data.site_default_currency) : null,
                    site_default_ai_agent: res.data.data.site_default_ai_agent === 0 || !res.data.data.site_default_ai_agent ? null : Number(res.data.data.site_default_ai_agent),
                    site_default_currency_symbol: res.data.data.site_default_currency_symbol,
                    site_default_language: res.data.data.site_default_language ? Number(res.data.data.site_default_language) : null,
                    site_language_switch: res.data.data.site_language_switch,
                    site_app_debug: res.data.data.site_app_debug,
                    site_currency_position: res.data.data.site_currency_position,
                    site_email_verification: res.data.data.site_email_verification,
                    site_phone_verification: res.data.data.site_phone_verification,
                    site_digit_after_decimal_point: res.data.data.site_digit_after_decimal_point,
                    site_cash_on_delivery: res.data.data.site_cash_on_delivery,
                    site_android_app_link: res.data.data.site_android_app_link,
                    site_ios_app_link: res.data.data.site_ios_app_link,
                    site_copyright: res.data.data.site_copyright,
                    site_online_payment_gateway: res.data.data.site_online_payment_gateway,
                    site_default_sms_gateway: res.data.data.site_default_sms_gateway === 0 || !res.data.data.site_default_sms_gateway ? null : Number(res.data.data.site_default_sms_gateway),
                    site_is_return_product_price_add_to_credit: res.data.data.site_is_return_product_price_add_to_credit,
                    site_whatsapp_status: res.data.data.site_whatsapp_status,
                    site_facebook_pixel_id: res.data.data.site_facebook_pixel_id,
                    site_facebook_capi_token: res.data.data.site_facebook_capi_token,
                    site_facebook_capi_status: res.data.data.site_facebook_capi_status !== null ? Number(res.data.data.site_facebook_capi_status) : null,
                    site_meta_ad_account_id: res.data.data.site_meta_ad_account_id,
                    site_meta_page_id: res.data.data.site_meta_page_id,
                    site_meta_marketing_token: res.data.data.site_meta_marketing_token,
                    site_guest_checkout: res.data.data.site_guest_checkout,
                }
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        save: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch("site/save", this.form).then((res) => {
                    this.loading.isActive = false;
                    alertService.successFlip(1, "Facebook CAPI Settings");
                    this.list();
                    this.$store.dispatch('frontendSetting/lists').then().catch();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || "An error occurred");
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
        async testMetaConnection() {
            try {
                this.loading.isActive = true;
                const res = await axios.get("admin/campaigns/meta/status");
                const data = res.data?.data || {};
                this.metaStatusOk = !!data.ok;
                this.metaStatusMessage = data.ok
                    ? `Connected: ${data.name || data.ad_account_id} (${data.currency || "n/a"})`
                    : (data.message || res.data?.message || "Connection failed");
                if (data.ok) {
                    alertService.success(this.metaStatusMessage);
                } else {
                    alertService.error(this.metaStatusMessage);
                }
            } catch (err) {
                this.metaStatusOk = false;
                this.metaStatusMessage = err.response?.data?.data?.message
                    || err.response?.data?.message
                    || err.message
                    || "Connection failed";
                alertService.error(this.metaStatusMessage);
            } finally {
                this.loading.isActive = false;
            }
        },
    }
}
</script>
