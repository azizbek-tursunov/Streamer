```
<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import {
    Map,
    FolderOpen,
    SquareChartGantt,
    Settings,
    Video,
    GraduationCap,
    FolderKey,
    LayoutGrid,
    Folder,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { index as usersIndex } from '@/routes/users';
import { index as rolesIndex } from '@/routes/roles';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as camerasIndex } from '@/routes/cameras';
import { index as facultiesIndex } from '@/routes/faculties';
import { index as auditoriumsIndex } from '@/actions/App/Http/Controllers/AuditoriumController';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const userPermissions = page.props.auth?.user?.permissions || [];
    const userRoles = page.props.auth?.user?.roles || [];
    
    // Admin checking or global super-admin check
    const isSuperAdmin = userRoles.includes('super-admin') || userPermissions.length > 20;

    const checkAccess = (item: NavItem) => {
        if (isSuperAdmin) return true;
        
        let hasPerm = !item.permissions || item.permissions.length === 0 || item.permissions.some(p => userPermissions.includes(p));
        let hasRole = !item.roles || item.roles.length === 0 || item.roles.some(r => userRoles.includes(r));

        if (item.permissions && item.roles) {
            return hasPerm || hasRole;
        }

        if (item.permissions) return hasPerm;
        if (item.roles) return hasRole;
        
        return true;
    };

    const rawItems: NavItem[] = [
        {
            title: 'Boshqaruv Paneli',
            href: dashboard(),
            icon: LayoutGrid,
            isActive: urlIsActive(dashboard().url, page.url),
        },
        {
            title: 'Kameralar',
            href: '#',
            icon: Video,
            permissions: ['view-cameras', 'view-camera-grid'],
            isActive: ['/cameras/grid', '/cameras'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: "Ro'yxat",
                    href: '/cameras',
                    permissions: ['view-cameras'],
                },
                {
                    title: 'Mozaika',
                    href: '/cameras/grid',
                    permissions: ['view-camera-grid'],
                },
            ],
        },
        {
            title: "O'quv jarayoni",
            href: '#',
            icon: GraduationCap,
            permissions: ['view-auditoriums', 'view-feedbacks'],
            isActive: ['/auditoriums', '/feedbacks'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'Auditoriyalar',
                    href: '/auditoriums',
                    permissions: ['view-auditoriums'],
                },
                {
                    title: 'Dars tahlili',
                    href: '/feedbacks',
                    permissions: ['view-feedbacks'],
                },
            ],
        },
        {
            title: "Ma'lumotnomalar",
            href: '#',
            icon: Folder,
            permissions: ['manage-users'], // Restrict to admin-level roughly
            isActive: ['/faculties'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'Fakultetlar',
                    href: facultiesIndex().url,
                },
            ],
        },
        {
            title: "Tizim",
            href: '#',
            icon: FolderKey,
            permissions: ['manage-users'],
            isActive: ['/users', '/roles', '/permissions'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'Foydalanuvchilar',
                    href: '/users',
                },
                {
                    title: 'Rollar',
                    href: '/roles',
                },
                {
                    title: 'Ruxsatnomalar',
                    href: '/permissions',
                },
            ],
        },
        {
            title: "Sozlamalar",
            href: '#',
            icon: Settings,
            roles: ['super-admin'],
            isActive: ['/hemis', '/hemis-auth'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'HEMIS API',
                    href: '/hemis',
                },
                {
                    title: 'HEMIS Avtorizatsiya',
                    href: '/hemis-auth',
                },
            ],
        },
    ];

    return rawItems.filter(item => checkAccess(item));
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
