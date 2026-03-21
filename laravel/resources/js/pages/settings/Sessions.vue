<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';
import { Monitor, Smartphone, Tablet, Trash2, LogOut, CheckCircle, AlertCircle } from 'lucide-vue-next';

interface Session {
    id: string;
    ip_address: string;
    is_current: boolean;
    device: {
        browser: string;
        platform: string;
        type: 'desktop' | 'mobile' | 'tablet';
    };
    last_active: string;
    last_active_at: string;
}

const props = defineProps<{
    sessions: Session[];
}>();

const page = usePage();
const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
const errorMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.error);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Sessiyalar',
        href: '/settings/sessions',
    },
];

const showLogoutAllDialog = ref(false);
const password = ref('');
const passwordError = ref('');
const deletingSessionId = ref<string | null>(null);
const loggingOutAll = ref(false);

const deviceIcon = (type: string) => {
    if (type === 'mobile') return Smartphone;
    if (type === 'tablet') return Tablet;
    return Monitor;
};

const deleteSession = (sessionId: string) => {
    deletingSessionId.value = sessionId;
    router.delete(`/settings/sessions/${sessionId}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingSessionId.value = null;
        },
    });
};

const logoutAllOtherSessions = () => {
    loggingOutAll.value = true;
    passwordError.value = '';

    router.delete('/settings/sessions', {
        data: { password: password.value },
        preserveScroll: true,
        onSuccess: () => {
            showLogoutAllDialog.value = false;
            password.value = '';
        },
        onError: (errors: Record<string, string>) => {
            passwordError.value = errors.password || 'Xatolik yuz berdi.';
        },
        onFinish: () => {
            loggingOutAll.value = false;
        },
    });
};

const otherSessionsCount = computed(() => props.sessions.filter(s => !s.is_current).length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Sessiyalar" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Qurilma sessiyalari"
                    description="Hisobingizga kirgan qurilmalarni boshqaring. Shubhali sessiyalarni o'chiring."
                />

                <!-- Success/Error messages -->
                <div
                    v-if="successMessage"
                    class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                >
                    <CheckCircle class="h-4 w-4 shrink-0" />
                    {{ successMessage }}
                </div>

                <div
                    v-if="errorMessage"
                    class="flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive dark:border-destructive/30 dark:bg-destructive/20"
                >
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    {{ errorMessage }}
                </div>

                <!-- Sessions list -->
                <div class="space-y-3">
                    <Card v-for="session in sessions" :key="session.id">
                        <CardContent class="flex items-center gap-4 p-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                <component
                                    :is="deviceIcon(session.device.type)"
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium truncate">
                                        {{ session.device.browser }} — {{ session.device.platform }}
                                    </p>
                                    <span
                                        v-if="session.is_current"
                                        class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400"
                                    >
                                        Joriy sessiya
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    {{ session.ip_address }} &middot; {{ session.last_active }}
                                </p>
                            </div>

                            <Button
                                v-if="!session.is_current"
                                variant="ghost"
                                size="icon"
                                @click="deleteSession(session.id)"
                                :disabled="deletingSessionId === session.id"
                                class="text-muted-foreground hover:text-destructive shrink-0"
                            >
                                <span
                                    v-if="deletingSessionId === session.id"
                                    class="h-4 w-4 rounded-full border-2 border-current border-t-transparent animate-spin"
                                />
                                <Trash2 v-else class="h-4 w-4" />
                            </Button>
                        </CardContent>
                    </Card>

                    <p v-if="sessions.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                        Faol sessiyalar topilmadi.
                    </p>
                </div>

                <!-- Logout all button -->
                <div v-if="otherSessionsCount > 0" class="pt-2">
                    <Button
                        variant="destructive"
                        @click="showLogoutAllDialog = true"
                    >
                        <LogOut class="mr-2 h-4 w-4" />
                        Boshqa barcha sessiyalarni tugatish ({{ otherSessionsCount }})
                    </Button>
                </div>
            </div>

            <!-- Confirm dialog for logout all -->
            <Dialog v-model:open="showLogoutAllDialog">
                <DialogContent>
                    <DialogTitle>Barcha boshqa sessiyalarni tugatish</DialogTitle>
                    <DialogDescription>
                        Barcha boshqa qurilmalardan chiqish uchun joriy parolingizni kiriting.
                    </DialogDescription>

                    <div class="grid gap-2 py-4">
                        <Label for="password">Parol</Label>
                        <Input
                            id="password"
                            v-model="password"
                            type="password"
                            placeholder="Joriy parolingiz"
                            :class="{ 'border-destructive': passwordError }"
                            @keyup.enter="logoutAllOtherSessions"
                        />
                        <p v-if="passwordError" class="text-sm text-destructive">
                            {{ passwordError }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="showLogoutAllDialog = false">
                            Bekor qilish
                        </Button>
                        <Button
                            variant="destructive"
                            @click="logoutAllOtherSessions"
                            :disabled="loggingOutAll || !password"
                        >
                            <span
                                v-if="loggingOutAll"
                                class="mr-2 h-4 w-4 rounded-full border-2 border-current border-t-transparent animate-spin"
                            />
                            Tasdiqlash
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </SettingsLayout>
    </AppLayout>
</template>
