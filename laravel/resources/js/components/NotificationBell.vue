<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { AppPageProps, NotificationItem, AnomalyAlertItem } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<AppPageProps>();

const notifications = computed(() => page.props.notifications ?? {
    unread_count: 0,
    recent: [],
});

const anomalyAlerts = computed(() => page.props.anomalyAlerts ?? {
    open_count: 0,
    recent: [],
});

const totalAlertCount = computed(() => notifications.value.unread_count + anomalyAlerts.value.open_count);

const anomalyLabels: Record<string, string> = {
    lesson_no_people: "Dars bor, odam yo'q",
    people_no_lesson: "Odam bor, dars yo'q",
    camera_offline_during_lesson: 'Dars paytida kamera uzilgan',
    stale_snapshot: 'Snapshot eskirgan',
};

const formatDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const openNotification = (notification: NotificationItem) => {
    const targetUrl = notification.data.url || '/feedbacks';

    if (notification.read_at) {
        router.visit(targetUrl, { preserveScroll: true });
        return;
    }

    router.post(`/notifications/${notification.id}/read`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            router.visit(targetUrl, { preserveScroll: true });
        },
    });
};

const markAllAsRead = () => {
    if (!notifications.value.unread_count) {
        return;
    }

    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const openAnomaly = (anomaly: AnomalyAlertItem) => {
    router.visit(anomaly.url, { preserveScroll: true });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-4 w-4" />
                <span
                    v-if="totalAlertCount"
                    class="absolute right-1 top-1 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
                >
                    {{ totalAlertCount > 9 ? '9+' : totalAlertCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Bildirishnomalar</span>
                <button
                    type="button"
                    class="text-xs text-primary disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!notifications.unread_count"
                    @click="markAllAsRead"
                >
                    Hammasini o'qildi
                </button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="anomalyAlerts.recent.length" class="px-3 py-2">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Ochiq anomaliyalar</span>
                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                        {{ anomalyAlerts.open_count }}
                    </span>
                </div>
                <div class="space-y-1">
                    <button
                        v-for="anomaly in anomalyAlerts.recent"
                        :key="anomaly.id"
                        type="button"
                        class="flex w-full items-start gap-2 rounded-md px-2 py-2 text-left hover:bg-muted"
                        @click="openAnomaly(anomaly)"
                    >
                        <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-1 text-sm font-medium">
                                {{ anomalyLabels[anomaly.type] ?? anomaly.type }}
                            </p>
                            <p class="line-clamp-1 text-xs text-muted-foreground">
                                {{ anomaly.auditorium_name || 'Auditoriya' }}
                            </p>
                        </div>
                    </button>
                </div>
                <DropdownMenuSeparator class="mt-2" />
            </div>

            <div v-if="notifications.recent.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">
                Hozircha bildirishnoma yo'q.
            </div>

            <template v-else>
                <DropdownMenuItem
                    v-for="notification in notifications.recent"
                    :key="notification.id"
                    class="cursor-pointer items-start gap-3 py-3"
                    @click="openNotification(notification)"
                >
                    <span
                        class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="notification.read_at ? 'bg-muted' : 'bg-primary'"
                    />
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="line-clamp-1 text-sm font-medium text-foreground">
                                {{ notification.data.title || 'Yangi fikr-mulohaza' }}
                            </p>
                            <span class="shrink-0 text-[11px] text-muted-foreground">
                                {{ formatDate(notification.created_at) }}
                            </span>
                        </div>
                        <p class="line-clamp-2 text-xs text-muted-foreground">
                            {{ notification.data.body || notification.data.message || 'Fikr-mulohazani ko‘rish uchun bosing.' }}
                        </p>
                    </div>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
