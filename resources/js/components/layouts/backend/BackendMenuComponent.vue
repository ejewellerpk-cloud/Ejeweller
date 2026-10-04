<template>
    <aside class="db-sidebar">
        <div class="db-sidebar-header">
            <router-link class="w-24" :to="{ name: 'frontend.home' }">
                <img :src="setting.theme_logo" alt="logo">
            </router-link>
            <button type="button" @click="closeSidebar" class="fa-solid fa-xmark xmark-btn close-db-menu"></button>
        </div>

        <nav class="db-sidebar-nav">
            <ul class="db-sidebar-nav-list">
                <template v-for="(menu, menuIndex) in menus" :key="menuKey(menu, menuIndex)">
                    <!-- Expandable section (title or parent with children) -->
                    <li
                        v-if="hasChildren(menu)"
                        class="db-sidebar-nav-group"
                        :class="{ 'is-open': isGroupOpen(menuKey(menu, menuIndex)), 'has-active': groupHasActiveRoute(menu) }"
                    >
                        <button
                            type="button"
                            class="db-sidebar-nav-group-toggle"
                            @click="toggleGroup(menuKey(menu, menuIndex))"
                        >
                            <i v-if="menu.icon" class="text-sm" :class="menu.icon"></i>
                            <span class="db-sidebar-nav-group-label">{{ menuTitle(menu) }}</span>
                            <i class="fa-solid fa-chevron-right db-sidebar-nav-group-chevron"></i>
                        </button>

                        <ul class="db-sidebar-nav-dropdown" :class="{ 'is-open': isGroupOpen(menuKey(menu, menuIndex)) }">
                            <li
                                v-if="!isSectionTitle(menu) && menu.url && menu.url !== '#'"
                                class="db-sidebar-nav-item"
                                @click="onNavClick"
                            >
                                <router-link :to="'/admin/' + menu.url" class="db-sidebar-nav-menu">
                                    <i class="text-sm" :class="menu.icon"></i>
                                    <span class="text-base flex-auto">{{ menuTitle(menu) }}</span>
                                </router-link>
                            </li>
                            <li
                                v-for="(child, childIndex) in menu.children"
                                :key="menuKey(child, childIndex)"
                                class="db-sidebar-nav-item"
                                @click="onNavClick"
                            >
                                <router-link :to="'/admin/' + child.url" class="db-sidebar-nav-menu">
                                    <i class="text-sm" :class="child.icon"></i>
                                    <span class="text-base flex-auto">{{ menuTitle(child) }}</span>
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Direct top-level link -->
                    <li
                        v-else
                        class="db-sidebar-nav-item"
                        @click="onNavClick"
                    >
                        <router-link :to="'/admin/' + menu.url" class="db-sidebar-nav-menu">
                            <i class="text-sm" :class="menu.icon"></i>
                            <span class="text-base flex-auto">{{ menuTitle(menu) }}</span>
                        </router-link>
                    </li>
                </template>
            </ul>

            <!-- Static Media Menu -->
            <ul class="db-sidebar-nav-list db-sidebar-nav-list--footer">
                <li class="db-sidebar-nav-item" @click="onNavClick">
                    <router-link :to="{ name: 'admin.media' }" class="db-sidebar-nav-menu">
                        <i class="fa-solid fa-photo-film text-sm"></i>
                        <span class="text-base flex-auto">Media Manager</span>
                    </router-link>
                </li>
            </ul>
        </nav>
    </aside>
</template>

<script>
import appService from "../../../services/appService";

export default {
    name: "BackendMenuComponent",
    data() {
        return {
            openGroups: {},
        };
    },
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },
        menus() {
            return this.$store.getters.authMenu || [];
        },
    },
    watch: {
        menus: {
            immediate: true,
            handler() {
                this.$nextTick(() => this.expandActiveGroups());
            },
        },
        '$route.fullPath'() {
            this.$nextTick(() => this.expandActiveGroups());
        },
    },
    mounted() {
        this.expandActiveGroups();
    },
    methods: {
        menuKey(item, index) {
            return item?.id || item?.language || item?.url || `menu-${index}`;
        },
        isSectionTitle(menu) {
            return !menu?.url || menu.url === '#';
        },
        hasChildren(menu) {
            return Array.isArray(menu?.children) && menu.children.length > 0;
        },
        isGroupOpen(key) {
            return Boolean(this.openGroups[key]);
        },
        toggleGroup(key) {
            this.openGroups = {
                ...this.openGroups,
                [key]: !this.openGroups[key],
            };
        },
        groupHasActiveRoute(menu) {
            const path = this.$route?.path || '';
            if (menu?.url && menu.url !== '#' && path.includes('/admin/' + menu.url)) {
                return true;
            }
            return (menu?.children || []).some((child) => child?.url && path.includes('/admin/' + child.url));
        },
        expandActiveGroups() {
            const next = { ...this.openGroups };
            (this.menus || []).forEach((menu, index) => {
                if (!this.hasChildren(menu)) {
                    return;
                }
                const key = this.menuKey(menu, index);
                if (this.groupHasActiveRoute(menu)) {
                    next[key] = true;
                }
            });
            this.openGroups = next;
        },
        onNavClick() {
            this.closeSidebar();
        },
        closeSidebar() {
            return appService.closeSidebar();
        },
        menuTitle(item) {
            if (!item) {
                return '';
            }
            const key = 'menu.' + item.language;
            if (this.$te(key)) {
                return this.$t(key);
            }
            return item.name || item.language || '';
        },
    },
};
</script>
