<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    token: string;
    name: string;
}>();

const { t, isUrdu } = useTranslation();

const statusUrl = computed(
    () => `${window.location.origin}/status/${props.token}`,
);

function switchLocale() {
    router.post(
        `/locale/${isUrdu() ? 'en' : 'ur'}`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('confirmation_title')" />

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="flex min-h-screen flex-col bg-white"
    >
        <!-- Header -->
        <header class="bg-white">
            <div
                class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-4">
                    <img
                        src="/images/wccik-logo.png"
                        alt="WCCIK Logo"
                        class="h-14 w-auto object-contain"
                    />
                    <span
                        class="hidden max-w-[240px] text-sm leading-tight font-semibold text-black sm:block"
                    >
                        {{ t('site_name') }}
                    </span>
                </div>
                <button
                    type="button"
                    class="rounded border px-5 py-2 text-xs font-semibold tracking-widest uppercase transition-colors duration-150 hover:bg-gray-50 focus:outline-none"
                    style="
                        border-color: hsl(164, 100%, 29%);
                        color: hsl(164, 100%, 29%);
                    "
                    @click="switchLocale"
                >
                    {{ t('language_toggle') }}
                </button>
            </div>
        </header>

        <!-- Content -->
        <section class="flex-1 px-6 py-20">
            <div class="mx-auto max-w-2xl">
                <div
                    class="mb-8 h-1 w-12"
                    style="background-color: hsl(164, 100%, 29%)"
                ></div>

                <h1
                    class="mb-4 text-3xl font-bold text-black"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('confirmation_heading') }}
                </h1>

                <p
                    class="mb-10 text-sm leading-relaxed text-gray-600"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('confirmation_body') }}
                </p>

                <div class="mb-8 border border-gray-200 p-6">
                    <p
                        class="mb-3 text-xs font-semibold tracking-widest text-gray-500 uppercase"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('confirmation_status_label') }}
                    </p>
                    <p
                        class="mb-3 font-mono text-sm break-all text-black"
                        dir="ltr"
                    >
                        {{ statusUrl }}
                    </p>
                    <p
                        class="text-xs text-gray-500"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('confirmation_save_link') }}
                    </p>
                </div>

                <a
                    href="/"
                    class="inline-block rounded px-8 py-3 text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all duration-150 hover:shadow-md focus:outline-none"
                    style="background-color: hsl(164, 100%, 29%)"
                >
                    {{ t('confirmation_home') }}
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white px-6 py-8">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 text-sm text-black sm:flex-row"
            >
                <span>{{ t('footer_org') }}</span>
                <span
                    >&copy; {{ new Date().getFullYear() }}
                    {{ t('footer_rights') }}</span
                >
            </div>
        </footer>
    </div>
</template>
