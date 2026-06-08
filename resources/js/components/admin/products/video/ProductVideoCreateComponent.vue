<template>
    <LoadingComponent :props="loading" />
    <SmModalCreateComponent @click="createButtonClick" :props="addButton" />

    <div id="videoModal" class="modal">
        <div class="modal-dialog max-w-2xl">
            <div class="modal-header">
                <h3 class="modal-title">{{ $t("menu.product_video") }}</h3>
                <button class="modal-close fa-solid fa-xmark text-xl text-slate-400 hover:text-red-500" @click.prevent="reset"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="save">
                    <div class="form-row">
                        <div class="form-col-12">
                            <label for="video_provider" class="db-field-title required">{{$t("label.video_provider") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="video_provider"
                                v-bind:class="errors.video_provider ? 'invalid' : ''" v-model="form.video_provider"
                                :options="enums.videoProviderEnum" label-by="name" value-by="id" :closeOnSelect="true"
                                :searchable="true" :clearOnClose="true" placeholder="--" search-placeholder="--" />
                            <small class="db-field-alert" v-if="errors.video_provider">
                                {{ errors.video_provider[0] }}
                            </small>
                        </div>

                        <div class="form-col-12" v-if="Number(form.video_provider) && Number(form.video_provider) !== 20">
                            <label for="link" class="db-field-title required">{{ $t("label.link") }}</label>
                            <textarea v-model="form.link" v-bind:class="errors.link ? 'invalid' : ''" id="link"
                                class="db-field-control" rows="2" placeholder="https://"></textarea>
                            <small class="db-field-alert" v-if="errors.link">
                                {{ errors.link[0] }}
                            </small>
                        </div>

                        <div class="form-col-12" v-if="Number(form.video_provider) === 20">
                            <label for="file" class="db-field-title" :class="{ required: !hasExistingVideo && !form.file }">
                                {{ $t("label.video") }} (Max: 10MB)
                            </label>

                            <div v-if="videoPreviewUrl" class="mb-3 rounded-xl border border-slate-200 bg-slate-900 overflow-hidden">
                                <video
                                    :key="videoPreviewUrl"
                                    :src="videoPreviewUrl"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    class="w-full max-h-52 object-contain bg-black"
                                ></video>
                                <p v-if="hasExistingVideo && !form.file" class="px-3 py-2 text-[11px] text-slate-500 bg-slate-50 border-t border-slate-200">
                                    Current uploaded video — choose a new file below only if you want to replace it.
                                </p>
                            </div>

                            <input type="file" @change="onFileChange" v-bind:class="errors.file ? 'invalid' : ''" id="file"
                                class="db-field-control" accept="video/*">
                            <small class="db-field-alert" v-if="errors.file">
                                {{ errors.file[0] }}
                            </small>
                        </div>

                        <!-- Thumbnail picker -->
                        <div class="form-col-12" v-if="Number(form.video_provider)">
                            <label class="db-field-title">{{ $t('label.thumbnail') || 'Video Thumbnail' }}</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <div class="relative w-full sm:w-36 h-36 rounded-xl overflow-hidden border border-slate-200 bg-white flex-shrink-0">
                                        <img v-if="thumbnailPreview" :src="thumbnailPreview" alt="Video thumbnail"
                                            class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-2 p-3 text-center">
                                            <i class="fa-solid fa-clapperboard text-2xl"></i>
                                            <span class="text-[10px] font-semibold uppercase tracking-wide">No thumbnail</span>
                                        </div>
                                        <span v-if="thumbnailPreview"
                                            class="absolute bottom-1.5 left-1.5 text-[9px] font-black px-1.5 py-0.5 rounded bg-black/60 text-white uppercase">
                                            Preview
                                        </span>
                                    </div>

                                    <div class="flex-1 min-w-0 space-y-2">
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            Shown on product gallery and listing cards. Recommended: 1000×1000 px square JPG/PNG/WebP.
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <label class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg cursor-pointer bg-primary text-white hover:bg-primary/90">
                                                <input type="file" class="hidden" accept="image/png,image/jpeg,image/jpg,image/webp" @change="onThumbnailFileChange" />
                                                <i class="fa-solid fa-upload text-[10px]"></i>
                                                Upload
                                            </label>
                                            <button type="button" @click="showMediaPicker = true"
                                                class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-primary hover:border-primary/40">
                                                <i class="fa-solid fa-images text-[10px]"></i>
                                                Gallery
                                            </button>
                                            <ImageLinkButton compact @selected="handleMediaSelected" @loading="loading.isActive = $event" />
                                            <button v-if="canCaptureFromVideo" type="button" @click="openFramePicker"
                                                :disabled="isCapturingThumbnail"
                                                class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-primary/40 disabled:opacity-50">
                                                <i v-if="isCapturingThumbnail" class="fa-solid fa-circle-notch animate-spin text-[10px]"></i>
                                                <i v-else class="fa-solid fa-film text-[10px]"></i>
                                                From Video
                                            </button>
                                            <button v-if="canUseYoutubeThumbnail" type="button" @click="useYoutubeThumbnail"
                                                class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-red-600 hover:border-red-200">
                                                <i class="fa-brands fa-youtube text-[10px]"></i>
                                                YouTube Frame
                                            </button>
                                            <button v-if="thumbnailPreview" type="button" @click="clearThumbnail"
                                                class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg bg-white border border-rose-200 text-rose-500 hover:bg-rose-50">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                Remove
                                            </button>
                                        </div>
                                        <small class="db-field-alert" v-if="errors.thumbnail">{{ errors.thumbnail[0] }}</small>

                                        <!-- Video frame picker -->
                                        <div v-if="showFramePicker" class="mt-3 rounded-xl border border-slate-200 bg-white p-3 space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-bold text-heading uppercase tracking-wide">Pick a frame</p>
                                                <button type="button" @click="closeFramePicker"
                                                    class="text-slate-400 hover:text-rose-500">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>

                                            <div v-if="isCapturingThumbnail" class="py-8 flex flex-col items-center justify-center text-slate-400 gap-2">
                                                <i class="fa-solid fa-circle-notch animate-spin text-2xl text-primary"></i>
                                                <span class="text-[10px] font-semibold uppercase tracking-wide">Extracting frames...</span>
                                            </div>

                                            <div v-else-if="videoFrames.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                                <button v-for="(frame, index) in videoFrames" :key="index" type="button"
                                                    @click="selectVideoFrame(index)"
                                                    class="relative aspect-square rounded-lg overflow-hidden border-2 transition-all"
                                                    :class="selectedFrameIndex === index ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200 hover:border-primary/50'">
                                                    <img :src="frame.dataUrl" :alt="'Frame ' + (index + 1)" class="w-full h-full object-cover" />
                                                    <span class="absolute bottom-1 right-1 text-[8px] font-black px-1 py-0.5 rounded bg-black/60 text-white">
                                                        {{ formatFrameTime(frame.time) }}
                                                    </span>
                                                </button>
                                            </div>

                                            <p v-else class="text-[11px] text-slate-500">No frames available. Upload a video first.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12">
                            <div class="modal-btns">
                                <button type="button" class="modal-btn-outline modal-close" @click.prevent="reset">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t('button.close') }}</span>
                                </button>

                                <button type="submit" class="db-btn py-2 text-white bg-primary">
                                    <i class="lab lab-fill-save"></i>
                                    <span>{{ $t('button.save') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <MediaPickerComponent :show="showMediaPicker" @close="showMediaPicker = false" @selected="handleMediaSelected" />
</template>
<script>
import SmModalCreateComponent from "../../components/buttons/SmModalCreateComponent";
import LoadingComponent from "../../components/LoadingComponent";
import MediaPickerComponent from "../../media/MediaPickerComponent";
import ImageLinkButton from "../../media/ImageLinkButton";
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";
import videoProviderEnum from "../../../../enums/modules/videoProviderEnum";
import { captureVideoFrames } from "../../../../utils/videoThumbnail";
import { assetToFile } from "../../../../services/imageUploadService";

export default {
    name: "ProductVideoCreateComponent",
    components: { SmModalCreateComponent, LoadingComponent, MediaPickerComponent, ImageLinkButton },
    props: ["productData"],
    data() {
        return {
            loading: {
                isActive: false
            },
            errors: {},
            showMediaPicker: false,
            isCapturingThumbnail: false,
            showFramePicker: false,
            videoFrames: [],
            selectedFrameIndex: null,
            frameVideoObjectUrl: "",
            thumbnailPreview: "",
            thumbnailFile: null,
            removeThumbnail: false,
            videoFilePreview: "",
            enums: {
                videoProviderEnum: videoProviderEnum,
            },
            form: {
                video_provider: 20,
                link: "",
                file: null
            }
        }
    },
    watch: {
        'productData.form': {
            handler(newVal) {
                if (newVal) {
                    this.form.video_provider = Number(newVal.video_provider) || 20;
                    this.form.link = newVal.link || "";
                    this.form.file = null;
                    this.revokeVideoFilePreview();
                    this.thumbnailPreview = newVal.thumbnail || "";
                    this.thumbnailFile = null;
                    this.removeThumbnail = false;
                    this.errors = {};
                }
            },
            deep: true
        }
    },
    computed: {
        addButton: function () {
            return { title: this.$t("button.add_video") }
        },
        isEditing: function () {
            return this.$store.getters['productVideo/temp'].isEditing;
        },
        hasExistingVideo: function () {
            return this.isEditing
                && Number(this.form.video_provider) === 20
                && !!this.form.link
                && !this.form.link.includes('uploading');
        },
        videoPreviewUrl: function () {
            if (this.videoFilePreview) {
                return this.videoFilePreview;
            }
            if (this.hasExistingVideo) {
                return this.form.link;
            }
            return "";
        },
        canCaptureFromVideo: function () {
            if (Number(this.form.video_provider) !== 20) {
                return false;
            }
            return !!(this.form.file || (this.form.link && !this.form.link.includes('uploading')));
        },
        canUseYoutubeThumbnail: function () {
            return Number(this.form.video_provider) === 5 && !!this.getYouTubeId(this.form.link);
        },
    },
    beforeUnmount() {
        this.closeFramePicker();
        this.revokeVideoFilePreview();
    },
    methods: {
        createButtonClick: function () {
            appService.modalShow('#videoModal');
        },
        reset: function () {
            appService.modalHide('#videoModal');
            appService.modalHide('#variationModal');
            this.$store.dispatch('productVideo/reset').then().catch();
            this.errors = {};
            this.showMediaPicker = false;
            this.thumbnailPreview = "";
            this.thumbnailFile = null;
            this.removeThumbnail = false;
            this.revokeVideoFilePreview();
            this.isCapturingThumbnail = false;
            this.closeFramePicker();
            this.form = {
                video_provider: 20,
                link: "",
                file: null
            };
            const fileInput = document.getElementById('file');
            if (fileInput) {
                fileInput.value = "";
            }
        },
        revokeVideoFilePreview() {
            if (this.videoFilePreview) {
                URL.revokeObjectURL(this.videoFilePreview);
                this.videoFilePreview = "";
            }
        },
        onFileChange: function (e) {
            this.form.file = e.target.files[0] || null;
            this.revokeVideoFilePreview();
            if (this.form.file) {
                this.videoFilePreview = URL.createObjectURL(this.form.file);
            }
            this.closeFramePicker();
        },
        onThumbnailFileChange: function (e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            this.setThumbnailFile(file);
            e.target.value = null;
        },
        setThumbnailFile: function (file) {
            this.thumbnailFile = file;
            this.removeThumbnail = false;
            this.thumbnailPreview = URL.createObjectURL(file);
        },
        clearThumbnail: function () {
            this.thumbnailFile = null;
            this.thumbnailPreview = "";
            this.removeThumbnail = true;
        },
        async handleMediaSelected(asset) {
            const items = Array.isArray(asset) ? asset : [asset];
            if (!items.length) {
                return;
            }

            try {
                this.loading.isActive = true;
                const file = await assetToFile(items[0]);
                const extension = items[0].filename?.split('.').pop() || 'jpg';
                const thumbFile = new File([file], `video-thumb.${extension}`, { type: file.type || 'image/jpeg' });
                this.setThumbnailFile(thumbFile);
                this.showMediaPicker = false;
            } catch (err) {
                alertService.error('Failed to load image from gallery');
            } finally {
                this.loading.isActive = false;
            }
        },
        getYouTubeId: function (url) {
            if (!url) {
                return null;
            }
            const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|shorts\/))([\w-]{11})/);
            return match ? match[1] : null;
        },
        async useYoutubeThumbnail() {
            const videoId = this.getYouTubeId(this.form.link);
            if (!videoId) {
                alertService.error('Enter a valid YouTube link first');
                return;
            }
            try {
                this.loading.isActive = true;
                const response = await fetch(`https://img.youtube.com/vi/${videoId}/hqdefault.jpg`);
                const blob = await response.blob();
                this.setThumbnailFile(new File([blob], 'youtube-thumb.jpg', { type: blob.type || 'image/jpeg' }));
            } catch (err) {
                alertService.error('Could not fetch YouTube thumbnail');
            } finally {
                this.loading.isActive = false;
            }
        },
        formatFrameTime(seconds) {
            if (!seconds || !isFinite(seconds)) {
                return '0:00';
            }
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${mins}:${secs}`;
        },
        closeFramePicker() {
            this.showFramePicker = false;
            this.videoFrames = [];
            this.selectedFrameIndex = null;
            if (this.frameVideoObjectUrl) {
                URL.revokeObjectURL(this.frameVideoObjectUrl);
                this.frameVideoObjectUrl = "";
            }
        },
        getVideoSourceUrl() {
            if (this.form.file) {
                if (!this.frameVideoObjectUrl) {
                    this.frameVideoObjectUrl = URL.createObjectURL(this.form.file);
                }
                return this.frameVideoObjectUrl;
            }
            if (this.hasExistingVideo) {
                return this.form.link;
            }
            return this.form.link || "";
        },
        async openFramePicker() {
            const videoUrl = this.getVideoSourceUrl();
            if (!videoUrl) {
                alertService.error('Upload or select a video first');
                return;
            }

            this.showFramePicker = true;
            this.isCapturingThumbnail = true;
            this.videoFrames = [];
            this.selectedFrameIndex = null;

            try {
                this.videoFrames = await captureVideoFrames(videoUrl, 8);
            } catch (err) {
                this.showFramePicker = false;
                alertService.error('Could not extract frames from video');
            } finally {
                this.isCapturingThumbnail = false;
            }
        },
        async selectVideoFrame(index) {
            const frame = this.videoFrames[index];
            if (!frame) {
                return;
            }

            this.selectedFrameIndex = index;
            try {
                const response = await fetch(frame.dataUrl);
                const blob = await response.blob();
                this.setThumbnailFile(new File([blob], `video-frame-${index + 1}.jpg`, { type: 'image/jpeg' }));
            } catch (err) {
                alertService.error('Could not apply selected frame');
            }
        },
        save: function () {
            try {
                this.loading.isActive = true;
                const formData = new FormData();
                formData.append('video_provider', Number(this.form.video_provider) || 20);
                formData.append('link', this.form.link || '');
                if (this.form.file) {
                    formData.append('file', this.form.file);
                }
                if (this.thumbnailFile) {
                    formData.append('thumbnail', this.thumbnailFile);
                }
                if (this.removeThumbnail) {
                    formData.append('remove_thumbnail', '1');
                }

                if (this.$store.getters['productVideo/temp'].isEditing) {
                    formData.append('_method', 'PUT');
                }

                this.$store.dispatch('productVideo/save', {
                    productId: this.productData.productId,
                    form: formData
                }).then((res) => {
                    appService.modalHide('#videoModal');
                    appService.modalHide('#variationModal');
                    this.loading.isActive = false;
                    alertService.successFlip((res.config.method === 'put' ?? 0), this.$t('label.product_video'));
                    this.reset();
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response.data.errors || {};
                    if (err.response.data.message) {
                        alertService.error(err.response.data.message);
                    }
                })
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err?.response?.data?.message || 'Save failed');
            }
        }
    }
};
</script>
