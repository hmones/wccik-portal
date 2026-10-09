<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { useTranslation } from '@/composables/useTranslation';

const { t, isUrdu } = useTranslation();

const props = defineProps<{
    backHref?: string;
    backLabel?: string;
    showSignOut?: boolean;
    beforeSignOut?: () => Promise<void>;
}>();

const signingOut = ref(false);

async function signOut() {
    if (signingOut.value) {
        return;
    }

    signingOut.value = true;
    await props.beforeSignOut?.();
    router.post(
        '/portal/sign-out',
        {},
        {
            onFinish: () => {
                signingOut.value = false;
            },
        },
    );
}

function switchLocale() {
    router.post(
        `/locale/${isUrdu() ? 'en' : 'ur'}`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <a href="#main" class="skip-link">{{
        t('skip_to_content') || 'Skip to main content'
    }}</a>

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="flex min-h-screen flex-col bg-background text-foreground"
    >
        <header
            class="border-b border-border/60 bg-background/90 backdrop-blur"
            role="banner"
        >
            <div
                class="mx-auto flex min-h-20 max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    :aria-label="t('site_name')"
                >
                    <img
                        src="/images/wccik-logo.png"
                        alt=""
                        class="h-12 w-auto object-contain sm:h-14"
                    />
                    <span
                        class="hidden max-w-[260px] text-sm leading-tight font-semibold text-foreground sm:block"
                    >
                        {{ t('site_name') }}
                    </span>
                </a>
                <nav
                    class="flex max-w-full flex-wrap items-center justify-end gap-2 sm:gap-3"
                    :aria-label="t('primary_navigation') || 'Primary'"
                >
                    <a
                        v-if="backHref"
                        :href="backHref"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        {{ backLabel || t('apply_back') }}
                    </a>
                    <button
                        v-if="showSignOut"
                        type="button"
                        :disabled="signingOut"
                        class="rounded-md px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted disabled:opacity-60"
                        @click="signOut"
                    >
                        {{ t('portal_sign_out') }}
                    </button>
                    <ThemeToggle />
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-brand-navy/20 bg-background px-4 py-2 text-xs font-semibold tracking-wider text-brand-navy uppercase transition-colors hover:bg-brand-navy hover:text-white dark:border-brand-teal/40 dark:text-brand-teal dark:hover:bg-brand-teal dark:hover:text-background"
                        @click="switchLocale"
                    >
                        {{ t('language_toggle') }}
                    </button>
                </nav>
            </div>
        </header>

        <main id="main" class="flex-1" role="main">
            <slot />
        </main>

        <footer
            class="mt-auto border-t border-border bg-background px-4 py-8 sm:px-6"
            role="contentinfo"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 text-sm text-muted-foreground sm:flex-row"
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
