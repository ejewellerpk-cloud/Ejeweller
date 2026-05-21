<template>
    <VueElementLoading v-if="isFullScreen" spinner="bar-fade-scale" color="#FD8B0E" :active="props.isActive" :is-full-screen="true" />
    <div v-else-if="props.isActive" class="w-full flex justify-center items-center py-12 opacity-80">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-200 border-t-primary shadow-sm"></div>
    </div>
</template>

<script>
import VueElementLoading from 'vue-element-loading';

export default {
    name: "LoadingComponent",
    components: { VueElementLoading },
    props: {
        props: {
            type: Object,
            required: true
        },
        isFullScreen: {
            type: Boolean,
            default: true
        }
    }
}
</script>