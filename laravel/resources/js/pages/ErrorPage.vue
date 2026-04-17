<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps<{
    status: number
}>()

const content = computed(() => {
    const pages: Record<number, { short: string; title: string; message: string; hint: string }> = {
        403: {
            short: "Ruxsat yo'q",
            title: 'Bu sahifaga kirish uchun ruxsat yetarli emas.',
            message: "Siz soragan bolim mavjud bolishi mumkin, lekin joriy hisobingiz bilan unga kirish mumkin emas.",
            hint: "Hisobingizdagi rol va ruxsatlarni tekshiring. Agar bu sahifa sizga ochiq bolishi kerak bolsa, administrator bilan boglaning.",
        },
        404: {
            short: 'Topilmadi',
            title: 'Sorilgan sahifa topilmadi.',
            message: 'Havola eskirgan, notogri yozilgan yoki sahifa tizimdan olib tashlangan bolishi mumkin.',
            hint: 'Manzilni qayta tekshiring yoki asosiy bolimlardan kerakli sahifaga qayta oting.',
        },
        429: {
            short: 'Juda kop sorov',
            title: 'Sorovlar soni vaqtincha cheklab qoyildi.',
            message: 'Tizimni himoya qilish uchun qisqa vaqt ichida yuborilgan juda kop sorovlar vaqtincha toxtatildi.',
            hint: 'Bir oz kutib, keyin yana urinib koring. Muammo odatiy foydalanishda qayta chiqsa, limitlarni tekshirish kerak.',
        },
        500: {
            short: 'Ichki xatolik',
            title: 'Ichki server xatoligi yuz berdi.',
            message: 'Sorov qabul qilindi, lekin tizim uni toliq yakunlay olmadi. Bu odatda backend xatosi yoki vaqtinchalik xizmat muammosi bilan bogliq boladi.',
            hint: 'Sahifani qayta yuklab koring. Muammo saqlansa, loglar va server xizmatlarini tekshirish kerak.',
        },
        503: {
            short: 'Xizmat vaqtincha band',
            title: 'Tizim hozircha vaqtincha mavjud emas.',
            message: 'Xizmat texnik ishlar, qayta ishga tushirish yoki vaqtinchalik yuklama sababli hozircha javob bera olmayapti.',
            hint: 'Bir necha daqiqadan keyin qayta urinib koring. Uzilish uzoq davom etsa, xizmatlar holatini tekshirish kerak.',
        },
    }

    return (
        pages[props.status] ?? {
            short: 'Xatolik',
            title: 'Kutilmagan holat yuz berdi.',
            message: 'Sahifani ochishda kutilmagan xatolik qaytdi.',
            hint: 'Sahifani yangilab koring yoki bosh sahifaga qayting.',
        }
    )
})
</script>

