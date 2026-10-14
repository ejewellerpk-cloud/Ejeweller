<template>
    <div>
        <main class="db-main" v-if="logged">
            <BackendNavbarComponent />
            <BackendMenuComponent />
            <router-view></router-view>
            <BackendAiSidebarComponent />
        </main>
        <div v-else>
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import BackendNavbarComponent from "./layouts/backend/BackendNavbarComponent";
import BackendMenuComponent from "./layouts/backend/BackendMenuComponent";
import BackendAiSidebarComponent from "./layouts/backend/BackendAiSidebarComponent.vue";
import DisplayModeEnum from "../enums/modules/displayModeEnum";

export default {
    name: "AdminShell",
    components: {
        BackendNavbarComponent,
        BackendMenuComponent,
        BackendAiSidebarComponent,
    },
    beforeMount() {
        document.body.classList.remove("theme-frontend");
        this.displayModeDefine();
        this.$store.dispatch("frontendSetting/lists").then((res) => {
            this.$store.dispatch("globalState/init", {
                language_id: res.data.data.site_default_language,
                search_restaurant: "",
                location: null,
                latitude: null,
                longitude: null,
            });
        }).catch(() => {});
    },
    computed: {
        logged() {
            return this.$store.getters.authStatus;
        },
        displayMode() {
            return this.$store.getters["globalState/lists"].display_mode;
        },
    },
    methods: {
        displayModeDefine() {
            let dir = "ltr";
            if (this.$store.getters["globalState/lists"].display_mode === DisplayModeEnum.RTL) {
                dir = "rtl";
            }
            document.documentElement.setAttribute("dir", dir);
        },
    },
    watch: {
        displayMode() {
            this.displayModeDefine();
        },
    },
};
</script>
