const ProfileEditProfileComponent = () =>   import("../../components/admin/profile/ProfileEditProfileComponent");
const ProfileChangePasswordComponent = () =>   import("../../components/admin/profile/ProfileChangePasswordComponent");
const ProfileActiveSessionsComponent = () =>   import("../../components/admin/profile/ProfileActiveSessionsComponent");
const ProfilePushDevicesComponent = () =>   import("../../components/admin/profile/ProfilePushDevicesComponent");


export default [
    {
        path: "/admin/profile/edit-profile",
        component: ProfileEditProfileComponent,
        name: "admin.profile.editProfile",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "",
            breadcrumb: "edit_profile",
        },
    },
    {
        path: "/admin/profile/change-password",
        component: ProfileChangePasswordComponent,
        name: "admin.profile.changePassword",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "",
            breadcrumb: "change_password",
        },
    },
    {
        path: "/admin/profile/active-devices",
        component: ProfileActiveSessionsComponent,
        name: "admin.profile.activeDevices",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "",
            breadcrumb: "active_devices",
        },
    },
    {
        path: "/admin/profile/push-devices",
        component: ProfilePushDevicesComponent,
        name: "admin.profile.pushDevices",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "",
            breadcrumb: "push_devices",
        },
    }
];
