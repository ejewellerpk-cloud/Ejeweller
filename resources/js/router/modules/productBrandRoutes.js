const ProductBrandComponent = () => import("../../components/admin/settings/ProductBrand/ProductBrandComponent");
const ProductBrandListComponent = () => import("../../components/admin/settings/ProductBrand/ProductBrandListComponent");
const ProductBrandShowComponent = () => import("../../components/admin/settings/ProductBrand/ProductBrandShowComponent");

export default [
    {
        path: '/admin/product-brands',
        component: ProductBrandComponent,
        name: 'admin.productBrand',
        redirect: { name: 'admin.productBrand.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'settings',
            breadcrumb: 'product_brands',
        },
        children: [
            {
                path: '',
                component: ProductBrandListComponent,
                name: 'admin.productBrand.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'settings',
                    breadcrumb: '',
                },
            },
            {
                path: 'show/:id',
                component: ProductBrandShowComponent,
                name: 'admin.productBrand.show',
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
