<template>
    <div class="db-card p-6 pb-20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-2xl font-black text-heading tracking-tight uppercase">Media Asset Manager</h1>
                <p class="text-xs font-medium mt-1 uppercase tracking-widest opacity-60">Global CDN & Resource Hub</p>
            </div>
            <div class="flex items-center gap-4">
                <input type="file" ref="fileInput" class="hidden" multiple accept="image/*" @change="handleFileUpload" />
                <button @click="$refs.fileInput.click()" :disabled="loading"
                    class="db-btn py-2 px-6 text-white bg-primary flex items-center gap-2">
                    <i v-if="loading" class="fa-solid fa-circle-notch animate-spin"></i>
                    <i v-else class="fa-solid fa-plus"></i>
                    <span>Deploy Assets</span>
                </button>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="flex flex-col xl:flex-row items-center justify-between gap-6 mb-8 bg-slate-50 p-4 rounded-xl">
            <div class="relative w-full xl:max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" v-model="search" @input="handleSearch" placeholder="Search assets..."
                    class="w-full pl-12 pr-6 h-12 bg-white border border-slate-200 rounded-xl focus:border-primary outline-none text-sm" />
            </div>
            <div class="flex items-center gap-2">
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

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <div v-for="asset in mediaList" :key="asset.id" 
                class="group relative bg-white border border-slate-100 rounded-2xl overflow-hidden hover:shadow-xl transition-all flex flex-col">
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
                        <th class="db-table-head-th">Asset</th>
                        <th class="db-table-head-th">Name</th>
                        <th class="db-table-head-th">Size</th>
                        <th class="db-table-head-th">Type</th>
                        <th class="db-table-head-th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="db-table-body">
                    <tr v-for="asset in mediaList" :key="asset.id" class="db-table-body-tr">
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
            filterTimer: null
        }
    },
    computed: {
        ...mapGetters({
            mediaList: 'media/lists',
            pagination: 'media/page'
        })
    },
    mounted() {
        this.fetchMedia();
    },
    methods: {
        ...mapActions({
            lists: 'media/lists',
            save: 'media/save',
            destroy: 'media/destroy'
        }),
        fetchMedia(page = 1) {
            this.lists({ page: page, search: this.search });
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
        async handleFileUpload(e) {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;

            const fd = new FormData();
            files.forEach(file => fd.append('files[]', file)); // Laravel works better with files[] for arrays

            this.loading = true;
            try {
                await this.save(fd);
                alertService.success("Assets deployed successfully");
                this.$refs.fileInput.value = null;
            } catch (err) {
                alertService.error("Deployment failed");
            } finally {
                this.loading = false;
            }
        },
        async deleteAsset(id) {
            appService.destroyConfirmation().then(async (res) => {
                try {
                    await this.destroy({ id: id, search: { page: this.pagination.currentPage, search: this.search } });
                    alertService.success("Asset purged successfully");
                } catch (err) {
                    alertService.error("Purge failed");
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
