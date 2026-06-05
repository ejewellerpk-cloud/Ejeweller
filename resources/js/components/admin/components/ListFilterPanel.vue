<template>
    <div v-show="show" class="list-filter-panel">
        <slot />
    </div>
</template>

<script>
export default {
    name: "ListFilterPanel",
    props: {
        show: {
            type: Boolean,
            default: false,
        },
    },
};
</script>
