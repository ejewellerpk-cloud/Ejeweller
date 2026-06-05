<template>
    <div class="col-12">
        <BreadcrumbComponent />
    </div>

    <div class="col-12">
        <UserSessionsComponent mode="self" />
    </div>
</template>

<script>
import BreadcrumbComponent from "../components/BreadcrumbComponent";
import UserSessionsComponent from "../components/UserSessionsComponent";

export default {
    name: "ProfileActiveSessionsComponent",
    components: {
        BreadcrumbComponent,
        UserSessionsComponent,
    },
};
</script>
