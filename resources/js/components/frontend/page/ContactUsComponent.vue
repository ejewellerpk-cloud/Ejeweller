<template>
    <div class="store-contact-extra mt-8 pt-8 border-t border-gray-100">
        <div v-if="outlets.length > 0" class="mb-8">
            <h3 class="text-xl font-bold text-heading capitalize mb-5">{{ $t('label.outlets') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div v-for="outlet in outlets" :key="outlet.id"
                    class="rounded-xl border border-gray-100 bg-gray-50/80 p-4 sm:p-5 hover:border-primary/30 transition-colors">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center bg-primary text-white flex-shrink-0">
                            <i class="lab lab-line-branches !text-xs"></i>
                        </span>
                        <h4 class="font-semibold text-heading capitalize">{{ outlet.name }}</h4>
                    </div>
                    <ul class="flex flex-col gap-2.5 text-sm text-secondary">
                        <li v-if="outlet.address || outlet.city" class="flex items-start gap-2">
                            <i class="lab lab-line-location flex-shrink-0 mt-0.5 text-primary"></i>
                            <span class="text-heading leading-relaxed">
                                <span v-if="outlet.address">{{ outlet.address }}</span>
                                <span v-if="outlet.city || outlet.state" class="block text-secondary">
                                    <template v-if="outlet.city">{{ outlet.city }}, </template>
                                    <template v-if="outlet.state">{{ outlet.state }}</template>
                                </span>
                            </span>
                        </li>
                        <li v-if="outlet.phone" class="flex items-center gap-2">
                            <i class="lab lab-call-calling text-primary"></i>
                            <span class="text-heading">{{ outlet.country_code }}{{ outlet.phone }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="rounded-xl bg-primary/5 border border-primary/15 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-heading capitalize mb-4">{{ $t('label.support') }}</h3>
            <ul class="flex flex-col gap-3 text-sm">
                <li v-if="setting.company_email" class="flex items-center gap-3">
                    <i class="lab lab-line-mail text-primary"></i>
                    <a :href="'mailto:' + setting.company_email" class="text-heading hover:text-primary break-all">{{ setting.company_email }}</a>
                </li>
                <li v-if="supportPhone" class="flex items-center gap-3">
                    <i class="lab lab-call-calling text-primary"></i>
                    <span class="text-heading font-medium">{{ supportPhone }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import statusEnum from "../../../enums/modules/statusEnum";

export default {
    name: "ContactUsComponent",
    computed: {
        outlets() {
            return this.$store.getters['frontendOutlet/lists'];
        },
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },
        supportPhone() {
            return `${this.setting.company_calling_code || ''}${this.setting.company_phone || ''}`.trim();
        },
    },
    mounted() {
        this.$store.dispatch('frontendOutlet/lists', {
            order_column: 'id',
            order_type: 'asc',
            status: statusEnum.ACTIVE,
        }).then().catch();
    },
};
</script>
