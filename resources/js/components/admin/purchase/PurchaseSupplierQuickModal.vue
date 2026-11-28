<template>
    <div id="purchaseSupplierModal" class="modal">
        <div class="modal-dialog max-w-lg">
            <div class="modal-header">
                <h3 class="modal-title">{{ $t('button.add_supplier') }}</h3>
                <button
                    type="button"
                    class="modal-close fa-solid fa-xmark text-xl text-slate-400 hover:text-red-500"
                    @click="close"
                ></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="save">
                    <div class="form-row">
                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="pq_company">{{ $t('label.company') }}</label>
                            <input id="pq_company" v-model="form.company" type="text" class="db-field-control" :class="errors.company ? 'invalid' : ''" />
                            <small class="db-field-alert" v-if="errors.company">{{ errors.company[0] }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="pq_name">{{ $t('label.name') }}</label>
                            <input id="pq_name" v-model="form.name" type="text" class="db-field-control" :class="errors.name ? 'invalid' : ''" />
                            <small class="db-field-alert" v-if="errors.name">{{ errors.name[0] }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title" for="pq_email">{{ $t('label.email') }}</label>
                            <input id="pq_email" v-model="form.email" type="email" class="db-field-control" :class="errors.email ? 'invalid' : ''" />
                            <small class="db-field-alert" v-if="errors.email">{{ errors.email[0] }}</small>
                        </div>
                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title" for="pq_phone">{{ $t('label.phone') }}</label>
                            <div class="db-field-control flex items-center" :class="errors.phone ? 'invalid' : ''">
                                <span class="text-xs text-slate-500 whitespace-nowrap pr-2 border-r border-slate-200">{{ form.country_code }}</span>
                                <input
                                    id="pq_phone"
                                    v-model="form.phone"
                                    type="text"
                                    class="pl-2 text-sm w-full h-full"
                                    @keypress="phoneNumber($event)"
                                />
                            </div>
                            <small class="db-field-alert" v-if="errors.phone">{{ errors.phone[0] }}</small>
                        </div>
                        <div class="form-col-12">
                            <label class="db-field-title" for="pq_address">{{ $t('label.address') }}</label>
                            <textarea id="pq_address" v-model="form.address" rows="2" class="db-field-control" :class="errors.address ? 'invalid' : ''"></textarea>
                            <small class="db-field-alert" v-if="errors.address">{{ errors.address[0] }}</small>
                        </div>
                    </div>
                    <div class="modal-btns mt-4">
                        <button type="button" class="modal-btn-outline" @click="close">
                            <i class="lab lab-fill-close-circle"></i>
                            <span>{{ $t('button.close') }}</span>
                        </button>
                        <button type="submit" class="db-btn py-2 text-white bg-primary" :disabled="busy">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t('button.save') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";

export default {
    name: "PurchaseSupplierQuickModal",
    emits: ["created"],
    data() {
        return {
            busy: false,
            errors: {},
            form: {
                company: "",
                name: "",
                email: "",
                phone: "",
                country_code: "+92",
                address: "",
            },
        };
    },
    mounted() {
        this.$store.dispatch("company/lists").then((companyRes) => {
            const code = companyRes?.data?.data?.company_country_code;
            if (!code) {
                return;
            }
            this.$store.dispatch("countryCode/show", code).then((res) => {
                if (res?.data?.data?.calling_code) {
                    this.form.country_code = res.data.data.calling_code;
                }
            }).catch(() => {});
        }).catch(() => {});
    },
    methods: {
        phoneNumber(e) {
            return appService.phoneNumber(e);
        },
        open() {
            this.errors = {};
            appService.modalShow("#purchaseSupplierModal");
        },
        close() {
            appService.modalHide("#purchaseSupplierModal");
            this.resetForm();
        },
        resetForm() {
            this.errors = {};
            this.form.company = "";
            this.form.name = "";
            this.form.email = "";
            this.form.phone = "";
            this.form.address = "";
        },
        save() {
            const fd = new FormData();
            fd.append("company", this.form.company);
            fd.append("name", this.form.name);
            fd.append("email", this.form.email || "");
            fd.append("phone", this.form.phone || "");
            fd.append("country_code", this.form.country_code || "+92");
            fd.append("address", this.form.address || "");
            fd.append("country", "");
            fd.append("state", "");
            fd.append("city", "");
            fd.append("zip_code", "");

            this.busy = true;
            this.$store
                .dispatch("supplier/save", { form: fd, search: { vuex: true } })
                .then((res) => {
                    this.busy = false;
                    const supplier = res?.data?.data;
                    alertService.successFlip(0, this.$t("menu.suppliers"));
                    this.close();
                    if (supplier?.id) {
                        this.$emit("created", supplier);
                    }
                })
                .catch((err) => {
                    this.busy = false;
                    this.errors = err.response?.data?.errors || {};
                    if (err.response?.data?.message && !Object.keys(this.errors).length) {
                        alertService.error(err.response.data.message);
                    }
                });
        },
    },
};
</script>
