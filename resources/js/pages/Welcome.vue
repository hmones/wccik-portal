<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';

const { t, isUrdu } = useTranslation();
const page = usePage();
const appUrl = ((page.props as Record<string, unknown>).appUrl as string) ?? '';

const metaDescription =
    'Apply for new membership or renew your existing membership at the Women Chamber of Commerce & Industry, Karachi (WCCIK). Submit your application online and track your status.';

function switchLocale() {
    router.post(
        `/locale/${isUrdu() ? 'en' : 'ur'}`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('site_name')">
        <link rel="canonical" :href="appUrl + '/'" />
        <link rel="alternate" hreflang="en" :href="appUrl + '/'" />
        <link rel="alternate" hreflang="ur" :href="appUrl + '/'" />
        <link rel="alternate" hreflang="x-default" :href="appUrl + '/'" />
        <meta name="description" :content="metaDescription" />
        <meta
            name="keywords"
            content="WCCIK, Women Chamber of Commerce, Karachi, membership, business, Pakistan, WCCI"
        />
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="t('site_name')" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:url" :content="appUrl + '/'" />
        <meta property="og:image" :content="appUrl + '/favicon-512.png'" />
        <meta property="og:image:width" content="512" />
        <meta property="og:image:height" content="512" />
        <meta property="og:image:alt" content="WCCIK Logo" />
        <meta name="twitter:title" :content="t('site_name')" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:image" :content="appUrl + '/favicon-512.png'" />
        <meta name="twitter:image:alt" content="WCCIK Logo" />
    </Head>

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
            class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/70"
            role="banner"
        >
            <div
                class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6"
            >
                <a
                    href="/"
                    class="flex items-center gap-3 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4"
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
                    class="flex items-center gap-2 sm:gap-3"
                    :aria-label="t('primary_navigation') || 'Primary'"
                >
                    <a
                        href="/login"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        {{ t('admin_login') }}
                    </a>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-brand-navy/20 bg-background px-4 py-2 text-xs font-semibold tracking-wider text-brand-navy uppercase transition-colors hover:bg-brand-navy hover:text-white dark:border-brand-teal/40 dark:text-brand-teal dark:hover:bg-brand-teal dark:hover:text-background"
                        :aria-label="
                            t('language_toggle_aria') || t('language_toggle')
                        "
                        @click="switchLocale"
                    >
                        <svg
                            aria-hidden="true"
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <path
                                d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"
                            />
                        </svg>
                        {{ t('language_toggle') }}
                    </button>
                </nav>
            </div>
        </header>

        <main id="main" class="flex-1" role="main">
            <!-- Hero -->
            <section
                class="relative overflow-hidden bg-brand-navy-deep text-white"
                aria-labelledby="hero-title"
            >
                <!-- Decorative glow accents using the electric green sparingly -->
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0"
                >
                    <div
                        class="absolute -top-24 right-[-10%] h-80 w-80 rounded-full bg-brand-electric/10 blur-3xl"
                    ></div>
                    <div
                        class="absolute bottom-[-20%] left-[-5%] h-96 w-96 rounded-full bg-brand-teal/20 blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:py-28"
                >
                    <h1
                        id="hero-title"
                        class="max-w-3xl text-4xl leading-[1.1] font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('hero_title') }}
                    </h1>
                    <p
                        class="mt-6 max-w-2xl text-lg leading-relaxed text-white/80 sm:text-xl"
                        :class="isUrdu() ? 'mr-0 ml-auto text-right' : ''"
                    >
                        {{ t('hero_subtitle') }}
                    </p>
                </div>
            </section>

            <!-- Action Cards -->
            <section
                class="bg-background px-4 py-16 sm:px-6 sm:py-20"
                aria-labelledby="actions-title"
            >
                <div class="mx-auto max-w-7xl">
                    <h2 id="actions-title" class="sr-only">
                        {{ t('actions_heading') || 'Membership actions' }}
                    </h2>
                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- New Membership -->
                        <article
                            class="group relative flex flex-col rounded-2xl border border-border bg-card p-8 shadow-sm transition-all duration-200 focus-within:border-brand-navy focus-within:shadow-xl hover:-translate-y-1 hover:border-brand-navy/40 hover:shadow-xl"
                        >
                            <div
                                class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-navy/10 text-brand-navy dark:bg-brand-teal/15 dark:text-brand-teal"
                                aria-hidden="true"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M19 8v6M22 11h-6" />
                                </svg>
                            </div>
                            <h3
                                class="text-xl font-semibold text-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('new_membership') }}
                            </h3>
                            <p
                                class="mt-3 flex-1 text-sm leading-relaxed text-muted-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('new_membership_desc') }}
                            </p>
                            <a
                                href="/apply"
                                class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep focus-visible:outline-offset-4 dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                            >
                                {{ t('new_membership_cta') }}
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                    :class="isUrdu() ? 'rotate-180' : ''"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </a>
                        </article>

                        <!-- Renewal -->
                        <article
                            class="group relative flex flex-col rounded-2xl border border-border bg-card p-8 shadow-sm transition-all duration-200 focus-within:border-brand-teal focus-within:shadow-xl hover:-translate-y-1 hover:border-brand-teal/40 hover:shadow-xl"
                        >
                            <div
                                class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-teal/10 text-brand-teal-deep dark:bg-brand-teal/20 dark:text-brand-teal"
                                aria-hidden="true"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M21 12a9 9 0 1 1-3-6.7L21 8" />
                                    <path d="M21 3v5h-5" />
                                </svg>
                            </div>
                            <h3
                                class="text-xl font-semibold text-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renewal') }}
                            </h3>
                            <p
                                class="mt-3 flex-1 text-sm leading-relaxed text-muted-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renewal_desc') }}
                            </p>
                            <a
                                href="/renew"
                                class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-teal-deep px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-teal focus-visible:outline-offset-4"
                            >
                                {{ t('renewal_cta') }}
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                    :class="isUrdu() ? 'rotate-180' : ''"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </a>
                        </article>

                        <!-- Check Status -->
                        <article
                            class="group relative flex flex-col rounded-2xl border border-border bg-card p-8 shadow-sm transition-all duration-200 focus-within:border-foreground focus-within:shadow-xl hover:-translate-y-1 hover:border-foreground/40 hover:shadow-xl"
                        >
                            <div
                                class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-muted text-foreground"
                                aria-hidden="true"
                            >
                                <svg
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.3-4.3" />
                                </svg>
                            </div>
                            <h3
                                class="text-xl font-semibold text-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('check_status') }}
                            </h3>
                            <p
                                class="mt-3 flex-1 text-sm leading-relaxed text-muted-foreground"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('check_status_desc') }}
                            </p>
                            <a
                                href="/status"
                                class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-6 py-3 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted focus-visible:outline-offset-4"
                            >
                                {{ t('check_status_cta') }}
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                    :class="isUrdu() ? 'rotate-180' : ''"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </a>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Info Strip -->
            <section
                class="bg-brand-navy-deep px-4 py-14 text-white sm:px-6"
                aria-labelledby="info-title"
            >
                <div
                    class="mx-auto flex max-w-7xl flex-col items-start gap-8 md:flex-row md:items-center"
                >
                    <h2
                        id="info-title"
                        class="shrink-0 text-xl leading-snug font-semibold md:w-64"
                        :class="isUrdu() ? 'text-right md:text-right' : ''"
                    >
                        {{ t('info_title') }}
                    </h2>
                    <div
                        class="hidden w-px self-stretch bg-white/15 md:block"
                        aria-hidden="true"
                    ></div>
                    <p
                        class="text-base leading-relaxed text-white/80"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('info_body') }}
                    </p>
                </div>
            </section>
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
