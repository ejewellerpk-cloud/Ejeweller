<template>
    <section class="mb-10 sm:mb-20">
        <div class="container mt-6">
            <h3 class="text-3xl font-bold capitalize max-sm:text-xl mb-6">
                {{ $t('label.all_categories') }}
            </h3>

            <div v-if="categories.length > 0">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 sm:gap-5">
                    <div v-for="category in categories" :key="category.id" class="relative">
                        <router-link
                            :to="{ name: 'frontend.product', query: { category: category.slug } }"
                            class="w-full block rounded-xl shadow-xs group border border-gray-100 bg-white hover:border-primary transition-all duration-300">
                            
                            <img class="w-full aspect-square object-cover block rounded-tl-xl rounded-tr-xl" 
                                :src="category.thumb" :alt="category.name" loading="lazy" />
                            
                            <div class="py-3 px-2 flex flex-col items-center justify-center gap-1">
                                <span class="text-[11px] sm:text-xs md:text-sm font-semibold capitalize overflow-hidden whitespace-nowrap text-ellipsis group-hover:text-primary transition-colors text-center w-full">
                                    {{ category.name }}
                                </span>
                            </div>
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
    }
}
</script>
