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

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="flex min-h-screen flex-col bg-white text-foreground"
    >
        <!-- Header -->
        <header class="bg-white shadow-sm">
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

                <div class="flex items-center gap-4">
                    <a
                        href="/login"
                        class="text-sm text-gray-500 transition-colors hover:text-black"
                    >
                        {{ t('admin_login') }}
                    </a>

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
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-primary px-6 py-20 text-white">
            <div class="mx-auto max-w-7xl">
                <p
                    class="mb-3 text-sm font-semibold tracking-widest uppercase opacity-80"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('hero_org') }}
                </p>
                <h1
                    class="mb-6 text-4xl leading-tight font-bold md:text-5xl"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('hero_title') }}
                </h1>
                <p
                    class="max-w-2xl text-lg leading-relaxed opacity-85"
                    :class="isUrdu() ? 'mr-0 ml-auto text-right' : ''"
                >
                    {{ t('hero_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Action Cards -->
        <section class="flex-1 bg-white px-6 py-16">
            <div
                class="mx-auto grid max-w-7xl gap-0 border border-border md:grid-cols-3"
            >
                <!-- New Membership -->
                <div
                    class="flex flex-col border-b border-border p-10 md:border-r md:border-b-0"
                >
                    <div
                        class="mb-8 h-1 w-10 bg-primary"
                        :class="isUrdu() ? 'mr-auto ml-0' : ''"
                    ></div>
                    <h2
                        class="mb-4 text-xl font-bold text-black"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('new_membership') }}
                    </h2>
                    <p
                        class="mb-8 flex-1 text-sm leading-relaxed text-gray-600"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('new_membership_desc') }}
                    </p>
                    <a
                        href="/apply"
                        class="inline-block rounded px-8 py-3 text-center text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all duration-150 hover:shadow-md"
                        style="background-color: hsl(164, 100%, 29%)"
                    >
                        {{ t('new_membership_cta') }}
                    </a>
                </div>

                <!-- Renewal -->
                <div
                    class="flex flex-col border-b border-border p-10 md:border-r md:border-b-0"
                >
                    <div
                        class="mb-8 h-1 w-10"
                        :class="
                            isUrdu()
                                ? 'mr-auto ml-0 bg-[hsl(219,68%,20%)]'
                                : 'bg-[hsl(219,68%,20%)]'
                        "
                    ></div>
                    <h2
                        class="mb-4 text-xl font-bold text-black"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('renewal') }}
                    </h2>
                    <p
                        class="mb-8 flex-1 text-sm leading-relaxed text-gray-600"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('renewal_desc') }}
                    </p>
                    <a
                        href="/renew"
                        class="inline-block rounded px-8 py-3 text-center text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all duration-150 hover:shadow-md"
                        style="background-color: hsl(219, 68%, 20%)"
                    >
                        {{ t('renewal_cta') }}
                    </a>
                </div>

                <!-- Check Status -->
                <div class="flex flex-col p-10">
                    <div
                        class="mb-8 h-1 w-10 bg-gray-400"
                        :class="isUrdu() ? 'mr-auto ml-0' : ''"
                    ></div>
                    <h2
                        class="mb-4 text-xl font-bold text-black"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('check_status') }}
                    </h2>
                    <p
                        class="mb-8 flex-1 text-sm leading-relaxed text-gray-600"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('check_status_desc') }}
                    </p>
                    <a
                        href="/status"
                        class="inline-block rounded border border-gray-400 px-8 py-3 text-center text-xs font-bold tracking-widest text-black uppercase shadow-sm transition-all duration-150 hover:border-gray-600 hover:shadow-md"
                    >
                        {{ t('check_status_cta') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Info Strip -->
        <section
            class="px-6 py-14"
            style="background-color: hsl(219, 68%, 20%)"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-start gap-8 md:flex-row"
            >
                <h3
                    class="shrink-0 text-lg leading-snug font-bold text-white md:w-56"
                    :class="isUrdu() ? 'text-right md:text-right' : ''"
                >
                    {{ t('info_title') }}
                </h3>
                <div
                    class="hidden w-px self-stretch bg-white/20 md:block"
                ></div>
                <p
                    class="text-sm leading-relaxed text-white/80"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('info_body') }}
                </p>
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
