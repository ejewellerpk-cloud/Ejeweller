<template>
    <div class="inline-flex">
        <button type="button" @click="openModal" :disabled="disabled"
            :class="buttonClass">
            <i class="fa-solid fa-link" :class="compact ? 'text-[10px]' : 'text-primary'"></i>
            <span :class="compact ? 'text-xs' : 'text-[10px] font-bold uppercase tracking-wider'">Link</span>
        </button>

        <div v-if="visible" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="closeModal">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-5 border-b flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-heading">Import from URL</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Paste a direct image link (JPG, PNG, WebP, etc.)</p>
                    </div>
                    <button type="button" @click="closeModal" :disabled="importing"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <input type="url" v-model="url" @keyup.enter="apply"
                        placeholder="https://example.com/image.jpg"
                        class="w-full h-11 px-4 bg-slate-100 border border-transparent rounded-xl text-sm outline-none focus:bg-white focus:ring-2 focus:ring-primary/20"
                        :disabled="importing" />
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="closeModal" :disabled="importing"
                            class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 hover:text-slate-700">
                            Cancel
                        </button>
                        <button type="button" @click="apply" :disabled="importing || !url.trim()"
                            class="db-btn py-2 px-5 text-white bg-primary text-xs disabled:opacity-50 flex items-center gap-2">
                            <i v-if="importing" class="fa-solid fa-circle-notch animate-spin"></i>
                            <span>{{ importing ? 'Importing…' : 'Use image' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import alertService from '../../../services/alertService';
import { importImageFromUrl } from '../../../services/imageUploadService';

export default {
    name: 'ImageLinkButton',
    props: {
        compact: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        folder: { type: String, default: 'uploads' },
    },
    emits: ['selected', 'loading'],
    data() {
        return {
            visible: false,
            url: '',
            importing: false,
        };
    },
    computed: {
        buttonClass() {
            if (this.compact) {
                return 'inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-primary hover:border-primary/40 disabled:opacity-50';
            }
            return 'px-4 h-10 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-all border border-slate-200 flex items-center gap-2 whitespace-nowrap disabled:opacity-50';
        },
    },
    methods: {
        openModal() {
            this.url = '';
            this.visible = true;
        },
        closeModal() {
            if (this.importing) {
                return;
            }
            this.visible = false;
            this.url = '';
        },
        async apply() {
            const trimmed = this.url.trim();
            if (!trimmed) {
                return;
            }

            this.importing = true;
            this.$emit('loading', true);

            try {
                const asset = await importImageFromUrl(trimmed, this.folder);
                this.$emit('selected', asset);
                alertService.success('Image imported successfully');
                this.closeModal();
            } catch (error) {
                const message = error?.response?.data?.error
                    || error?.response?.data?.message
                    || 'Failed to import image from URL';
                alertService.error(message);
            } finally {
                this.importing = false;
                this.$emit('loading', false);
            }
        },
    },
};
</script>
