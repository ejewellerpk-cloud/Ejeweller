<template>
    <aside id="mobile-category-canvas"
        class="fixed inset-0 z-30 bg-black/50 duration-500 transition-all invisible opacity-0">
        <div
            class="w-full max-w-xs h-dvh overflow-x-hidden overflow-y-auto bg-white duration-500 transition-all ltr:-translate-x-full rtl:translate-x-full">
            <div class="py-4 flex items-center justify-between px-4 border-b border-slate-100">
                <router-link :to="{ name: 'frontend.home' }"
                    class="router-link-active router-link-exact-active flex-shrink-0">
                    <img class="w-28 sm:w-32" :src="setting.theme_logo" alt="logo">
                </router-link>

                <button type="button">
                    <i @click.prevent="hideTarget('mobile-category-canvas', 'canvas-active')"
                        class="lab-line-circle-cross text-xl text-danger"></i>
                </button>
            </div>

            <div v-if="categories.length > 0" class="px-4 pb-10">
                <div class="grid grid-cols-3 gap-3 mt-4">
                    <div v-for="category in categories" :key="category.id" class="relative">
                        <router-link v-on:click="hideTarget('mobile-category-canvas', 'canvas-active')"
                            :to="{ name: 'frontend.product', query: { category: category.slug } }"
                            class="w-full block rounded-xl shadow-xs group border border-gray-100 bg-white hover:border-primary transition-all duration-300">
                            
                            <img class="w-full aspect-square object-cover block rounded-tl-xl rounded-tr-xl" 
                                :src="category.thumb" alt="category" >
                            
                            <div class="py-2 px-2 flex items-center justify-between gap-1">
                                <span class="text-xs font-semibold capitalize overflow-hidden whitespace-nowrap text-ellipsis group-hover:text-primary transition-colors"
                                    :class="category.children.length > 0 ? 'w-[calc(100%-24px)] text-left' : 'w-full text-center'">
                                    {{ category.name }}
                                </span>

                                <button v-if="category.children.length > 0"
                                    @click.prevent.stop="showTarget('mobile_category_' + category.slug, '!translate-x-0')"
                                    type="button" class="w-6 h-6 flex-shrink-0 flex items-center justify-center rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                                    <i class="lab-line-chevron-right text-[10px]"></i>
                                </button>
                            </div>
                        </router-link>

                        <MobileMenuChildrenComponent :key="category" v-if="category.children" :parentCategory="category"
                            :categories="category.children" />
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>
<script>
import targetService from "../../../services/targetService";
import MobileMenuChildrenComponent from "../../frontend/components/MobileMenuChildrenComponent.vue";

export default {
    name: "FrontendMobileCategoryComponent",
    components: { MobileMenuChildrenComponent },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        categories: function () {
            return this.$store.getters['frontendProductCategory/trees'];
        },
    },
    methods: {
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        hideTarget: function (id, cClass) {
            targetService.hideTarget(id, cClass);
        }
    }
}
</script>