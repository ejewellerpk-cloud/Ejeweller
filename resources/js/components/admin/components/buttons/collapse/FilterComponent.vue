<template>
    <button type="button" class="db-card-filter-btn table-filter-btn" v-bind="$attrs">
        <i class="lab lab-line-filter lab-font-size-14"></i>
        <span>{{ $t('button.filter') }}</span>
    </button>
</template>

<script>
export default {
    name: "FilterComponent",
    inheritAttrs: false,
}
</script>
