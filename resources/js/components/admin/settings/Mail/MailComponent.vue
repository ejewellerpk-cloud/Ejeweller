<template>
    <LoadingComponent :props="loading" />

    <div id="mail" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.mail") }}</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save" class="w-full d-block">
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_host" class="db-field-title required">
                            {{ $t("label.mail_host") }}
                        </label>
                        <input v-model="form.mail_host" v-bind:class="errors.mail_host ? 'invalid' : ''" type="text"
                            id="mail_host" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_host">
                            {{ errors.mail_host[0] }}
                        </small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_port" class="db-field-title required">{{ $t("label.mail_port") }}</label>
                        <input v-model="form.mail_port" v-bind:class="errors.mail_port ? 'invalid' : ''" type="text"
                            id="mail_port" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_port">
                            {{ errors.mail_port[0] }}
                        </small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_username" class="db-field-title required">
                            {{ $t("label.mail_username") }}
                        </label>
                        <input v-model="form.mail_username" v-bind:class="errors.mail_username ? 'invalid' : ''" type="text"
                            id="mail_username" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_username">{{ errors.mail_username[0] }}</small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_password" class="db-field-title">
                            {{ $t("label.mail_password") }}
                        </label>
                        <input v-model="form.mail_password" v-bind:class="errors.mail_password ? 'invalid' : ''" type="text"
                            id="mail_password" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_password">{{ errors.mail_password[0] }}</small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_from_name" class="db-field-title required">
                            {{ $t("label.mail_from_name") }}
                        </label>
                        <input v-model="form.mail_from_name" v-bind:class="errors.mail_from_name ? 'invalid' : ''"
                            type="text" id="mail_from_name" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_from_name">
                            {{ errors.mail_from_name[0] }}
                        </small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="mail_from_email" class="db-field-title required">
                            {{ $t("label.mail_from_email") }}
                        </label>
                        <input v-model="form.mail_from_email" v-bind:class="errors.mail_from_email ? 'invalid' : ''"
                            type="text" id="mail_from_email" class="db-field-control" />
                        <small class="db-field-alert" v-if="errors.mail_from_email">
                            {{ errors.mail_from_email[0] }}
                        </small>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label class="db-field-title required" for="active">
                            {{ $t("label.mail_encryption") }}
                        </label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.encryptionEnum.SSL" v-model="form.mail_encryption" id="ssl"
                                        type="radio" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="ssl" class="db-field-label">
                                    {{ $t("label.ssl") }}
                                </label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input :value="enums.encryptionEnum.TLS" v-model="form.mail_encryption" type="radio"
                                        id="tls" class="custom-radio-field" />
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="tls" class="db-field-label">
                                    {{ $t("label.tls") }}
                                </label>
                            </div>
                        </div>
                        <small class="db-field-alert" v-if="errors.mail_encryption">
                            {{ errors.mail_encryption[0] }}
                        </small>
                    </div>
                    <div class="form-col-12">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h4 class="db-card-title mb-1">{{ $t("label.test_email") || "Test Email" }}</h4>
                <p class="text-sm text-slate-500 mb-4">
                    {{ $t("label.test_email_hint") || "Save mail settings first, then send a test message to verify SMTP." }}
                </p>
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6">
                        <label for="test_email" class="db-field-title required">
                            {{ $t("label.email") || "Email" }}
                        </label>
                        <input
                            v-model="testEmail"
                            v-bind:class="testErrors.email ? 'invalid' : ''"
                            type="email"
                            id="test_email"
                            class="db-field-control"
                            placeholder="you@example.com"
                        />
                        <small class="db-field-alert" v-if="testErrors.email">
                            {{ testErrors.email[0] }}
                        </small>
                    </div>
                    <div class="form-col-12 flex items-end">
                        <button
                            type="button"
                            class="db-btn text-white bg-gray-700"
                            :disabled="testLoading"
                            @click="sendTestEmail"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>{{ $t("button.send_test_email") || "Send Test Email" }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import encryptionEnum from "../../../../enums/modules/encryptionEnum";

export default {
    name: "MailComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            enums: {
                encryptionEnum: encryptionEnum,
                encryptionEnumArray: {
                    [encryptionEnum.SSL]: this.$t("label.ssl"),
                    [encryptionEnum.TLS]: this.$t("label.tls"),
                },
            },
            form: {
                mail_host: "",
                mail_port: "",
                mail_username: "",
                mail_password: "",
                mail_encryption: "",
                mail_from_name: "",
                mail_from_email: "",
            },
            errors: {},
            testEmail: "",
            testLoading: false,
            testErrors: {},
        };
    },
    mounted() {
        try {
            const authEmail = this.$store.getters["auth/authInfo"]?.email;
            if (authEmail) {
                this.testEmail = authEmail;
            }
            this.loading.isActive = true;
            this.$store
                .dispatch("mail/lists")
                .then((res) => {
                    this.form = {
                        mail_host: res.data.data.mail_host,
                        mail_port: res.data.data.mail_port,
                        mail_username: res.data.data.mail_username,
                        mail_password: res.data.data.mail_password,
                        mail_encryption: res.data.data.mail_encryption,
                        mail_from_name: res.data.data.mail_from_name,
                        mail_from_email: res.data.data.mail_from_email,
                    };
                    if (!this.testEmail && res.data.data.mail_from_email) {
                        this.testEmail = res.data.data.mail_from_email;
                    }
                    this.loading.isActive = false;
                })
                .catch((err) => {
                    this.loading.isActive = false;
                });
        } catch (err) {
            this.loading.isActive = false;
            alertService.error(err);
        }
    },
    methods: {
        save: function () {
            try {
                this.loading.isActive = true;
                this.$store
                    .dispatch("mail/save", this.form)
                    .then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(
                            res.config.method === "put" ?? 0,
                            this.$t("menu.mail")
                        );
                        this.errors = {};
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
        sendTestEmail: function () {
            const email = String(this.testEmail || "").trim();
            if (!email) {
                this.testErrors = { email: ["Email is required."] };
                return;
            }
            this.testErrors = {};
            this.testLoading = true;
            this.loading.isActive = true;
            this.$store
                .dispatch("mail/sendTest", { email })
                .then((res) => {
                    alertService.success(res.data?.message || "Test email sent successfully");
                })
                .catch((err) => {
                    if (err.response?.data?.errors) {
                        this.testErrors = err.response.data.errors;
                    }
                    alertService.error(err.response?.data?.message || err.message || "Failed to send test email");
                })
                .finally(() => {
                    this.testLoading = false;
                    this.loading.isActive = false;
                });
        },
    },
};
</script>
