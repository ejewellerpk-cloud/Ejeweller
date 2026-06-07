<template>
    <div class="media-explorer db-card">
        <!-- Toolbar -->
        <header class="media-explorer__header">
            <div class="media-explorer__header-left">
                <h1 class="media-explorer__title">Media Library</h1>
                <p class="media-explorer__subtitle">{{ pagination.total || 0 }} items</p>
            </div>
            <div class="media-explorer__header-actions">
                <input type="file" ref="fileInput" class="hidden" multiple accept="image/*" @change="handleQuickUpload" />
                <input type="file" ref="bulkFileInput" class="hidden" multiple accept="image/*" @change="handleBulkFileSelect" />
                <button type="button" @click="$refs.fileInput.click()" :disabled="isUploading" class="media-explorer__btn media-explorer__btn--primary">
                    <i class="fa-solid fa-upload"></i>
                    <span>Upload</span>
                </button>
                <button type="button" @click="openBulkUpload" :disabled="isUploading" class="media-explorer__btn media-explorer__btn--secondary">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Bulk upload</span>
                </button>
            </div>
        </header>

        <!-- Bulk upload panel -->
        <div v-if="bulkUploadOpen || isUploading" class="media-explorer__bulk">
            <div class="media-explorer__bulk-head">
                <div>
                    <h2 class="text-sm font-semibold text-heading">Bulk upload</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Images are converted to WebP automatically</p>
                </div>
                <button v-if="!isUploading" type="button" @click="closeBulkUpload" class="media-explorer__icon-btn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-4 space-y-4">
                <div
                    class="media-explorer__dropzone"
                    :class="{ 'media-explorer__dropzone--active': isDragging }"
                    @click="!isUploading && $refs.bulkFileInput.click()"
                    @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave"
                    @drop.prevent="onDrop">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 mb-2"></i>
                    <p class="text-sm font-medium text-heading">Drop images here or click to browse</p>
                    <p class="text-xs text-slate-500 mt-1">JPG, PNG, GIF, WebP, SVG</p>
                </div>

                <div v-if="uploadQueue.length > 0" class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-slate-600">{{ uploadQueue.length }} file(s) in queue</span>
                        <div class="flex gap-2">
                            <button v-if="!isUploading" type="button" @click="clearQueue" class="media-explorer__btn media-explorer__btn--ghost text-xs">Clear</button>
                            <button type="button" @click="startBulkUpload" :disabled="isUploading || uploadQueue.length === 0"
                                class="media-explorer__btn media-explorer__btn--primary text-xs">
                                <i v-if="isUploading" class="fa-solid fa-circle-notch animate-spin"></i>
                                <span>{{ isUploading ? 'Uploading…' : 'Start upload' }}</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="isUploading || overallProgress > 0" class="h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-300" :style="{ width: overallProgress + '%' }"></div>
                    </div>
                    <div class="max-h-56 overflow-y-auto thin-scrolling rounded-lg border border-slate-200 divide-y divide-slate-100 bg-white">
                        <div v-for="(item, index) in uploadQueue" :key="item.id" class="px-3 py-2 flex items-center gap-3">
                            <div class="w-9 h-9 rounded bg-slate-100 overflow-hidden flex-shrink-0">
                                <img v-if="item.preview" :src="item.preview" :alt="item.name" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium truncate">{{ item.name }}</p>
                                <p class="text-[10px] text-slate-400">{{ statusLabel(item.status) }}</p>
                            </div>
                            <button v-if="!isUploading && item.status === 'pending'" type="button" @click="removeFromQueue(index)" class="text-slate-400 hover:text-rose-500">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body: sidebar + main -->
        <div class="media-explorer__body">
            <aside class="media-explorer__sidebar">
                <p class="media-explorer__sidebar-label">Folders</p>
                <nav class="media-explorer__folder-list">
                    <button v-for="folder in flatFolders" :key="folder.id" type="button"
                        @click="selectFolder(folder.id)"
                        class="media-explorer__folder-item"
                        :class="{ 'media-explorer__folder-item--active': selectedFolder === folder.id }"
                        :style="{ paddingLeft: (10 + folder.depth * 14) + 'px' }">
                        <i class="fa-solid fa-folder text-[11px] opacity-70"></i>
                        <span class="truncate">{{ folder.name }}</span>
                    </button>
                </nav>
            </aside>

            <main class="media-explorer__main">
                <!-- Command bar -->
                <div class="media-explorer__commandbar">
                    <div class="media-explorer__search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" v-model="search" @input="handleSearch" placeholder="Search files…" />
                    </div>

                    <div class="media-explorer__commandbar-right">
                        <label v-if="mediaList && mediaList.length > 0" class="media-explorer__select-all">
                            <input type="checkbox" :checked="allSelected" :indeterminate.prop="someSelected && !allSelected" @change="toggleSelectAll" />
                            <span>Select all</span>
                        </label>
                        <button v-if="selectedIds.length > 0" type="button" @click="bulkDeleteAssets" class="media-explorer__btn media-explorer__btn--danger text-xs">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete ({{ selectedIds.length }})</span>
                        </button>

                        <div class="media-explorer__view-toggle" role="group" aria-label="View mode">
                            <button v-for="mode in viewModes" :key="mode.id" type="button"
                                @click="viewMode = mode.id"
                                class="media-explorer__view-btn"
                                :class="{ 'media-explorer__view-btn--active': viewMode === mode.id }"
                                :title="mode.label">
                                <i :class="mode.icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Icon views -->
                <div v-if="viewMode !== 'list'" class="media-explorer__grid" :class="'media-explorer__grid--' + viewMode">
                    <div v-for="asset in mediaList" :key="asset.id"
                        class="media-explorer__item"
                        :class="{ 'media-explorer__item--selected': isSelected(asset.id) }"
                        @dblclick="downloadAsset(asset)">
                        <label class="media-explorer__item-check" @click.stop>
                            <input type="checkbox" :checked="isSelected(asset.id)" @change="toggleSelect(asset.id)" />
                        </label>
                        <div class="media-explorer__item-preview">
                            <img :src="asset.url" :alt="asset.originalName" loading="lazy" />
                        </div>
                        <div class="media-explorer__item-actions">
                            <button type="button" @click.stop="downloadAsset(asset)" title="Download">
                                <i class="fa-solid fa-download"></i>
                            </button>
                            <button type="button" @click.stop="copyUrl(asset.url)" title="Copy URL">
                                <i class="fa-solid fa-link"></i>
                            </button>
                            <button type="button" @click.stop="deleteAsset(asset.id)" title="Delete" class="text-rose-500">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <div class="media-explorer__item-meta">
                            <p class="media-explorer__item-name" :title="asset.originalName">{{ asset.originalName }}</p>
                            <p class="media-explorer__item-size">{{ formatSize(asset.size) }}</p>
                        </div>
                    </div>
                </div>

                <!-- List / details view -->
                <div v-else class="media-explorer__list-wrap">
                    <table class="media-explorer__table">
                        <thead>
                            <tr>
                                <th class="w-10"></th>
                                <th class="w-14"></th>
                                <th>Name</th>
                                <th class="w-24">Size</th>
                                <th class="w-20">Type</th>
                                <th class="w-32 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="asset in mediaList" :key="asset.id"
                                :class="{ 'media-explorer__table-row--selected': isSelected(asset.id) }"
                                @dblclick="downloadAsset(asset)">
                                <td>
                                    <input type="checkbox" :checked="isSelected(asset.id)" @change="toggleSelect(asset.id)" />
                                </td>
                                <td>
                                    <img :src="asset.url" :alt="asset.originalName" class="media-explorer__list-thumb" loading="lazy" />
                                </td>
                                <td class="font-medium text-sm truncate max-w-[240px]" :title="asset.originalName">{{ asset.originalName }}</td>
                                <td class="text-xs text-slate-500">{{ formatSize(asset.size) }}</td>
                                <td class="text-xs text-slate-500 uppercase">{{ fileExtension(asset) }}</td>
                                <td>
                                    <div class="media-explorer__list-actions">
                                        <button type="button" @click="downloadAsset(asset)" title="Download"><i class="fa-solid fa-download"></i></button>
                                        <button type="button" @click="copyUrl(asset.url)" title="Copy URL"><i class="fa-solid fa-link"></i></button>
                                        <button type="button" @click="deleteAsset(asset.id)" title="Delete" class="hover:text-rose-500"><i class="fa-solid fa-trash-can"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div v-if="mediaList && mediaList.length === 0" class="media-explorer__empty">
                    <i class="fa-regular fa-image text-4xl text-slate-300 mb-3"></i>
                    <p class="text-sm font-medium text-slate-500">No files in this folder</p>
                    <button type="button" @click="$refs.fileInput.click()" class="media-explorer__btn media-explorer__btn--primary text-xs mt-4">
                        Upload images
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.totalPages > 1" class="media-explorer__pagination">
                    <button type="button" @click="changePage(pagination.currentPage - 1)" :disabled="pagination.currentPage === 1">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="text-xs text-slate-500">Page {{ pagination.currentPage }} of {{ pagination.totalPages }}</span>
                    <button type="button" @click="changePage(pagination.currentPage + 1)" :disabled="pagination.currentPage === pagination.totalPages">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </main>
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
            viewMode: "medium",
            viewModes: [
                { id: 'extra-large', icon: 'fa-solid fa-grip', label: 'Extra large icons' },
                { id: 'large', icon: 'fa-solid fa-table-cells-large', label: 'Large icons' },
                { id: 'medium', icon: 'fa-solid fa-table-cells', label: 'Medium icons' },
                { id: 'small', icon: 'fa-solid fa-border-all', label: 'Small icons' },
                { id: 'list', icon: 'fa-solid fa-list', label: 'Details' },
            ],
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
        fileExtension(asset) {
            const fromMime = asset.mimetype?.split('/')?.[1];
            if (fromMime) {
                return fromMime;
            }
            const parts = (asset.originalName || '').split('.');
            return parts.length > 1 ? parts.pop() : '—';
        },
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
                    item.error = err?.response?.data?.error || err?.response?.data?.message || 'Upload failed';
                    failCount++;
                }
                this.updateOverallProgress(total);
            }

            this.isUploading = false;
            this.loading = false;
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
        async downloadAsset(asset) {
            const filename = asset.originalName || 'download';
            try {
                const response = await fetch(asset.url);
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch {
                const link = document.createElement('a');
                link.href = asset.url;
                link.download = filename;
                link.target = '_blank';
                link.rel = 'noopener';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },
        async deleteAsset(id) {
            appService.destroyConfirmation().then(async () => {
                try {
                    await this.destroy({ id, search: { page: this.pagination.currentPage, search: this.search, folder: this.selectedFolder } });
                    this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
                    alertService.success("File deleted");
                } catch {
                    alertService.error("Delete failed");
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
                        search: { page: this.pagination.currentPage, search: this.search, folder: this.selectedFolder },
                    });
                    const deleted = res?.data?.deleted ?? this.selectedIds.length;
                    const failed = res?.data?.failed ?? 0;
                    this.clearSelection();
                    if (failed > 0) {
                        alertService.warning(`${deleted} deleted, ${failed} not found`);
                    } else {
                        alertService.success(`${deleted} file(s) deleted`);
                    }
                } catch {
                    alertService.error("Bulk delete failed");
                }
            }).catch(() => {});
        },
        copyUrl(url) {
            navigator.clipboard.writeText(url);
            alertService.success("URL copied");
        },
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    }
}
</script>

