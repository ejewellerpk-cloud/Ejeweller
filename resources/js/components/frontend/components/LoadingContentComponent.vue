<template>
    <template v-if="props.isActive">
        <ProductCardSkeleton
            v-for="n in count"
            :key="'sk-' + n"
            class="col-span-1"
            aria-hidden="true"
        />
    </template>
</template>

<script>
import ProductCardSkeleton from './skeleton/ProductCardSkeleton.vue';

export default {
    name: 'LoadingContentComponent',
    components: { ProductCardSkeleton },
    props: {
        props: {
            type: Object,
            required: true,
        },
        count: {
            type: Number,
            default: 4,
        },
    },
};
</script>
