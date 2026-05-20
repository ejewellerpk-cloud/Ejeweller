<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-5xl h-[80vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="p-6 border-b flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-xl font-black text-heading uppercase tracking-tight">Select Media Asset</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pick an existing image from your library</p>
                </div>
                <button @click="$emit('close')" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-xmark text-slate-500"></i>
                </button>
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
                <div v-if="loading" class="h-full flex items-center justify-center">
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
                <div class="flex items-center gap-3">
                    <button @click="$emit('close')" class="px-6 py-2 text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';

export default {
    name: "MediaPickerComponent",
    props: {
        show: { type: Boolean, default: false }
    },
    data() {
        return {
            loading: false,
            search: "",
            filterTimer: null
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
            if (newVal) this.fetchMedia();
        }
    },
    methods: {
        ...mapActions({
            lists: 'media/lists'
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
        }
    }
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
