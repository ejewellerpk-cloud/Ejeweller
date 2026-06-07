const ProductCategoryComponent = () => import("../../components/admin/settings/ProductCategory/ProductCategoryComponent");
const ProductCategoryListComponent = () => import("../../components/admin/settings/ProductCategory/ProductCateogryListComponent");
const ProductCategoryShowComponent = () => import("../../components/admin/settings/ProductCategory/ProductCategoryShowComponent");

export default [
    {
        path: '/admin/product-categories',
        component: ProductCategoryComponent,
        name: 'admin.productCategory',
        redirect: { name: 'admin.productCategory.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'settings',
            breadcrumb: 'product_categories',
        },
        children: [
            {
                path: '',
                component: ProductCategoryListComponent,
                name: 'admin.productCategory.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'settings',
                    breadcrumb: '',
                },
            },
            {
                path: 'show/:id',
                component: ProductCategoryShowComponent,
                name: 'admin.productCategory.show',
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
