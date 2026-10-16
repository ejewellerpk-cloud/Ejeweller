const PostExShipmentComponent = () =>
    import("../../components/admin/postexShipments/PostExShipmentComponent.vue");
const PostExShipmentListComponent = () =>
    import("../../components/admin/postexShipments/PostExShipmentListComponent.vue");

export default [
    {
        path: "/admin/postex-shipments",
        component: PostExShipmentComponent,
        name: "admin.postex.shipments",
        redirect: { name: "admin.postex.shipments.list" },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "postex-shipments",
            breadcrumb: "postex_shipments",
        },
        children: [
            {
                path: "",
                component: PostExShipmentListComponent,
                name: "admin.postex.shipments.list",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "postex-shipments",
                    breadcrumb: "",
                },
            },
        ],
    },
];
