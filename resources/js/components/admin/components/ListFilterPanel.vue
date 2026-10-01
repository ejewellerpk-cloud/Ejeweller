<template>
    <div v-if="asDropdown" class="dropdown-group relative" ref="root">
        <button type="button" class="db-card-filter-btn" @click.stop="toggle">
            <i class="lab lab-line-filter lab-font-size-14"></i>
            <span>{{ $t("button.filter") }}</span>
        </button>
        <div
            v-show="internalOpen"
            class="db-card-filter-form-dropdown"
            role="dialog"
            @click.stop
            @keydown.esc.stop="close"
        >
            <slot :close="close" />
        </div>
    </div>
    <div v-else v-show="show" class="list-filter-panel">
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
        asDropdown: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:show", "close"],
    data() {
        return {
            internalOpen: false,
        };
    },
    mounted() {
        if (this.asDropdown) {
            this.onDocumentClick = (event) => {
                const root = this.$refs.root;
                if (!root || root.contains(event.target)) {
                    return;
                }
                this.close();
            };
            this.onDocumentKeydown = (event) => {
                if (event.key === "Escape") {
                    this.close();
                }
            };
            document.addEventListener("click", this.onDocumentClick);
            document.addEventListener("keydown", this.onDocumentKeydown);
        }
    },
    beforeUnmount() {
        if (this.onDocumentClick) {
            document.removeEventListener("click", this.onDocumentClick);
        }
        if (this.onDocumentKeydown) {
            document.removeEventListener("keydown", this.onDocumentKeydown);
        }
    },
    methods: {
        toggle() {
            this.internalOpen = !this.internalOpen;
        },
        close() {
            this.internalOpen = false;
            this.$emit("close");
        },
    },
};
</script>
