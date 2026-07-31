<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';

const { t, isUrdu } = useTranslation();

function switchLocale() {
    router.post(
        `/locale/${isUrdu() ? 'en' : 'ur'}`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('site_name')" />

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="min-h-screen flex flex-col bg-white text-foreground"
    >
        <!-- Header -->
        <header class="bg-white border-b border-border">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <img
                        src="/images/wccik-logo.png"
                        alt="WCCIK Logo"
                        class="h-14 w-auto object-contain"
                    />
                    <span class="hidden sm:block text-sm font-semibold text-black leading-tight max-w-[240px]">
                        {{ t('site_name') }}
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <a
                        href="/login"
                        class="text-sm text-gray-500 hover:text-black transition-colors"
                    >
                        {{ t('admin_login') }}
                    </a>

                    <button
                        type="button"
                        class="border border-primary text-primary px-4 py-1.5 text-sm font-medium hover:bg-primary hover:text-white transition-colors"
                        @click="switchLocale"
                    >
                        {{ t('language_toggle') }}
                    </button>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-primary text-white py-20 px-6">
            <div class="max-w-7xl mx-auto">
                <p
                    class="text-sm font-semibold tracking-widest uppercase opacity-80 mb-3"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('hero_org') }}
                </p>
                <h1
                    class="text-4xl md:text-5xl font-bold mb-6 leading-tight"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('hero_title') }}
                </h1>
                <p
                    class="text-lg opacity-85 max-w-2xl leading-relaxed"
                    :class="isUrdu() ? 'text-right mr-0 ml-auto' : ''"
                >
                    {{ t('hero_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Action Cards -->
        <section class="py-16 px-6 bg-white">
            <div class="max-w-7xl mx-auto grid md:grid-cols-3 gap-0 border border-border">
                <!-- New Membership -->
                <div class="border-b md:border-b-0 md:border-r border-border p-10 flex flex-col">
                    <div
                        class="w-10 h-1 bg-primary mb-8"
                        :class="isUrdu() ? 'mr-auto ml-0' : ''"
                    ></div>
                    <h2
                        class="text-xl font-bold text-black mb-4"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('new_membership') }}
                    </h2>
                    <p
                        class="text-gray-600 text-sm leading-relaxed mb-8 flex-1"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('new_membership_desc') }}
                    </p>
                    <a
                        href="/apply"
                        class="inline-block bg-primary text-white text-sm font-semibold px-6 py-3 hover:bg-primary/90 transition-colors text-center"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('new_membership_cta') }}
                    </a>
                </div>

                <!-- Renewal -->
                <div class="border-b md:border-b-0 md:border-r border-border p-10 flex flex-col">
                    <div
                        class="w-10 h-1 mb-8"
                        :class="isUrdu() ? 'mr-auto ml-0 bg-[hsl(219,68%,20%)]' : 'bg-[hsl(219,68%,20%)]'"
                    ></div>
                    <h2
                        class="text-xl font-bold text-black mb-4"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('renewal') }}
                    </h2>
                    <p
                        class="text-gray-600 text-sm leading-relaxed mb-8 flex-1"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('renewal_desc') }}
                    </p>
                    <a
                        href="/renew"
                        class="inline-block text-sm font-semibold px-6 py-3 hover:opacity-90 transition-colors text-center text-white"
                        style="background-color: hsl(219, 68%, 20%)"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('renewal_cta') }}
                    </a>
                </div>

                <!-- Check Status -->
                <div class="p-10 flex flex-col">
                    <div
                        class="w-10 h-1 bg-gray-400 mb-8"
                        :class="isUrdu() ? 'mr-auto ml-0' : ''"
                    ></div>
                    <h2
                        class="text-xl font-bold text-black mb-4"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('check_status') }}
                    </h2>
                    <p
                        class="text-gray-600 text-sm leading-relaxed mb-8 flex-1"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('check_status_desc') }}
                    </p>
                    <a
                        href="/status"
                        class="inline-block border border-gray-400 text-black text-sm font-semibold px-6 py-3 hover:bg-gray-100 transition-colors text-center"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('check_status_cta') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Info Strip -->
        <section
            class="py-14 px-6"
            style="background-color: hsl(219, 68%, 20%)"
        >
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row gap-8 items-start">
                <h3
                    class="text-white font-bold text-lg md:w-56 shrink-0 leading-snug"
                    :class="isUrdu() ? 'text-right md:text-right' : ''"
                >
                    {{ t('info_title') }}
                </h3>
                <div class="w-px bg-white/20 self-stretch hidden md:block"></div>
                <p
                    class="text-white/80 text-sm leading-relaxed"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('info_body') }}
                </p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-foreground text-white py-8 px-6 mt-auto">
            <div
                class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-3 text-sm text-white/60"
            >
                <span>{{ t('footer_org') }}</span>
                <span>&copy; {{ new Date().getFullYear() }} {{ t('footer_rights') }}</span>
            </div>
        </footer>
    </div>
</template>
