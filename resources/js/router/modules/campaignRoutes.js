const CampaignComponent = () => import('../../components/admin/campaigns/CampaignComponent.vue');
const CampaignHubComponent = () => import('../../components/admin/campaigns/CampaignHubComponent.vue');

export default [
    {
        path: '/admin/campaigns',
        component: CampaignComponent,
        name: 'admin.campaigns',
        redirect: { name: 'admin.campaigns.hub' },
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: 'campaigns',
            breadcrumb: 'campaigns',
        },
        children: [
            {
                path: '',
                component: CampaignHubComponent,
                name: 'admin.campaigns.hub',
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: 'campaigns',
                    breadcrumb: '',
                },
            },
        ],
    },
];
