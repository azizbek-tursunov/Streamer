<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps<{
    status: number
}>()

const content = computed(() => {
    const pages: Record<number, { title: string; message: string }> = {
        403: {
            title: "Ruxsat yo'q",
            message: 'Bu sahifani ochish mumkin emas.',
        },
        404: {
            title: 'Topilmadi',
            message: 'Sahifa mavjud emas.',
        },
        429: {
            title: 'Biroz kuting',
            message: "Juda kop sorov yuborildi.",
        },
        500: {
            title: 'Server xatosi',
            message: 'Kutilmagan xatolik yuz berdi.',
        },
        503: {
            title: 'Xizmat mavjud emas',
            message: 'Hozircha sahifani ochib bolmaydi.',
        },
    }

    return (
        pages[props.status] ?? {
            title: 'Xatolik',
            message: 'Sahifani ochib bolmadi.',
        }
    )
})
</script>

<template>
    <Head :title="`${props.status}`" />

    <div class="shell">
        <main class="panel">
            <div class="code">{{ props.status }}</div>
            <h1>{{ content.title }}</h1>
            <p>{{ content.message }}</p>

            <div class="actions">
                <a href="/" class="button button-primary">Bosh sahifa</a>
                <button type="button" class="button button-secondary" @click="window.history.back()">
                    Orqaga
                </button>
            </div>
        </main>
    </div>
</template>

<style scoped>
.shell {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: linear-gradient(180deg, #f7faf9 0%, #edf4f2 100%);
}

.panel {
    width: min(100%, 420px);
    padding: 40px 32px;
    border: 1px solid #d9e5e1;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    text-align: center;
}

.code {
    font-size: clamp(3rem, 10vw, 5rem);
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.08em;
    color: #0f766e;
}

h1 {
    margin: 16px 0 8px;
    font-size: 1.75rem;
    line-height: 1.1;
    color: #0f172a;
}

p {
    margin: 0;
    color: #52606d;
    font-size: 0.98rem;
    line-height: 1.6;
}

.actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 24px;
}

.button {
    appearance: none;
    border-radius: 999px;
    padding: 12px 18px;
    border: 1px solid transparent;
    font: inherit;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease;
}

.button:hover {
    transform: translateY(-1px);
}

.button-primary {
    background: #0f766e;
    color: #fff;
}

.button-secondary {
    background: #fff;
    color: #0f172a;
    border-color: #d9e5e1;
}

@media (max-width: 480px) {
    .panel {
        padding: 32px 20px;
    }

    .actions {
        flex-direction: column;
    }
}
</style>
