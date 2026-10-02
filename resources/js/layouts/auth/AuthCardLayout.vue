<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <a href="#auth-content" class="skip-link">Skip to main content</a>

    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-muted p-6 md:p-10"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10"
        >
            <div
                class="absolute top-0 left-1/2 h-96 w-[32rem] -translate-x-1/2 rounded-full bg-brand-teal/10 blur-3xl"
            ></div>
        </div>

        <main
            id="auth-content"
            class="flex w-full max-w-md flex-col gap-6"
            role="main"
        >
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
                :aria-label="title || 'Home'"
            >
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-navy/10 text-brand-navy ring-1 ring-brand-navy/20 dark:bg-brand-teal/15 dark:text-brand-teal dark:ring-brand-teal/30"
                >
                    <AppLogoIcon class="size-6 fill-current" />
                </div>
            </Link>

            <Card class="rounded-2xl border-border shadow-sm">
                <CardHeader class="px-8 pt-8 pb-0 text-center sm:px-10">
                    <CardTitle class="text-2xl font-semibold tracking-tight">{{
                        title
                    }}</CardTitle>
                    <CardDescription>{{ description }}</CardDescription>
                </CardHeader>
                <CardContent class="px-8 py-8 sm:px-10">
                    <slot />
                </CardContent>
            </Card>
        </main>
    </div>
</template>
