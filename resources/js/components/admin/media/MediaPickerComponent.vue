<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-5xl h-[85vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="p-6 border-b flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-xl font-black text-heading uppercase tracking-tight">Select Media Asset</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pick an image or upload new assets</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="file" ref="bulkFileInput" class="hidden" multiple accept="image/*" @change="handleBulkFileSelect" />
                    <button type="button" @click="toggleBulkUpload" :disabled="isUploading"
                        class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl border border-primary text-primary text-xs font-bold uppercase tracking-wide disabled:opacity-60">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Bulk Upload</span>
                    </button>
                    <button @click="closePicker" :disabled="isUploading"
                        class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-200 transition-colors disabled:opacity-60">
                        <i class="fa-solid fa-xmark text-slate-500"></i>
                    </button>
                </div>
            </div>

            <!-- Bulk Upload Panel -->
            <div v-if="bulkUploadOpen || isUploading" class="border-b bg-slate-50 px-4 py-4 space-y-3 max-h-[40vh] overflow-y-auto thin-scrolling">
                <div
                    class="rounded-2xl border-2 border-dashed transition-colors cursor-pointer bg-white"
                    :class="isDragging ? 'border-primary bg-primary/5' : 'border-slate-300 hover:border-primary/60'"
                    @click="!isUploading && $refs.bulkFileInput.click()"
                    @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave"
                    @drop.prevent="onDrop">
                    <div class="py-6 px-4 text-center pointer-events-none">
                        <i class="fa-solid fa-images text-2xl text-primary/70 mb-2"></i>
                        <p class="text-xs font-bold text-heading">Drop images here or click to browse</p>
                        <p class="text-[10px] text-slate-500 mt-1">Converted to WebP automatically</p>
                    </div>
                </div>

                <div v-if="uploadQueue.length > 0" class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                            {{ uploadQueue.length }} file(s)
                        </p>
                        <div class="flex items-center gap-2">
                            <button v-if="!isUploading" type="button" @click="clearQueue"
                                class="text-[10px] font-semibold text-slate-500 hover:text-rose-500 px-2 py-1 rounded border border-slate-200 bg-white">
                                Clear
                            </button>
                            <button type="button" @click="startBulkUpload" :disabled="isUploading || uploadQueue.length === 0"
                                class="db-btn py-1.5 px-4 text-white bg-primary text-[10px] disabled:opacity-60 flex items-center gap-1.5">
                                <i v-if="isUploading" class="fa-solid fa-circle-notch animate-spin"></i>
                                <i v-else class="fa-solid fa-upload"></i>
                                <span>{{ isUploading ? 'Uploading...' : 'Start Upload' }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="isUploading || overallProgress > 0" class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase text-slate-500">
                            <span>Overall</span>
                            <span>{{ completedCount }}/{{ uploadQueue.length }} · {{ Math.round(overallProgress) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full bg-primary transition-all duration-300 rounded-full"
                                :style="{ width: overallProgress + '%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100 max-h-32 overflow-y-auto thin-scrolling">
                        <div v-for="(item, index) in uploadQueue" :key="item.id" class="px-3 py-2 flex items-center gap-2">
                            <div class="w-8 h-8 rounded bg-slate-100 flex-shrink-0 overflow-hidden">
                                <img v-if="item.preview" :src="item.preview" :alt="item.name" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-bold truncate">{{ item.name }}</p>
                                    <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded" :class="statusClass(item.status)">
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </div>
                                <div v-if="item.status === 'uploading'" class="h-1 rounded-full bg-slate-100 overflow-hidden mt-1">
                                    <div class="h-full bg-primary transition-all duration-200" :style="{ width: item.progress + '%' }"></div>
                                </div>
                            </div>
                            <button v-if="!isUploading && item.status === 'pending'" type="button"
                                @click="removeFromQueue(index)" class="text-slate-400 hover:text-rose-500">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="p-4 border-b bg-white">
                <div class="relative max-w-md">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" v-model="search" @input="handleSearch" placeholder="Search images..."
                        class="w-full pl-11 pr-4 h-11 bg-slate-100 border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 outline-none text-sm transition-all" />
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                <div v-if="loading && !isUploading" class="h-full flex items-center justify-center">
                    <i class="fa-solid fa-circle-notch animate-spin text-3xl text-primary"></i>
                </div>
                <div v-else-if="mediaList && mediaList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="asset in mediaList" :key="asset.id" @click="selectImage(asset)"
                        class="group relative aspect-square bg-slate-50 rounded-2xl overflow-hidden border-2 border-transparent hover:border-primary cursor-pointer transition-all">
                        <img :src="asset.url" class="w-full h-full object-contain p-2 group-hover:scale-110 transition-transform duration-500" />
                        <div class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="absolute bottom-2 right-2 w-6 h-6 bg-primary text-white rounded-full flex items-center justify-center scale-0 group-hover:scale-100 transition-transform">
                            <i class="fa-solid fa-check text-[10px]"></i>
                        </div>
                    </div>
                </div>
                <div v-else class="h-full flex flex-col items-center justify-center text-slate-400">
                    <i class="fa-solid fa-image text-5xl mb-4 opacity-20"></i>
                    <p class="font-bold uppercase text-xs tracking-widest">No images found</p>
                    <button type="button" @click="openBulkUpload" class="mt-4 text-xs font-bold text-primary uppercase tracking-wide">
                        Upload images
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2" v-if="pagination.totalPages > 1">
                    <button @click="changePage(pagination.currentPage - 1)" :disabled="pagination.currentPage === 1"
                        class="w-8 h-8 flex items-center justify-center border rounded-lg disabled:opacity-30 bg-white">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <span class="text-xs font-bold px-2">{{ pagination.currentPage }} / {{ pagination.totalPages }}</span>
                    <button @click="changePage(pagination.currentPage + 1)" :disabled="pagination.currentPage === pagination.totalPages"
                        class="w-8 h-8 flex items-center justify-center border rounded-lg disabled:opacity-30 bg-white">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
                <div class="flex items-center gap-3 ml-auto">
                    <button type="button" @click="openBulkUpload" :disabled="isUploading"
                        class="sm:hidden px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-primary border border-primary rounded-lg disabled:opacity-60">
                        Bulk Upload
                    </button>
                    <button @click="closePicker" :disabled="isUploading"
                        class="px-6 py-2 text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700 disabled:opacity-60">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import alertService from '../../../services/alertService';

export default {
    name: "MediaPickerComponent",
    props: {
        show: { type: Boolean, default: false }
    },
    data() {
        return {
            loading: false,
            search: "",
            filterTimer: null,
            bulkUploadOpen: false,
            isUploading: false,
            isDragging: false,
            uploadQueue: [],
            overallProgress: 0,
            completedCount: 0,
            queueIdSeed: 0,
        }
    },
    computed: {
        ...mapGetters({
            mediaList: 'media/lists',
            pagination: 'media/page'
        })
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.fetchMedia();
            } else {
                this.resetBulkUpload();
            }
        }
    },
    beforeUnmount() {
        this.revokeQueuePreviews();
    },
    methods: {
        ...mapActions({
            lists: 'media/lists',
            uploadFile: 'media/uploadFile',
        }),
        async fetchMedia(page = 1) {
            this.loading = true;
            try {
                await this.lists({ page: page, search: this.search });
            } finally {
                this.loading = false;
            }
        },
        handleSearch() {
            clearTimeout(this.filterTimer);
            this.filterTimer = setTimeout(() => {
                this.fetchMedia(1);
            }, 500);
        },
        changePage(page) {
            this.fetchMedia(page);
        },
        selectImage(asset) {
            this.$emit('selected', asset);
            this.$emit('close');
        },
        closePicker() {
            if (this.isUploading) {
                return;
            }
            this.$emit('close');
        },
        toggleBulkUpload() {
            this.bulkUploadOpen = !this.bulkUploadOpen;
        },
        openBulkUpload() {
            this.bulkUploadOpen = true;
        },
        resetBulkUpload() {
            if (this.isUploading) {
                return;
            }
            this.bulkUploadOpen = false;
            this.clearQueue();
        },
        onDragOver() {
            if (!this.isUploading) {
                this.isDragging = true;
            }
        },
        onDragLeave() {
            this.isDragging = false;
        },
        onDrop(event) {
            this.isDragging = false;
            if (this.isUploading) {
                return;
            }
            this.addFilesToQueue(Array.from(event.dataTransfer?.files || []));
        },
        handleBulkFileSelect(event) {
            this.addFilesToQueue(Array.from(event.target.files || []));
            event.target.value = null;
        },
        addFilesToQueue(files) {
            const imageFiles = files.filter((file) => file.type.startsWith('image/'));
            if (imageFiles.length === 0) {
                alertService.error('Please select image files only');
                return;
            }
            if (imageFiles.length !== files.length) {
                alertService.warning('Non-image files were skipped');
            }
            imageFiles.forEach((file) => {
                this.uploadQueue.push({
                    id: ++this.queueIdSeed,
                    file,
                    name: file.name,
                    size: file.size,
                    preview: URL.createObjectURL(file),
                    progress: 0,
                    status: 'pending',
                    error: '',
                });
            });
            this.bulkUploadOpen = true;
        },
        removeFromQueue(index) {
            const item = this.uploadQueue[index];
            if (item?.preview) {
                URL.revokeObjectURL(item.preview);
            }
            this.uploadQueue.splice(index, 1);
        },
        clearQueue() {
            this.revokeQueuePreviews();
            this.uploadQueue = [];
            this.overallProgress = 0;
            this.completedCount = 0;
        },
        revokeQueuePreviews() {
            this.uploadQueue.forEach((item) => {
                if (item.preview) {
                    URL.revokeObjectURL(item.preview);
                }
            });
        },
        async startBulkUpload() {
            const pendingItems = this.uploadQueue.filter((item) => item.status === 'pending' || item.status === 'error');
            if (pendingItems.length === 0 || this.isUploading) {
                return;
            }

            this.isUploading = true;
            this.bulkUploadOpen = true;
            const total = this.uploadQueue.length;
            let successCount = 0;
            let failCount = 0;

            for (const item of this.uploadQueue) {
                if (item.status === 'done') {
                    continue;
                }

                item.status = 'uploading';
                item.progress = 0;
                item.error = '';

                const formData = new FormData();
                formData.append('files[]', item.file);

                try {
                    await this.uploadFile({
                        formData,
                        onUploadProgress: (progressEvent) => {
                            if (!progressEvent.total) {
                                return;
                            }
                            item.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.updateOverallProgress(total);
                        },
                    });
                    item.status = 'done';
                    item.progress = 100;
                    successCount++;
                } catch (err) {
                    item.status = 'error';
                    item.error = err?.response?.data?.error || err?.response?.data?.message || 'Upload failed';
                    failCount++;
                }

                this.completedCount = this.uploadQueue.filter((queueItem) => queueItem.status === 'done' || queueItem.status === 'error').length;
                this.updateOverallProgress(total);
            }

            this.isUploading = false;
            this.overallProgress = 100;
            await this.fetchMedia(1);

            if (successCount > 0 && failCount === 0) {
                alertService.success(`${successCount} image(s) uploaded`);
            } else if (successCount > 0) {
                alertService.warning(`${successCount} uploaded, ${failCount} failed`);
            } else if (failCount > 0) {
                alertService.error('Upload failed');
            }
        },
        updateOverallProgress(total) {
            if (total === 0) {
                this.overallProgress = 0;
                return;
            }
            const accumulated = this.uploadQueue.reduce((sum, item) => {
                if (item.status === 'done' || item.status === 'error') {
                    return sum + 100;
                }
                if (item.status === 'uploading') {
                    return sum + item.progress;
                }
                return sum;
            }, 0);
            this.overallProgress = Math.min(100, accumulated / total);
        },
        statusLabel(status) {
            return { pending: 'Queued', uploading: 'Uploading', done: 'Done', error: 'Failed' }[status] || status;
        },
        statusClass(status) {
            return {
                pending: 'bg-slate-100 text-slate-500',
                uploading: 'bg-primary/10 text-primary',
                done: 'bg-emerald-100 text-emerald-600',
                error: 'bg-rose-100 text-rose-600',
            }[status] || 'bg-slate-100 text-slate-500';
        },
    }
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
