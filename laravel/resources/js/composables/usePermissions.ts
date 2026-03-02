import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();
    const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
    const userRoles = computed(() => page.props.auth?.user?.roles || []);
    const isSuperAdmin = computed(() => userRoles.value.includes('super-admin'));

    const hasPermission = (perm: string): boolean => {
        return isSuperAdmin.value || userPermissions.value.includes(perm);
    };

    const hasRole = (role: string): boolean => {
        return isSuperAdmin.value || userRoles.value.includes(role);
    };

    return { hasPermission, hasRole, isSuperAdmin, userPermissions, userRoles };
}
