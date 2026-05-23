<template>
    <button
        class="h-8 px-3 flex items-center gap-2 text-xs tracking-wide capitalize rounded-md shadow text-white bg-primary"
        @click.prevent="handleClick">
        <i class="lab lab-line-add-circle"></i>
        <span>{{ props.title }}</span>
    </button>
</template>

<script>

import { useCanvas } from "../../../../composables/canvas";

export default {
    name: "SmSidebarModalCreateComponent",
    props: ['props'],
    data() {
        return {
            openCanvas: useCanvas().openCanvas
        }
    },
    methods: {
        handleClick() {
            const sidebars = document.querySelectorAll('#sidebar');
            console.log(`SmSidebarModalCreateComponent: Found ${sidebars.length} elements with id="sidebar".`);
            sidebars.forEach((el, i) => console.log(`Sidebar ${i}:`, el.closest('.db-main') ? 'Inside .db-main' : 'Outside .db-main', el));
            console.log("SmSidebarModalCreateComponent: 'Add Product' button clicked! Opening sidebar...");
            this.openCanvas('sidebar');
        }
    }
}
</script>