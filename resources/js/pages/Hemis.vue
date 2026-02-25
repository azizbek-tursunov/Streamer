<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Save, Link as LinkIcon, Key, ExternalLink, Activity, CheckCircle, AlertCircle, Eye, EyeOff } from 'lucide-vue-next';

const page = usePage();

const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
const errorMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.error);

const props = defineProps<{
    baseUrl: string;
    token: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sozlamalar', href: '/hemis' },
    { title: 'HEMIS API', href: '/hemis' },
];

const form = useForm({
    base_url: props.baseUrl,
    token: props.token,
});

const updateSettings = () => {
    form.put('/hemis', {
        preserveScroll: true,
    });
};

const showToken = ref(false);

const isTesting = ref(false);

const testConnection = () => {
    isTesting.value = true;
    router.post('/hemis/test', {
        base_url: form.base_url,
        token: form.token,
    }, {
        preserveScroll: true,
        onFinish: () => { isTesting.value = false; }
    });
};
</script>

<template>
    <Head title="HEMIS Sozlamalari" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-4 p-8 pt-6 max-w-5xl mx-auto w-full">
            <div class="flex items-center justify-between space-y-2">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">HEMIS Integratsiyasi</h2>
                    <p class="text-muted-foreground mt-1">
                        Oliy ta'lim jarayonlarini boshqarish axborot tizimi API sozlamalari.
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
                            <CardTitle>API Ulanish Sozlamalari</CardTitle>
                            <CardDescription>
                                HEMIS axborot tizimi bilan sinxronizatsiya qilish uchun ma'lumotlarni kiriting. 
                                Ushbu ma'lumotlar xavfsiz saqlanadi va bevosita HEMIS serveriga yuboriladi.
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent class="space-y-6">
                            <!-- Base URL -->
                            <div class="space-y-3">
                                <Label for="base_url" class="flex gap-2 items-center">
                                    <LinkIcon class="h-4 w-4 text-muted-foreground" />
                                    Asosiy URL manzili (Base URL)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <Input 
                                        id="base_url" 
                                        v-model="form.base_url" 
                                        type="url" 
                                        placeholder="Masalan: https://student.hemis.uz/rest/v1" 
                                        :class="{ 'border-destructive': form.errors.base_url }"
                                        required
                                    />
                                    <p v-if="form.errors.base_url" class="text-sm font-medium text-destructive">
                                        {{ form.errors.base_url }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        HEMIS tizimining tashqi API uchun ajratilgan manzili (odatda `/rest/v1` bilan tugaydi).
                                    </p>
                                </div>
                            </div>

                            <!-- Token -->
                            <div class="space-y-3 pb-2">
                                <Label for="token" class="flex gap-2 items-center">
                                    <Key class="h-4 w-4 text-muted-foreground" />
                                    API Xavfsizlik Kaliti (Token)
                                </Label>
                                <div class="flex flex-col gap-1.5">
                                    <div class="relative">
                                        <Input 
                                            id="token" 
                                            v-model="form.token" 
                                            :type="showToken ? 'text' : 'password'" 
                                            placeholder="••••••••••••••••" 
                                            :class="{ 'border-destructive': form.errors.token }"
                                            required
                                            class="pr-10"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showToken = !showToken"
                                            class="absolute right-0 top-0 h-full px-3 py-2 text-muted-foreground hover:text-foreground focus:outline-none"
                                        >
                                            <Eye v-if="!showToken" class="h-4 w-4" />
                                            <EyeOff v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p v-if="form.errors.token" class="text-sm font-medium text-destructive">
                                        {{ form.errors.token }}
                                    </p>
                                    <p v-else class="text-sm text-muted-foreground">
                                        HEMIS administratoringiz tomonidan taqdim etilgan ruxsat kaliti (Bearer token).
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-md bg-blue-50 dark:bg-blue-950/40 p-4 border border-blue-200 dark:border-blue-900 mt-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <ExternalLink class="h-5 w-5 text-blue-400" />
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                            Eslatma
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                            <p>
                                                O'zgartirishlarni saqlaganingizdan so'ng, "Fakultetlar" bo'limidan yoki "Auditoriyalar" bo'limidan ma'lumotlarni qayta sinxronizatsiya qilishingiz tavsiya etiladi. Noto'g'ri kalit terilsa, server Hemis bilan bog'lana olmaydi.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        
                        <CardFooter class="flex justify-between border-t px-6 py-4 bg-muted/20">
                            <p class="text-sm text-muted-foreground hidden sm:block">
                                Barcha so'rovlar <span class="font-bold font-mono text-xs">{{ form.base_url || 'Noma\'lum URL' }}</span> ga yo'naltiriladi.
                            </p>
                            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                <Button type="button" variant="outline" @click="testConnection" :disabled="isTesting || form.processing">
                                    <Activity class="mr-2 h-4 w-4" v-if="!isTesting" />
                                    <span v-if="isTesting" class="mr-2 h-4 w-4 rounded-full border-2 border-current border-t-transparent animate-spin"></span>
                                    {{ isTesting ? "Tekshirilmoqda..." : "Ulanishni tekshirish" }}
                                </Button>
                                <Button type="submit" :disabled="form.processing || isTesting">
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
