<template>
    <div class="col-12">
        <BreadcrumbComponent />
    </div>

    <div class="col-12">
        <UserFcmTokensComponent mode="self" />
    </div>
</template>

<script>
import BreadcrumbComponent from "../components/BreadcrumbComponent";
import UserFcmTokensComponent from "../components/UserFcmTokensComponent";

export default {
    name: "ProfilePushDevicesComponent",
    components: {
        BreadcrumbComponent,
        UserFcmTokensComponent,
    },
};
</script>
