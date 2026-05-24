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
                            class="w-full flex flex-col items-center gap-2 sm:gap-3 group">
                            
                            <div class="w-full aspect-square rounded-2xl overflow-hidden bg-[#fafafa] border border-gray-100 transition-all duration-300 group-hover:shadow-[0_4px_15px_rgba(0,0,0,0.05)] group-hover:border-primary/20">
                                <img class="w-full h-full object-cover block transition-transform duration-500 group-hover:scale-[1.05]" 
                                    :src="category.thumb" :alt="category.name" loading="lazy" />
                            </div>
                            
                            <span class="text-sm sm:text-base md:text-lg font-bold capitalize text-center leading-snug group-hover:text-primary transition-colors px-1 whitespace-normal">
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
