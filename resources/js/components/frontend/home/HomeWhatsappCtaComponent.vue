<template>
    <section v-if="showBlock" class="mb-10 sm:mb-20">
        <div class="container">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#128C7E] to-[#25D366] px-6 py-8 sm:px-10 sm:py-10 text-white">
                <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/10"></div>
                <div class="absolute -left-4 -bottom-4 w-32 h-32 rounded-full bg-white/10"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div class="max-w-xl">
                        <div class="flex items-center gap-3 mb-3">
                            <i class="fa-brands fa-whatsapp text-4xl"></i>
                            <h2 class="text-xl sm:text-2xl font-bold">{{ $t('label.order_on_whatsapp') }}</h2>
                        </div>
                        <p class="text-sm sm:text-base text-white/90 leading-relaxed">
                            {{ $t('message.home_whatsapp_cta') }}
                        </p>
                    </div>
                    <a :href="whatsappUrl" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 flex-shrink-0 px-6 py-3.5 rounded-full bg-white text-[#128C7E] font-bold text-sm sm:text-base hover:scale-[1.02] active:scale-[0.98] transition-transform shadow-lg">
                        <i class="fa-brands fa-whatsapp text-xl"></i>
                        {{ $t('button.chat_on_whatsapp') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import activityEnum from '../../../enums/modules/activityEnum';

export default {
    name: 'HomeWhatsappCtaComponent',
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },
        showBlock() {
            return this.setting
                && this.setting.whatsapp_status === activityEnum.ENABLE
                && this.setting.whatsapp_number;
        },
        whatsappUrl() {
            const phone = (this.setting.whatsapp_calling_code + this.setting.whatsapp_number).replace(/[^0-9]/g, '');
            const text = encodeURIComponent(this.$t('message.home_whatsapp_prefill'));
            return `https://wa.me/${phone}?text=${text}`;
        },
    },
};
</script>
