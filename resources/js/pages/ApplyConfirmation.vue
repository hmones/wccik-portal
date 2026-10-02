<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    token: string;
    name: string;
}>();

const { t, isUrdu } = useTranslation();

const statusUrl = computed(
    () => `${window.location.origin}/status/${props.token}`,
);

const copied = ref(false);

async function copyLink() {
    try {
        await navigator.clipboard.writeText(statusUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* ignore */
    }
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
    <Head :title="t('confirmation_title')" />

    <a href="#main" class="skip-link">{{
        t('skip_to_content') || 'Skip to main content'
    }}</a>

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="flex min-h-screen flex-col bg-background text-foreground"
    >
        <!-- Header -->
        <header
            class="border-b border-border/60 bg-background/90 backdrop-blur"
            role="banner"
        >
            <div
                class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6"
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
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-brand-navy/20 bg-background px-4 py-2 text-xs font-semibold tracking-wider text-brand-navy uppercase transition-colors hover:bg-brand-navy hover:text-white dark:border-brand-teal/40 dark:text-brand-teal dark:hover:bg-brand-teal dark:hover:text-background"
                    :aria-label="
                        t('language_toggle_aria') || t('language_toggle')
                    "
                    @click="switchLocale"
                >
                    {{ t('language_toggle') }}
                </button>
            </div>
        </header>

        <main id="main" class="flex-1 px-4 py-16 sm:px-6 sm:py-20" role="main">
            <div class="mx-auto max-w-2xl">
                <!-- Success badge -->
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand-teal/30 bg-brand-teal/10 px-3 py-1 text-xs font-semibold tracking-wider text-brand-teal-deep uppercase dark:text-brand-teal"
                >
                    <svg
                        aria-hidden="true"
                        class="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    {{ t('confirmation_badge') || 'Submitted' }}
                </div>

                <h1
                    class="mb-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('confirmation_heading') }}
                </h1>

                <p
                    class="mb-10 text-base leading-relaxed text-muted-foreground"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('confirmation_body') }}
                </p>

                <div
                    class="mb-10 rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('confirmation_status_label') }}
                    </p>
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <code
                            class="min-w-0 flex-1 overflow-x-auto rounded-md bg-muted px-3 py-2 font-mono text-sm text-foreground"
                            dir="ltr"
                        >
                            {{ statusUrl }}
                        </code>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-xs font-semibold tracking-wider text-foreground uppercase transition-colors hover:bg-muted"
                            :aria-label="t('copy_link') || 'Copy link'"
                            @click="copyLink"
                        >
                            <svg
                                v-if="!copied"
                                aria-hidden="true"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="9"
                                    y="9"
                                    width="13"
                                    height="13"
                                    rx="2"
                                />
                                <path
                                    d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"
                                />
                            </svg>
                            <svg
                                v-else
                                aria-hidden="true"
                                class="h-4 w-4 text-brand-teal-deep dark:text-brand-teal"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            <span aria-live="polite">{{
                                copied
                                    ? t('copied') || 'Copied'
                                    : t('copy') || 'Copy'
                            }}</span>
                        </button>
                    </div>
                    <p
                        class="mt-3 text-xs text-muted-foreground"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('confirmation_save_link') }}
                    </p>
                </div>

                <a
                    href="/"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                >
                    <svg
                        aria-hidden="true"
                        class="h-4 w-4"
                        :class="isUrdu() ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                    {{ t('confirmation_home') }}
                </a>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-border bg-background px-4 py-8 sm:px-6"
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
