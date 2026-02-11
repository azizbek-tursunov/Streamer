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
import { BookOpen, Folder, LayoutGrid, Camera, Shield } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { index as usersIndex } from '@/routes/users';
import { index as rolesIndex } from '@/routes/roles';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as camerasIndex } from '@/routes/cameras';
import { index as branchesIndex } from '@/routes/branches';
import { index as floorsIndex } from '@/routes/floors';
import { index as facultiesIndex } from '@/routes/faculties';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Boshqaruv Paneli',
        href: dashboard(),
        icon: LayoutGrid,
        isActive: urlIsActive(dashboard().url, page.url),
    },
    {
        title: 'Rasmlar',
        href: '/snapshots', // Manual route for now
        icon: BookOpen, // Using BookOpen as placeholder for Gallery/History
        isActive: urlIsActive('/snapshots', page.url),
    },
    {
        title: 'Kameralar',
        href: '#',
        icon: Camera,
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
        title: "Ma'lumotnomalar",
        href: '#',
        icon: Folder,
        isActive: ['/branches', '/floors', '/faculties'].some(path => urlIsActive(path, page.url)),
        items: [
            {
                title: 'Filiallar',
                href: branchesIndex().url,
            },
            {
                title: 'Qavatlar',
                href: floorsIndex().url,
            },
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
