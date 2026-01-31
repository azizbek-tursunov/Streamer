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
import { BookOpen, Folder, LayoutGrid, Camera } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

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
                href: '/branches',
            },
            {
                title: 'Qavatlar',
                href: '/floors',
            },
            {
                title: 'Fakultetlar',
                href: '/faculties',
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
