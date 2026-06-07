const ProductAttributeComponent = () => import("../../components/admin/settings/ProductAttribute/ProductAttributeComponent");
const ProductAttributeListComponent = () => import("../../components/admin/settings/ProductAttribute/ProductAttributeListComponent");
const ProductAttributeShowComponent = () => import("../../components/admin/settings/ProductAttribute/ProductAttributeShowComponent");

export default [
    {
        path: '/admin/product-attributes',
        component: ProductAttributeComponent,
        name: 'admin.productAttribute',
        redirect: { name: 'admin.productAttribute.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'settings',
            breadcrumb: 'product_attributes',
        },
        children: [
            {
                path: '',
                component: ProductAttributeListComponent,
                name: 'admin.productAttribute.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'settings',
                    breadcrumb: '',
                },
            },
            {
                path: 'show/:id',
                component: ProductAttributeShowComponent,
                name: 'admin.productAttribute.show',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'settings',
                    breadcrumb: 'view',
                },
            },
        ],
    },
];
