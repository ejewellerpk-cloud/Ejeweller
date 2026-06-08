<template>
    <div class="inline-flex items-center gap-2 flex-wrap">
        <button v-if="showGallery" type="button" @click="showMediaPicker = true" :disabled="disabled"
            :class="galleryButtonClass">
            <i class="fa-solid fa-images" :class="compact ? 'text-[10px]' : 'text-primary'"></i>
            <span :class="compact ? 'text-xs' : 'text-[10px] font-bold uppercase tracking-wider'">Gallery</span>
        </button>

        <ImageLinkButton :compact="compact" :disabled="disabled" :folder="folder"
            @selected="onLinkSelected" @loading="$emit('loading', $event)" />

        <MediaPickerComponent v-if="showGallery" :show="showMediaPicker" :multiple="multiple"
            @close="showMediaPicker = false" @selected="onGallerySelected" />
    </div>
</template>

<script>
import MediaPickerComponent from './MediaPickerComponent';
import ImageLinkButton from './ImageLinkButton';

export default {
    name: 'ImageUploadSourceButtons',
    components: { MediaPickerComponent, ImageLinkButton },
    props: {
        showGallery: { type: Boolean, default: true },
        multiple: { type: Boolean, default: false },
        compact: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        folder: { type: String, default: 'uploads' },
    },
    emits: ['selected', 'loading'],
    data() {
        return {
            showMediaPicker: false,
        };
    },
    computed: {
        galleryButtonClass() {
            if (this.compact) {
                return 'inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-primary hover:border-primary/40 disabled:opacity-50';
            }
            return 'px-4 h-10 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-all border border-slate-200 flex items-center gap-2 whitespace-nowrap disabled:opacity-50';
        },
    },
    methods: {
        onGallerySelected(assets) {
            this.showMediaPicker = false;
            this.$emit('selected', assets);
        },
        onLinkSelected(asset) {
            this.$emit('selected', asset);
        },
    },
};
</script>
