<template>
    <section class="mb-10 sm:mb-20">
        <div class="container mt-6">
            <h3 class="text-3xl font-bold capitalize max-sm:text-xl mb-6">
                {{ $t('label.all_categories') }}
            </h3>

            <div v-if="categories.length > 0">
                <div class="category-page-grid grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 sm:gap-5">
                    <div v-for="category in categories" :key="category.id" class="category-page-grid__cell relative h-full z-0 hover:z-20 focus-within:z-20">
                        <router-link
                            :to="{ name: 'frontend.product', query: { category: category.slug } }"
                            class="category-card w-full h-full flex flex-col items-center gap-2 sm:gap-3 group relative isolate">
                            <div class="category-card__media w-full aspect-square rounded-2xl overflow-hidden bg-[#fafafa] border border-gray-100 transition-all duration-300 group-hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] group-hover:border-primary/20 group-hover:-translate-y-0.5">
                                <img
                                    v-if="category.thumb && !category.thumb.includes('default/category')"
                                    class="w-full h-full object-cover block transition-transform duration-500 group-hover:scale-[1.04]"
                                    :src="category.thumb"
                                    :alt="category.name"
                                    loading="lazy"
                                    @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-4')"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-50/50">
                                    <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-1/2 h-1/2 object-contain opacity-40">
                                </div>
                            </div>
                            <span class="category-card__label text-xs sm:text-sm md:text-base font-bold capitalize text-center leading-snug line-clamp-2 min-h-[2.5em] group-hover:text-primary transition-colors px-1">
                                {{ category.name }}
                            </span>
                        </router-link>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-10">
                <p class="text-gray-500 font-medium">{{ $t('label.no_category_found') }}</p>
            </div>
        </div>
    </section>
</template>

<script>
import { trackCategoryViewed } from "../../../services/analyticsEcommerceBridge";

export default {
    name: "CategoryComponent",
    computed: {
        categories: function () {
            // Using the existing tree of categories
            return this.$store.getters['frontendProductCategory/trees'];
        },
    },
    mounted() {
        window.scrollTo(0, 0);
        trackCategoryViewed({ slug: 'all', name: 'all_categories' });
    }
}
</script>
