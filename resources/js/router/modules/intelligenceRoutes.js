const IntelligenceComponent = () => import('../../components/admin/intelligence/IntelligenceComponent.vue');
const IntelligenceDashboardComponent = () => import('../../components/admin/intelligence/IntelligenceDashboardComponent.vue');

export default [
    {
        path: '/admin/intelligence',
        component: IntelligenceComponent,
        name: 'admin.intelligence',
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'dashboard',
            breadcrumb: 'intelligence',
        },
        children: [
            {
                path: '',
                component: IntelligenceDashboardComponent,
                name: 'admin.intelligence.dashboard',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'dashboard',
                    breadcrumb: 'intelligence_dashboard',
                },
            },
        ],
    },
];
