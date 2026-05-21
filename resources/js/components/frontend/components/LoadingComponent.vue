<template>
    <VueElementLoading spinner="bar-fade-scale" color="#FD8B0E" :active="props.isActive" :is-full-screen="isFullScreen" />
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