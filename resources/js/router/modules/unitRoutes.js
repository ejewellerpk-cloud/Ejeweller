const UnitComponent = () => import("../../components/admin/settings/Unit/UnitComponent");
const UnitListComponent = () => import("../../components/admin/settings/Unit/UnitListComponent");

export default [
    {
        path: '/admin/units',
        component: UnitComponent,
        name: 'admin.unit',
        redirect: { name: 'admin.unit.list' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'settings',
            breadcrumb: 'units',
        },
        children: [
            {
                path: '',
                component: UnitListComponent,
                name: 'admin.unit.list',
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