<style scoped>
.media-explorer {
    padding: 0;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.media-explorer__header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #fafbfc;
}

.media-explorer__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.media-explorer__subtitle {
    font-size: 0.75rem;
    color: #64748b;
    margin: 2px 0 0;
}

.media-explorer__header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.media-explorer__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.media-explorer__btn--primary {
    background: rgb(var(--primary));
    color: #fff;
}

.media-explorer__btn--primary:hover:not(:disabled) {
    opacity: 0.92;
}

.media-explorer__btn--secondary {
    background: #fff;
    border-color: #e2e8f0;
    color: #334155;
}

.media-explorer__btn--secondary:hover:not(:disabled) {
    border-color: rgb(var(--primary));
    color: rgb(var(--primary));
}

.media-explorer__btn--ghost {
    background: transparent;
    border-color: #e2e8f0;
    color: #64748b;
}

.media-explorer__btn--danger {
    background: #fff;
    border-color: #fecaca;
    color: #e11d48;
}

.media-explorer__btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.media-explorer__bulk {
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.media-explorer__bulk-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}

.media-explorer__dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 28px 16px;
    text-align: center;
    cursor: pointer;
    background: #fff;
    transition: border-color 0.15s, background 0.15s;
}

.media-explorer__dropzone:hover,
.media-explorer__dropzone--active {
    border-color: rgb(var(--primary));
    background: rgb(var(--primary) / 0.04);
}

