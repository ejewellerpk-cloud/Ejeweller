const TaxComponent = () => import("../../components/admin/settings/Tax/TaxComponent");
const TaxListComponent = () => import("../../components/admin/settings/Tax/TaxListComponent");

export default [
    {
        path: '/admin/taxes',
        component: TaxComponent,
        name: 'admin.tax',
        redirect: { name: 'admin.tax.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'settings',
            breadcrumb: 'taxes',
        },
        children: [
            {
                path: '',
                component: TaxListComponent,
                name: 'admin.tax.list',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'settings',
                    breadcrumb: '',
                },
            },
        ],
    },
];
