<template>

    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="form" />
    <p class="h-screen"></p>

</template>

<script>
import router from "../../../router";
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import appService from "../../../services/appService";

let isRequestSent = false;

export default {

    name: "SocialLoginComponent",
    components: {
        LoadingComponent
    },
    data() {
        return {
            loading: {
                isActive: true,
            }
        };
    },
    computed: {
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },

    },
    created() {
        this.loading.isActive = true;
        if (this.$route.query.code && !isRequestSent) {
            isRequestSent = true;
            this.loading.isActive = true;
            this.$store.dispatch("verifySocialLogin", { code: { code: this.$route.query.code }, provider: 'google' }).then(res => {
                this.loading.isActive = false;
                alertService.success(res.data.message);
                this.$store.dispatch("frontendWishlist/lists").then().catch();
                if (this.carts.length > 0) {
                    router.push({ name: "frontend.checkout" });
                } else {
                    router.push({ name: "frontend.home" });
                }
                setTimeout(() => {
                    appService.recursiveRouter(router.options.routes, this.$store.getters.authPermission);
                }, 1000);
            }).catch((error) => {
                isRequestSent = false;
                this.loading.isActive = false;
                alertService.error(error.response.data.message);
            });
        }
    },
    methods: {

    }
}
</script>