.media-explorer__body {
    display: flex;
    min-height: 520px;
}

.media-explorer__sidebar {
    width: 200px;
    flex-shrink: 0;
    border-right: 1px solid #e2e8f0;
    background: #fafbfc;
    padding: 12px 0;
}

.media-explorer__sidebar-label {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    padding: 0 14px 8px;
    margin: 0;
}

.media-explorer__folder-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
    max-height: 480px;
    overflow-y: auto;
}

.media-explorer__folder-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    text-align: left;
    padding: 7px 14px;
    font-size: 0.8125rem;
    color: #475569;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: background 0.12s, color 0.12s;
}

.media-explorer__folder-item:hover {
    background: #f1f5f9;
}

.media-explorer__folder-item--active {
    background: rgb(var(--primary) / 0.1);
    color: rgb(var(--primary));
    font-weight: 600;
}

.media-explorer__main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.media-explorer__commandbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    background: #fafbfc;
}

.media-explorer__search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
    max-width: 320px;
}

.media-explorer__search-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.75rem;
}

.media-explorer__search-wrap input {
    width: 100%;
    height: 34px;
    padding: 0 12px 0 32px;
    font-size: 0.8125rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    outline: none;
}

.media-explorer__search-wrap input:focus {
    border-color: rgb(var(--primary));
}

