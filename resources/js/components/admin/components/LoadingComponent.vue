<template>
    <!--
      Full-screen overlay is visual-only (see app.css .velmld-full-screen).
      pointer-events:none keeps the admin sidebar/header clickable while page APIs load.
    -->
    <VueElementLoading spinner="bar-fade-scale" color="#FD8B0E" :active="props.isActive" :is-full-screen="true" />
</template>

<script>
import VueElementLoading from 'vue-element-loading';

export default {
    name: "LoadingComponent",
    components: { VueElementLoading },
    props: ['props'],
    data() {
        return {
            isActive: false
        }
    }
}
</script>