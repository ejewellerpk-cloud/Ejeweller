<template>
    <div class="db-card p-6 pb-20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-2xl font-black text-heading tracking-tight uppercase">Media Asset Manager</h1>
                <p class="text-xs font-medium mt-1 uppercase tracking-widest opacity-60">Global CDN & Resource Hub</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <input type="file" ref="fileInput" class="hidden" multiple accept="image/*" @change="handleQuickUpload" />
                <input type="file" ref="bulkFileInput" class="hidden" multiple accept="image/*" @change="handleBulkFileSelect" />
                <button @click="$refs.fileInput.click()" :disabled="isUploading"
                    class="db-btn py-2 px-6 text-white bg-primary flex items-center gap-2 disabled:opacity-60">
                    <i v-if="loading && !isUploading" class="fa-solid fa-circle-notch animate-spin"></i>
                    <i v-else class="fa-solid fa-plus"></i>
                    <span>Deploy Assets</span>
                </button>
                <button @click="openBulkUpload" :disabled="isUploading"
                    class="db-btn py-2 px-6 bg-white border border-primary text-primary flex items-center gap-2 disabled:opacity-60">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Bulk Upload</span>
                </button>
            </div>
        </div>

        <!-- Bulk Upload Panel -->
        <div v-if="bulkUploadOpen || isUploading" class="mb-8 rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
            <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-slate-200 bg-white">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-heading">Bulk Upload</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Images are converted to WebP automatically (same as single upload)</p>
                </div>
                <button v-if="!isUploading" type="button" @click="closeBulkUpload"
                    class="w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:text-rose-500 hover:border-rose-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-5">
                <div
                    class="relative rounded-2xl border-2 border-dashed transition-colors cursor-pointer"
                    :class="isDragging ? 'border-primary bg-primary/5' : 'border-slate-300 bg-white hover:border-primary/60'"
                    @click="!isUploading && $refs.bulkFileInput.click()"
                    @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave"
                    @drop.prevent="onDrop">
                    <div class="py-10 px-6 text-center pointer-events-none">
                        <i class="fa-solid fa-images text-4xl text-primary/70 mb-3"></i>
                        <p class="text-sm font-bold text-heading">Drop images here or click to browse</p>
                        <p class="text-xs text-slate-500 mt-1">JPG, PNG, GIF, WebP, SVG — multiple files supported</p>
                    </div>
                </div>

                <div v-if="uploadQueue.length > 0" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-600">
                            {{ uploadQueue.length }} file(s) queued
                        </p>
                        <div class="flex items-center gap-2">
                            <button v-if="!isUploading" type="button" @click="clearQueue"
                                class="text-xs font-semibold text-slate-500 hover:text-rose-500 px-3 py-1.5 rounded-lg border border-slate-200 bg-white">
                                Clear queue
                            </button>
                            <button type="button" @click="startBulkUpload" :disabled="isUploading || uploadQueue.length === 0"
                                class="db-btn py-2 px-5 text-white bg-primary text-xs disabled:opacity-60 flex items-center gap-2">
                                <i v-if="isUploading" class="fa-solid fa-circle-notch animate-spin"></i>
                                <i v-else class="fa-solid fa-upload"></i>
                                <span>{{ isUploading ? 'Uploading...' : 'Start Upload' }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="isUploading || overallProgress > 0" class="space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            <span>Overall progress</span>
                            <span>{{ completedCount }}/{{ uploadQueue.length }} · {{ Math.round(overallProgress) }}%</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full bg-primary transition-all duration-300 rounded-full"
                                :style="{ width: overallProgress + '%' }"></div>
                        </div>
                    </div>

                    <div class="max-h-72 overflow-y-auto thin-scrolling rounded-xl border border-slate-200 bg-white divide-y divide-slate-100">
                        <div v-for="(item, index) in uploadQueue" :key="item.id"
                            class="px-4 py-3 flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                <img v-if="item.preview" :src="item.preview" :alt="item.name" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-image text-slate-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <p class="text-xs font-bold text-heading truncate">{{ item.name }}</p>
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded"
                                        :class="statusClass(item.status)">
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-400 mb-2">{{ formatSize(item.size) }}</p>
                                <div v-if="item.status === 'uploading'" class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-primary transition-all duration-200 rounded-full"
                                        :style="{ width: item.progress + '%' }"></div>
                                </div>
                                <p v-if="item.status === 'error' && item.error" class="text-[10px] text-rose-500 mt-1">{{ item.error }}</p>
                            </div>
                            <button v-if="!isUploading && item.status === 'pending'" type="button"
                                @click="removeFromQueue(index)"
                                class="text-slate-400 hover:text-rose-500 flex-shrink-0">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="flex flex-col xl:flex-row gap-6 mb-8">
            <div class="xl:w-64 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-2xl p-4 max-h-[420px] overflow-y-auto thin-scrolling">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Folders</p>
                <div class="space-y-1">
                    <button v-for="folder in flatFolders" :key="folder.id" type="button"
                        @click="selectFolder(folder.id)"
                        class="w-full text-left px-3 py-2 rounded-lg text-xs font-semibold transition-all"
                        :class="selectedFolder === folder.id ? 'bg-primary text-white' : 'text-slate-600 hover:bg-white hover:text-primary'"
                        :style="{ paddingLeft: (12 + folder.depth * 12) + 'px' }">
                        <i class="fa-solid fa-folder mr-2 opacity-70"></i>{{ folder.name }}
                    </button>
                </div>
            </div>

            <div class="flex-1 flex flex-col gap-4">
        <div class="flex flex-col xl:flex-row items-center justify-between gap-6 bg-slate-50 p-4 rounded-xl">
            <div class="relative w-full xl:max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" v-model="search" @input="handleSearch" placeholder="Search assets..."
                    class="w-full pl-12 pr-6 h-12 bg-white border border-slate-200 rounded-xl focus:border-primary outline-none text-sm" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label v-if="mediaList && mediaList.length > 0"
                    class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" :checked="allSelected" :indeterminate.prop="someSelected && !allSelected"
                        @change="toggleSelectAll" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary" />
                    <span>Select all</span>
                </label>
                <button v-if="selectedIds.length > 0" type="button" @click="bulkDeleteAssets"
                    class="db-btn py-2 px-4 bg-white border border-rose-200 text-rose-500 hover:bg-rose-500 hover:text-white text-xs flex items-center gap-2">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Delete selected ({{ selectedIds.length }})</span>
                </button>
                <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white shadow text-primary' : 'text-slate-400'"
                    class="p-3 rounded-xl transition-all">
                    <i class="fa-solid fa-grid-2"></i>
                </button>
                <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white shadow text-primary' : 'text-slate-400'"
                    class="p-3 rounded-xl transition-all">
                    <i class="fa-solid fa-list"></i>
                </button>
            </div>
        </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div v-for="asset in mediaList" :key="asset.id" 
                class="group relative bg-white border rounded-2xl overflow-hidden hover:shadow-xl transition-all flex flex-col"
                :class="isSelected(asset.id) ? 'border-primary ring-2 ring-primary/20' : 'border-slate-100'">
                <label class="absolute top-2 left-2 z-10 cursor-pointer">
                    <input type="checkbox" :checked="isSelected(asset.id)" @change="toggleSelect(asset.id)"
                        class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary bg-white shadow" />
                </label>
                <div class="aspect-square bg-slate-50 relative overflow-hidden flex items-center justify-center border-b border-slate-100">
                    <img :src="asset.url" :alt="asset.originalName" class="w-full h-full object-contain p-2 group-hover:scale-110 transition-all" />
                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button @click="copyUrl(asset.url)" class="p-2 bg-white text-slate-900 rounded-lg hover:bg-primary hover:text-white" title="Copy URL">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button @click="deleteAsset(asset.id)" class="p-2 bg-white text-rose-500 rounded-lg hover:bg-rose-500 hover:text-white" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                <div class="p-3">
                    <p class="text-[10px] font-bold text-heading truncate mb-1 uppercase tracking-tight">{{ asset.originalName }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] text-slate-400">{{ formatSize(asset.size) }}</span>
                        <span class="text-[9px] font-black text-primary px-1 bg-primary/5 rounded uppercase">{{ asset.mimetype.split('/')[1] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="db-table-responsive border border-slate-100 rounded-2xl">
            <table class="db-table">
                <thead class="db-table-head">
                    <tr class="db-table-head-tr">
                        <th class="db-table-head-th w-10">
                            <input type="checkbox" :checked="allSelected" :indeterminate.prop="someSelected && !allSelected"
                                @change="toggleSelectAll" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary" />
                        </th>
                        <th class="db-table-head-th">Asset</th>
                        <th class="db-table-head-th">Name</th>
                        <th class="db-table-head-th">Size</th>
                        <th class="db-table-head-th">Type</th>
                        <th class="db-table-head-th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="db-table-body">
                    <tr v-for="asset in mediaList" :key="asset.id" class="db-table-body-tr"
                        :class="isSelected(asset.id) ? 'bg-primary/5' : ''">
                        <td class="db-table-body-td">
                            <input type="checkbox" :checked="isSelected(asset.id)" @change="toggleSelect(asset.id)"
                                class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary" />
                        </td>
                        <td class="db-table-body-td">
                            <img :src="asset.url" class="w-10 h-10 object-cover rounded" />
                        </td>
                        <td class="db-table-body-td font-bold text-xs uppercase">{{ asset.originalName }}</td>
                        <td class="db-table-body-td text-xs">{{ formatSize(asset.size) }}</td>
                        <td class="db-table-body-td">
                            <span class="px-2 py-0.5 bg-slate-100 text-[10px] font-bold rounded uppercase">{{ asset.mimetype.split('/')[1] }}</span>
                        </td>
                        <td class="db-table-body-td text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="copyUrl(asset.url)" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-copy"></i></button>
                                <button @click="deleteAsset(asset.id)" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-center mt-10 gap-2" v-if="pagination.totalPages > 1">
            <button @click="changePage(pagination.currentPage - 1)" :disabled="pagination.currentPage === 1"
                class="w-10 h-10 flex items-center justify-center border rounded-lg disabled:opacity-30">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button v-for="p in pagination.totalPages" :key="p" @click="changePage(p)"
                :class="pagination.currentPage === p ? 'bg-primary text-white border-primary' : 'border-slate-200'"
                class="w-10 h-10 border rounded-lg font-bold text-xs">
                {{ p }}
            </button>
            <button @click="changePage(pagination.currentPage + 1)" :disabled="pagination.currentPage === pagination.totalPages"
                class="w-10 h-10 flex items-center justify-center border rounded-lg disabled:opacity-30">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="mediaList && mediaList.length === 0" class="py-40 flex flex-col items-center justify-center text-center">
            <i class="fa-solid fa-image text-5xl text-slate-200 mb-4"></i>
            <h3 class="text-lg font-bold uppercase text-slate-400">No assets found</h3>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import alertService from '../../../services/alertService';
import appService from '../../../services/appService';

export default {
    name: "MediaGalleryComponent",
    data() {
        return {
            loading: false,
            search: "",
            viewMode: "grid",
            filterTimer: null,
            bulkUploadOpen: false,
            isUploading: false,
            isDragging: false,
            uploadQueue: [],
            overallProgress: 0,
            completedCount: 0,
            queueIdSeed: 0,
            selectedIds: [],
            selectedFolder: 'all',
        }
    },
    computed: {
        ...mapGetters({
            mediaList: 'media/lists',
            folders: 'media/folders',
            currentFolder: 'media/currentFolder',
            pagination: 'media/page'
        }),
        flatFolders() {
            const items = [];

            (this.folders || []).forEach((folder) => {
                items.push({ id: folder.id, name: folder.name, depth: 0 });
                (folder.children || []).forEach((child) => {
                    items.push({ id: child.id, name: child.name, depth: 1 });
                });
            });

            return items;
        },
        allSelected() {
            return this.mediaList?.length > 0 && this.mediaList.every((asset) => this.selectedIds.includes(asset.id));
        },
        someSelected() {
            return this.selectedIds.length > 0;
        },
    },
    mounted() {
        this.selectedFolder = this.currentFolder || 'all';
        this.fetchMedia();
    },
    beforeUnmount() {
        this.revokeQueuePreviews();
    },
    methods: {
        ...mapActions({
            lists: 'media/lists',
            save: 'media/save',
            uploadFile: 'media/uploadFile',
            destroy: 'media/destroy',
            bulkDestroy: 'media/bulkDestroy',
        }),
        fetchMedia(page = 1) {
            this.clearSelection();
            this.lists({
                page: page,
                search: this.search,
                folder: this.selectedFolder,
            });
        },
        selectFolder(folderId) {
            this.selectedFolder = folderId;
            this.fetchMedia(1);
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
        openBulkUpload() {
            this.bulkUploadOpen = true;
        },
        closeBulkUpload() {
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
            const files = Array.from(event.dataTransfer?.files || []);
            this.addFilesToQueue(files);
        },
        handleBulkFileSelect(event) {
            const files = Array.from(event.target.files || []);
            this.addFilesToQueue(files);
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
        async handleQuickUpload(event) {
            const files = Array.from(event.target.files || []);
            if (files.length === 0) {
                return;
            }

            this.bulkUploadOpen = true;
            this.addFilesToQueue(files);
            event.target.value = null;
            await this.startBulkUpload();
        },
        async startBulkUpload() {
            const pendingItems = this.uploadQueue.filter((item) => item.status === 'pending' || item.status === 'error');
            if (pendingItems.length === 0 || this.isUploading) {
                return;
            }

            this.isUploading = true;
            this.loading = true;
            this.completedCount = this.uploadQueue.filter((item) => item.status === 'done').length;
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
                formData.append('folder', this.selectedFolder);

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
                    item.error = err?.response?.data?.error
                        || err?.response?.data?.message
                        || 'Upload failed';
                    failCount++;
                }

                this.completedCount = this.uploadQueue.filter((queueItem) => queueItem.status === 'done' || queueItem.status === 'error').length;
                this.updateOverallProgress(total);
            }

            this.isUploading = false;
            this.loading = false;
            this.overallProgress = 100;

            await this.fetchMedia(1);

            if (successCount > 0 && failCount === 0) {
                alertService.success(`${successCount} image(s) uploaded successfully`);
            } else if (successCount > 0) {
                alertService.warning(`${successCount} uploaded, ${failCount} failed`);
            } else {
                alertService.error('Bulk upload failed');
            }
        },
        updateOverallProgress(total) {
            if (total === 0) {
                this.overallProgress = 0;
                return;
            }

            const accumulated = this.uploadQueue.reduce((sum, item) => {
                if (item.status === 'done') {
                    return sum + 100;
                }
                if (item.status === 'error') {
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
            const labels = {
                pending: 'Queued',
                uploading: 'Uploading',
                done: 'Done',
                error: 'Failed',
            };
            return labels[status] || status;
        },
        statusClass(status) {
            const classes = {
                pending: 'bg-slate-100 text-slate-500',
                uploading: 'bg-primary/10 text-primary',
                done: 'bg-emerald-100 text-emerald-600',
                error: 'bg-rose-100 text-rose-600',
            };
            return classes[status] || 'bg-slate-100 text-slate-500';
        },
        async deleteAsset(id) {
            appService.destroyConfirmation().then(async (res) => {
                try {
                    await this.destroy({ id: id, search: { page: this.pagination.currentPage, search: this.search } });
                    this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
                    alertService.success("Asset purged successfully");
                } catch (err) {
                    alertService.error("Purge failed");
                }
            }).catch(() => {});
        },
        isSelected(id) {
            return this.selectedIds.includes(id);
        },
        toggleSelect(id) {
            if (this.isSelected(id)) {
                this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
            } else {
                this.selectedIds = [...this.selectedIds, id];
            }
        },
        toggleSelectAll() {
            if (this.allSelected) {
                this.clearSelection();
            } else {
                this.selectedIds = this.mediaList.map((asset) => asset.id);
            }
        },
        clearSelection() {
            this.selectedIds = [];
        },
        async bulkDeleteAssets() {
            if (this.selectedIds.length === 0) {
                return;
            }

            appService.destroyConfirmation().then(async () => {
                try {
                    const res = await this.bulkDestroy({
                        ids: [...this.selectedIds],
                        search: { page: this.pagination.currentPage, search: this.search },
                    });
                    const deleted = res?.data?.deleted ?? this.selectedIds.length;
                    const failed = res?.data?.failed ?? 0;
                    this.clearSelection();

                    if (failed > 0) {
                        alertService.warning(`${deleted} deleted, ${failed} not found`);
                    } else {
                        alertService.success(`${deleted} asset(s) deleted successfully`);
                    }
                } catch (err) {
                    alertService.error("Bulk delete failed");
                }
            }).catch(() => {});
        },
        copyUrl(url) {
            navigator.clipboard.writeText(url);
            alertService.success("URL copied to clipboard");
        },
        formatSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
}
</script>
