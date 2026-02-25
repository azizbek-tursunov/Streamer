<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Save, Link as LinkIcon, Key, UserCircle, CheckCircle, AlertCircle, Eye, EyeOff } from 'lucide-vue-next';

const page = usePage();

const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
const errorMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.error);

const props = defineProps<{
    clientId: string;
    clientSecret: string;
    urlAuthorize: string;
    urlAccessToken: string;
    urlUserInfo: string;
    redirectUri: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sozlamalar', href: '/hemis' },
    { title: 'HEMIS Avtorizatsiya', href: '/hemis-auth' },
];

const form = useForm({
    client_id: props.clientId,
    client_secret: props.clientSecret,
    url_authorize: props.urlAuthorize,
    url_access_token: props.urlAccessToken,
    url_user_info: props.urlUserInfo,
    redirect_uri: props.redirectUri,
});

const showSecret = ref(false);

const updateSettings = () => {
    form.put('/hemis-auth', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="HEMIS Avtorizatsiya Sozlamalari" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-4 p-8 pt-6 max-w-5xl mx-auto w-full">
            <div class="flex items-center justify-between space-y-2">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">HEMIS OAuth Sozlamalari</h2>
                    <p class="text-muted-foreground mt-1">
                        Professor-o'qituvchilar va xodimlarning yagona darcha orqali tizimga kirishi uchun OAuth 2.0 ma'lumotlari.
                    </p>
                </div>
            </div>

            <!-- Success Toast -->
            <div
                v-if="successMessage"
                class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 mt-4 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                {{ successMessage }}
            </div>

            <!-- Error Toast -->
            <div
                v-if="errorMessage"
                class="flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 mt-4 text-sm text-destructive dark:border-destructive/30 dark:bg-destructive/20"
            >
                <AlertCircle class="h-4 w-4 shrink-0" />
                {{ errorMessage }}
            </div>

            <div class="grid gap-6 mt-6">
                <Card>
                    <form @submit.prevent="updateSettings">
                        <CardHeader>
                            <CardTitle>OAuth Integratsiyasi</CardTitle>
                            <CardDescription>
                                HEMIS axborot tizimining OAuth sahifasida ushbu ilovani ro'yxatdan o'tkazgandan so'ng taqdim etilgan
                                Client ID va Client Secret kalitlarini kiriting.
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent class="space-y-6">
                            <!-- Authorize URL -->
                            <div class="space-y-3">
                                <Label for="url_authorize" class="flex gap-2 items-center">
                                    <LinkIcon class="h-4 w-4 text-muted-foreground" />
                                    Avtorizatsiya URL manzili (Authorize URL)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="url_authorize" 
                                        v-model="form.url_authorize" 
                                        type="url" 
                                        placeholder="Masalan: https://hemis.namdu.uz/oauth/authorize" 
                                        :class="{ 'border-destructive': form.errors.url_authorize }"
                                        required
                                    />
                                    <p v-if="form.errors.url_authorize" class="text-sm font-medium text-destructive">
                                        {{ form.errors.url_authorize }}
                                    </p>
                                </div>
                            </div>

                            <!-- Access Token URL -->
                            <div class="space-y-3">
                                <Label for="url_access_token" class="flex gap-2 items-center">
                                    <LinkIcon class="h-4 w-4 text-muted-foreground" />
                                    Kirish Kaliti URL manzili (Access Token URL)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="url_access_token" 
                                        v-model="form.url_access_token" 
                                        type="url" 
                                        placeholder="Masalan: https://hemis.namdu.uz/oauth/access-token" 
                                        :class="{ 'border-destructive': form.errors.url_access_token }"
                                        required
                                    />
                                    <p v-if="form.errors.url_access_token" class="text-sm font-medium text-destructive">
                                        {{ form.errors.url_access_token }}
                                    </p>
                                </div>
                            </div>

                            <!-- User Info URL -->
                            <div class="space-y-3">
                                <Label for="url_user_info" class="flex gap-2 items-center">
                                    <LinkIcon class="h-4 w-4 text-muted-foreground" />
                                    Foydalanuvchi ma'lumotlari (User Info URL)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="url_user_info" 
                                        v-model="form.url_user_info" 
                                        type="url" 
                                        placeholder="Masalan: https://hemis.namdu.uz/oauth/api/user" 
                                        :class="{ 'border-destructive': form.errors.url_user_info }"
                                        required
                                    />
                                    <p v-if="form.errors.url_user_info" class="text-sm font-medium text-destructive">
                                        {{ form.errors.url_user_info }}
                                    </p>
                                </div>
                            </div>

                            <!-- Client ID -->
                            <div class="space-y-3">
                                <Label for="client_id" class="flex gap-2 items-center">
                                    <UserCircle class="h-4 w-4 text-muted-foreground" />
                                    Client ID
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="client_id" 
                                        v-model="form.client_id" 
                                        type="text" 
                                        placeholder="123456" 
                                        :class="{ 'border-destructive': form.errors.client_id }"
                                        required
                                    />
                                    <p v-if="form.errors.client_id" class="text-sm font-medium text-destructive">
                                        {{ form.errors.client_id }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        Tizimga berilgan identifikator raqami.
                                    </p>
                                </div>
                            </div>

                            <!-- Client Secret -->
                            <div class="space-y-3 pb-2">
                                <Label for="client_secret" class="flex gap-2 items-center">
                                    <Key class="h-4 w-4 text-muted-foreground" />
                                    Client Secret
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <div class="relative">
                                        <Input 
                                            id="client_secret" 
                                            v-model="form.client_secret" 
                                            :type="showSecret ? 'text' : 'password'" 
                                            placeholder="••••••••••••••••" 
                                            :class="{ 'border-destructive': form.errors.client_secret }"
                                            required
                                            class="pr-10"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showSecret = !showSecret"
                                            class="absolute right-0 top-0 h-full px-3 py-2 text-muted-foreground hover:text-foreground focus:outline-none"
                                        >
                                            <Eye v-if="!showSecret" class="h-4 w-4" />
                                            <EyeOff v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p v-if="form.errors.client_secret" class="text-sm font-medium text-destructive">
                                        {{ form.errors.client_secret }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        Faqatgina serverga ma'lum bo'lishi kerak bo'lgan maxfiy kalit.
                                    </p>
                                </div>
                            </div>

                            <!-- Callback URL -->
                            <div class="space-y-3 pb-2">
                                <Label for="redirect_uri" class="flex gap-2 items-center">
                                    <LinkIcon class="h-4 w-4 text-muted-foreground" />
                                    Qayta ishlash manzili (Callback URI)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="redirect_uri" 
                                        v-model="form.redirect_uri" 
                                        type="url" 
                                        placeholder="Masalan: http://127.0.0.1/hemis/callback/employee" 
                                        :class="{ 'border-destructive': form.errors.redirect_uri }"
                                        required
                                    />
                                    <p v-if="form.errors.redirect_uri" class="text-sm font-medium text-destructive">
                                        {{ form.errors.redirect_uri }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        Ushbu URL manzilini HEMIS tizimida ilovani yaratayotganda <strong class="text-foreground">Redirect URI</strong> maydoniga kiritishingiz shart.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                        
                        <CardFooter class="flex justify-between border-t px-6 py-4 bg-muted/20">
                            <p class="text-sm text-muted-foreground hidden sm:block">
                                OAuth so'rovlari ushbu ma'lumotlar asosida amalga oshiriladi.
                            </p>
                            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                <Button type="submit" :disabled="form.processing">
                                    <Save class="mr-2 h-4 w-4" v-if="!form.processing" />
                                    <span v-if="form.processing" class="mr-2 h-4 w-4 rounded-full border-2 border-current border-t-transparent animate-spin"></span>
                                    {{ form.processing ? "Saqlanmoqda..." : "Saqlash" }}
                                </Button>
                            </div>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
