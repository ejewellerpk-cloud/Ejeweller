const MediaGalleryComponent = () => import("../../components/admin/media/MediaGalleryComponent");

export default [
    {
        path: "/admin/media",
        component: MediaGalleryComponent,
        name: "admin.media",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "media",
            breadcrumb: "media",
        },
    },
];
