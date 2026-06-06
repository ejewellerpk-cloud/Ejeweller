const WhatsappOrdersComponent = () => import("../../components/admin/whatsappOrders/WhatsappOrdersComponent");
const WhatsappOrderListComponent = () => import("../../components/admin/whatsappOrders/WhatsappOrderListComponent");
const WhatsappOrderShowComponent = () => import("../../components/admin/whatsappOrders/WhatsappOrderShowComponent");

export default [
    {
        path: "/admin/whatsapp-orders",
        component: WhatsappOrdersComponent,
        name: "admin.whatsapp.orders",
        redirect: { name: "admin.whatsapp.orders.list" },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'whatsapp-orders',
            breadcrumb: 'whatsapp_orders'
        },
        children: [
            {
                path: "",
                component: WhatsappOrderListComponent,
                name: "admin.whatsapp.orders.list",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "whatsapp-orders",
                    breadcrumb: "",
                },
            },
            {
                path: "show/:id",
                component: WhatsappOrderShowComponent,
                name: "admin.whatsapp.orders.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "whatsapp-orders",
                    breadcrumb: "view",
                },
            }
        ],
    },
];
