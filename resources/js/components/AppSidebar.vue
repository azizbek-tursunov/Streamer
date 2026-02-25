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
import { BookOpen, Folder, LayoutGrid, Camera, Shield, Settings } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { index as usersIndex } from '@/routes/users';
import { index as rolesIndex } from '@/routes/roles';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as camerasIndex } from '@/routes/cameras';
import { index as facultiesIndex } from '@/routes/faculties';
import { index as auditoriumsIndex } from '@/actions/App/Http/Controllers/AuditoriumController';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Boshqaruv Paneli',
        href: dashboard(),
        icon: LayoutGrid,
        isActive: urlIsActive(dashboard().url, page.url),
    },
    {
        title: 'Kameralar',
        href: '/cameras',
        icon: Camera,
        isActive: urlIsActive('/cameras', page.url),
    },
    {
        title: "Ma'lumotnomalar",
        href: '#',
        icon: Folder,
        isActive: ['/faculties', auditoriumsIndex().url].some(path => urlIsActive(path, page.url)),
        items: [
            {
                title: 'Fakultetlar',
                href: facultiesIndex().url,
            },
            {
                title: 'Auditoriyalar',
                href: auditoriumsIndex().url,
            },
        ],
    },
    {
        title: "Xavfsizlik",
        href: '#',
        icon: Shield,
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
        title: "HEMIS Sozlamalari",
        href: '/hemis',
        icon: Settings,
        isActive: urlIsActive('/hemis', page.url),
    },
]);

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
