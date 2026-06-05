<template>
    <button type="button" class="db-card-filter-btn table-filter-btn" @click="handleClick">
        <i class="lab lab-line-filter lab-font-size-14"></i>
        <span>{{ $t('button.filter') }}</span>
    </button>
</template>

<script>
export default {
    name: "FilterComponent",
    emits: ["click"],
    methods: {
        handleClick: function (event) {
            console.log("[FilterComponent] filter button clicked", {
                target: event?.target,
                currentTarget: event?.currentTarget,
            });
            this.$emit("click", event);
        },
    },
}
</script>