.media-explorer__commandbar-right {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.media-explorer__select-all {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #64748b;
    cursor: pointer;
    user-select: none;
}

.media-explorer__view-toggle {
    display: flex;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
}

.media-explorer__view-btn {
    width: 34px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-right: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 0.8125rem;
    cursor: pointer;
    transition: background 0.12s, color 0.12s;
}

.media-explorer__view-btn:last-child {
    border-right: none;
}

.media-explorer__view-btn:hover {
    background: #f1f5f9;
    color: #334155;
}

.media-explorer__view-btn--active {
    background: rgb(var(--primary) / 0.12);
    color: rgb(var(--primary));
}

.media-explorer__grid {
    display: grid;
    gap: 12px;
    padding: 16px;
    flex: 1;
    align-content: start;
    overflow-y: auto;
}

.media-explorer__grid--extra-large {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
}

.media-explorer__grid--large {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.media-explorer__grid--medium {
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
}

.media-explorer__grid--small {
    grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
    gap: 8px;
}

.media-explorer__item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 6px 6px;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: default;
    transition: background 0.12s, border-color 0.12s;
}

.media-explorer__item:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
}

.media-explorer__item--selected {
    background: rgb(var(--primary) / 0.08);
    border-color: rgb(var(--primary) / 0.35);
}

.media-explorer__item-check {
    position: absolute;
    top: 4px;
    left: 4px;
    z-index: 2;
    cursor: pointer;
}

.media-explorer__item-check input {
    width: 14px;
    height: 14px;
    cursor: pointer;
}

.media-explorer__item-preview {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 6px;
}

.media-explorer__grid--extra-large .media-explorer__item-preview {
    aspect-ratio: 1;
}

.media-explorer__grid--large .media-explorer__item-preview {
    aspect-ratio: 1;
}

.media-explorer__grid--medium .media-explorer__item-preview {
    aspect-ratio: 1;
}

.media-explorer__grid--small .media-explorer__item-preview {
    aspect-ratio: 1;
}

.media-explorer__item-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 4px;
}

.media-explorer__item-actions {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -55%);
    display: flex;
    gap: 4px;
    padding: 4px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s;
    z-index: 3;
}

.media-explorer__item:hover .media-explorer__item-actions {
    opacity: 1;
    pointer-events: auto;
}

.media-explorer__item-actions button {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #475569;
    font-size: 0.75rem;
    cursor: pointer;
}

.media-explorer__item-actions button:hover {
    background: #f1f5f9;
    color: rgb(var(--primary));
}

.media-explorer__item-meta {
    width: 100%;
    text-align: center;
    min-width: 0;
}

.media-explorer__item-name {
    font-size: 0.6875rem;
    font-weight: 500;
    color: #334155;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.media-explorer__grid--small .media-explorer__item-name,
.media-explorer__grid--small .media-explorer__item-size {
    display: none;
}

.media-explorer__item-size {
    font-size: 0.625rem;
    color: #94a3b8;
    margin: 2px 0 0;
}

.media-explorer__list-wrap {
    flex: 1;
    overflow: auto;
}

.media-explorer__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.media-explorer__table thead {
    position: sticky;
    top: 0;
    background: #fafbfc;
    z-index: 1;
}

.media-explorer__table th {
    text-align: left;
    padding: 8px 12px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.media-explorer__table td {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.media-explorer__table tbody tr:hover {
    background: #f8fafc;
}

.media-explorer__table-row--selected {
    background: rgb(var(--primary) / 0.06) !important;
}

.media-explorer__list-thumb {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

.media-explorer__list-actions {
    display: flex;
    justify-content: flex-end;
    gap: 4px;
}

.media-explorer__list-actions button {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #64748b;
    cursor: pointer;
}

.media-explorer__list-actions button:hover {
    background: #f1f5f9;
    color: rgb(var(--primary));
}

.media-explorer__empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 16px;
}

.media-explorer__pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 12px;
    border-top: 1px solid #e2e8f0;
}

.media-explorer__pagination button {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #475569;
    cursor: pointer;
}

.media-explorer__pagination button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.media-explorer__icon-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    color: #64748b;
    cursor: pointer;
}

@media (max-width: 768px) {
    .media-explorer__body {
        flex-direction: column;
    }

    .media-explorer__sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        max-height: 160px;
    }
}
</style>
