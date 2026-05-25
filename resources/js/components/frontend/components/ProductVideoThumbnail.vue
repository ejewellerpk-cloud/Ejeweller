<template>
    <div class="product-video-thumbnail relative w-full h-full overflow-hidden bg-black" :class="rootClass">
        <img
            v-if="posterUrl"
            :src="posterUrl"
            alt=""
            class="w-full h-full pointer-events-none"
            :class="fitClass"
            loading="lazy"
        />
        <video
            v-else-if="src && !failed"
            ref="videoEl"
            :src="src"
            preload="metadata"
            muted
            playsinline
            webkit-playsinline
            disablePictureInPicture
            class="w-full h-full pointer-events-none"
            :class="fitClass"
            @loadedmetadata="onLoadedMetadata"
            @seeked="onSeeked"
            @error="onError"
        ></video>
        <div v-else class="w-full h-full flex items-center justify-center bg-black">
            <i class="fa-solid fa-play text-white opacity-60" :class="playIconClass"></i>
        </div>

        <div
            v-if="showPlayOverlay"
            class="absolute inset-0 flex items-center justify-center pointer-events-none z-10 bg-black/25"
        >
            <i class="fa-solid fa-play text-white drop-shadow-md" :class="playIconClass"></i>
        </div>
    </div>
</template>

<script>
import { getVideoPoster, setVideoPoster } from '../../../utils/productVideoPosterCache';

export default {
    name: 'ProductVideoThumbnail',
    props: {
        src: { type: String, default: '' },
        fitClass: { type: String, default: 'object-cover' },
        rootClass: { type: String, default: '' },
        showPlayOverlay: { type: Boolean, default: true },
        playIconClass: { type: String, default: 'text-base' },
    },
    data() {
        return {
            posterUrl: null,
            failed: false,
            capturing: false,
        };
    },
    watch: {
        src: {
            immediate: true,
            handler(val) {
                this.failed = false;
                this.capturing = false;
                this.posterUrl = val ? getVideoPoster(val) : null;
            },
        },
    },
    mounted() {
        if (this.src && !this.posterUrl) {
            this.scheduleCapture();
        }
    },
    methods: {
        scheduleCapture() {
            requestAnimationFrame(() => {
                if (!this.posterUrl && this.src && this.$refs.videoEl) {
                    this.onLoadedMetadata();
                }
            });
        },
        onLoadedMetadata() {
            if (this.posterUrl || this.capturing || !this.$refs.videoEl) {
                return;
            }
            const video = this.$refs.videoEl;
            this.capturing = true;
            const targetTime = 0.05;
            try {
                video.currentTime = targetTime;
            } catch (e) {
                this.capturing = false;
            }
        },
        onSeeked() {
            if (this.posterUrl || !this.$refs.videoEl) {
                return;
            }
            const video = this.$refs.videoEl;
            try {
                if (video.videoWidth > 0 && video.videoHeight > 0) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    if (ctx) {
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.82);
                        setVideoPoster(this.src, dataUrl);
                        this.posterUrl = dataUrl;
                    }
                }
                video.pause();
                video.removeAttribute('src');
                video.load();
            } catch (e) {
                /* CORS or codec — keep video frame visible */
                video.pause();
            } finally {
                this.capturing = false;
            }
        },
        onError() {
            this.failed = true;
            this.capturing = false;
        },
    },
};
</script>
