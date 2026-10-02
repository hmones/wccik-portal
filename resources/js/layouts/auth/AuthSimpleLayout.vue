<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <a href="#auth-content" class="skip-link">Skip to main content</a>

    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10"
    >
        <!-- Subtle decorative background -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10"
        >
            <div
                class="absolute top-0 left-1/2 h-96 w-[36rem] -translate-x-1/2 rounded-full bg-brand-teal/10 blur-3xl dark:bg-brand-teal/15"
            ></div>
            <div
                class="absolute right-0 bottom-0 h-72 w-72 translate-x-1/4 translate-y-1/4 rounded-full bg-brand-navy/10 blur-3xl dark:bg-brand-electric/5"
            ></div>
        </div>

        <main id="auth-content" class="w-full max-w-sm" role="main">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                        :aria-label="title || 'Home'"
                    >
                        <div
                            class="mb-1 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-navy/10 text-brand-navy ring-1 ring-brand-navy/20 dark:bg-brand-teal/15 dark:text-brand-teal dark:ring-brand-teal/30"
                        >
                            <AppLogoIcon class="size-7 fill-current" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8"
                >
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