<template>
    <Head :title="`${props.status} | UniVision`" />

    <div class="error-shell">
        <main class="error-panel">
            <section class="error-main">
                <div class="brand">
                    <img src="/images/univision_logo_transparent.png" alt="UniVision" class="brand-logo" />
                    <div>
                        <div class="brand-title">UniVision</div>
                        <div class="brand-subtitle">Namangan davlat universiteti monitoring tizimi</div>
                    </div>
                </div>

                <div class="status-badge">
                    <span class="status-dot" />
                    Tizim holati
                </div>

                <div class="status-code">{{ props.status }}</div>
                <h1>{{ content.title }}</h1>
                <p class="message">{{ content.message }}</p>

                <div class="actions">
                    <a href="/" class="button button-primary">Bosh sahifaga qaytish</a>
                    <button type="button" class="button button-secondary" @click="window.history.back()">
                        Oldingi sahifa
                    </button>
                </div>

                <div class="footer">Agar muammo davom etsa, tizim administratori bilan boglaning.</div>
            </section>

            <aside class="sidebar">
                <div class="card">
                    <div class="card-label">Holat tafsiloti</div>
                    <div class="card-value"><strong>{{ props.status }}</strong> - {{ content.short }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Tavsiya</div>
                    <div class="hint">{{ content.hint }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Manzil</div>
                    <div class="hint">{{ window.location.pathname }}</div>
                </div>
            </aside>
        </main>
    </div>
</template>

<style scoped>
.error-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background:
        radial-gradient(circle at top left, rgba(20, 184, 166, 0.18), transparent 28%),
        radial-gradient(circle at bottom right, rgba(14, 116, 144, 0.18), transparent 30%),
        linear-gradient(135deg, #f3f8fa, #dbf0ef);
    padding: 24px;
    display: grid;
    place-items: center;
}

.error-shell::before,
.error-shell::after {
    content: '';
    position: fixed;
    pointer-events: none;
}

.error-shell::before {
    inset: auto;
    width: 34rem;
    height: 34rem;
    right: -8rem;
    top: -8rem;
    background: radial-gradient(circle, rgba(84, 211, 194, 0.24), transparent 68%);
    filter: blur(10px);
}

.error-shell::after {
    inset: 0;
    background-image:
        linear-gradient(rgba(15, 118, 110, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15, 118, 110, 0.06) 1px, transparent 1px);
    background-size: 36px 36px;
    mask-image: radial-gradient(circle at center, black 38%, transparent 82%);
}

.error-panel {
    position: relative;
    z-index: 1;
    width: min(1080px, 100%);
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(260px, 0.85fr);
    gap: 2rem;
    padding: 2rem;
    border-radius: 28px;
    border: 1px solid rgba(19, 78, 74, 0.14);
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(20px);
}

.error-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(84, 211, 194, 0.08), transparent 34%, rgba(14, 116, 144, 0.08));
    pointer-events: none;
}

.error-main,
.sidebar {
    position: relative;
    z-index: 1;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.4rem;
}

.brand-logo {
    width: 2.75rem;
    height: 2.75rem;
    object-fit: contain;
}

.brand-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}

.brand-subtitle {
    font-size: 0.82rem;
    color: #486270;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.55rem 0.8rem;
    border-radius: 999px;
    background: #d8f3f0;
    color: #115e59;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.status-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    background: currentColor;
    box-shadow: 0 0 0 0 rgba(84, 211, 194, 0.6);
    animation: pulse 2.2s infinite;
}

.status-code {
    margin: 1rem 0 0;
    font-size: clamp(4.25rem, 11vw, 8rem);
    font-weight: 800;
    line-height: 0.9;
    letter-spacing: -0.08em;
    color: #115e59;
    text-shadow: 0 10px 30px rgba(15, 118, 110, 0.18);
}

h1 {
    margin: 1rem 0 0.75rem;
    font-size: clamp(1.8rem, 3vw, 3rem);
    line-height: 1.02;
    letter-spacing: -0.04em;
    color: #0f172a;
}

.message,
.hint,
.footer,
.card-label {
    color: #486270;
}

.message {
    margin: 0;
    max-width: 38rem;
    font-size: 1rem;
    line-height: 1.7;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
    margin-top: 2rem;
}

.button {
    appearance: none;
    border: 1px solid transparent;
    border-radius: 999px;
    padding: 0.95rem 1.25rem;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease, color 180ms ease;
}

.button:hover {
    transform: translateY(-1px);
}

.button-primary {
    background: #0f766e;
    color: #fff;
}

.button-secondary {
    border-color: rgba(19, 78, 74, 0.14);
    background: transparent;
    color: #0f172a;
}

.sidebar {
    display: grid;
    gap: 1rem;
    align-content: start;
}

.card {
    padding: 1.2rem 1.15rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.36);
    border: 1px solid rgba(19, 78, 74, 0.14);
}

.card-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.55rem;
}

.card-value {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.45;
    color: #0f172a;
}

.card-value strong {
    color: #115e59;
}

.hint {
    font-size: 0.94rem;
    line-height: 1.6;
}

.footer {
    margin-top: 1.1rem;
    font-size: 0.85rem;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(84, 211, 194, 0.55); }
    70% { box-shadow: 0 0 0 12px rgba(84, 211, 194, 0); }
    100% { box-shadow: 0 0 0 0 rgba(84, 211, 194, 0); }
}

@media (max-width: 900px) {
    .error-panel {
        grid-template-columns: 1fr;
    }

    .error-shell {
        padding: 1rem;
    }
}
</style>
