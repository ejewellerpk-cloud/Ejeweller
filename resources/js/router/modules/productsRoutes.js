const ProductComponent = ()=> import("../../components/admin/products/ProductComponent");
const ProductListComponent = ()=> import("../../components/admin/products/ProductListComponent");
const ProductShowComponent = ()=> import("../../components/admin/products/ProductShowComponent");
const ProductCreateAndEditComponent = ()=> import("../../components/admin/products/ProductCreateAndEditComponent");

export default [
    {
        path: '/admin/products',
        component: ProductComponent,
        name: 'admin.products',
        redirect: { name: 'admin.products.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'products',
            breadcrumb: 'products'
        },
        children: [
            {
                path: '',
                component: ProductListComponent,
                name: 'admin.products.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'products',
                    breadcrumb: ''
                },
            },
            {
                path: 'add',
                component: ProductCreateAndEditComponent,
                name: 'admin.products.create',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'products_create',
                    breadcrumb: 'create',
                },
            },
            {
                path: 'edit/:id',
                component: ProductCreateAndEditComponent,
                name: 'admin.products.edit',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'products_edit',
                    breadcrumb: 'edit',
                },
            },
            {
                path: "show/:id",
                component: ProductShowComponent,
                name: "admin.product.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "products",
                    breadcrumb: "view",
                },
            }
        ]
    }
]
