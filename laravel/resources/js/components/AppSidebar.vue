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
import { BookOpen, Folder, LayoutGrid, Camera, Shield, Settings, KeySquare } from 'lucide-vue-next';
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
    
    // Admin checking or global super-admin check
    const isSuperAdmin = userPermissions.length > 20; // super admins get all permissions implicitly, but we can just check if they have specific ones

    const checkPermission = (perms?: string[]) => {
        if (!perms || perms.length === 0) return true;
        return perms.some(p => userPermissions.includes(p));
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
            icon: Camera,
            permissions: ['manage-cameras', 'view-streams'], // example permissions
            isActive: ['/cameras', '/cameras/grid'].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: "Ro'yhat",
                    href: '/cameras',
                },
                {
                    title: 'Mozaika',
                    href: '/cameras/grid',
                },
            ],
        },
        {
            title: "O'quv jarayoni",
            href: '#',
            icon: BookOpen,
            permissions: ['view-streams', 'add-comments', 'analyze-comments'],
            isActive: ['/feedbacks', auditoriumsIndex().url].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'Auditoriyalar',
                    href: auditoriumsIndex().url,
                },
                {
                    title: 'Dars tahlili',
                    href: '/feedbacks',
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
            title: "Xavfsizlik",
            href: '#',
            icon: Shield,
            permissions: ['manage-users', 'manage-roles', 'manage-permissions'],
            isActive: [usersIndex().url, rolesIndex().url, permissionsIndex().url].some(path => urlIsActive(path, page.url)),
            items: [
                {
                    title: 'Foydalanuvchilar',
                    href: usersIndex().url,
                },
                {
                    title: 'Rollar',
                    href: rolesIndex().url,
                },
                {
                    title: 'Ruxsatnomalar',
                    href: permissionsIndex().url,
                },
            ],
        },
        {
            title: "Sozlamalar",
            href: '#',
            icon: Settings,
            permissions: ['manage-sync'],
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

    return rawItems.filter(item => checkPermission(item.permissions));
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
