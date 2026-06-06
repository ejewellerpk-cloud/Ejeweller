const WhatsappOrderComponent = () => import("../../components/admin/whatsappOrders/WhatsappOrderComponent");

export default [
    {
        path: "/admin/whatsapp-order",
        component: WhatsappOrderComponent,
        name: "admin.whatsapp.order",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "whatsapp-order",
        },
    },
];
