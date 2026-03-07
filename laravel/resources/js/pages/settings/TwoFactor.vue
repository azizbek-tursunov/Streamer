<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { disable, enable, show } from '@/routes/two-factor';
import { BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

interface Props {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ikki bosqichli tasdiqlash',
        href: show.url(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ikki bosqichli tasdiqlash" />
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Ikki bosqichli tasdiqlash"
                    description="Ikki bosqichli tasdiqlash sozlamalarini boshqaring"
                />

                <div
                    v-if="!twoFactorEnabled"
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="destructive">O'chirilgan</Badge>

                    <p class="text-muted-foreground">
                        Ikki bosqichli tasdiqlashni yoqsangiz, tizimga kirishda
                        xavfsiz PIN kod so'raladi. Bu kodni telefoningizdagi
                        TOTP ilovasidan olishingiz mumkin.
                    </p>

                    <div>
                        <Button
                            v-if="hasSetupData"
                            @click="showSetupModal = true"
                        >
                            <ShieldCheck />Sozlashni davom ettirish
                        </Button>
                        <Form
                            v-else
                            v-bind="enable.form()"
                            @success="showSetupModal = true"
                            #default="{ processing }"
                        >
                            <Button type="submit" :disabled="processing">
                                <ShieldCheck />2FA ni yoqish</Button
                            ></Form
                        >
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="default">Yoqilgan</Badge>

                    <p class="text-muted-foreground">
                        Ikki bosqichli tasdiqlash yoqilgan. Tizimga kirishda
                        xavfsiz PIN kod so'raladi, uni telefoningizdagi TOTP
                        ilovasidan olishingiz mumkin.
                    </p>

                    <TwoFactorRecoveryCodes />

                    <div class="relative inline">
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <Button
                                variant="destructive"
                                type="submit"
                                :disabled="processing"
                            >
                                <ShieldBan />
                                2FA ni o'chirish
                            </Button>
                        </Form>
                    </div>
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
