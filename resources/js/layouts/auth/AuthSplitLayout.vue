<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <a href="#auth-content" class="skip-link">Skip to main content</a>

    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <aside
            class="relative hidden h-full flex-col overflow-hidden bg-brand-navy-deep p-10 text-white lg:flex"
            aria-label="Branding"
        >
            <!-- Decorative glow -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
            >
                <div
                    class="absolute -top-24 -left-16 h-80 w-80 rounded-full bg-brand-electric/10 blur-3xl"
                ></div>
                <div
                    class="absolute right-[-10%] bottom-[-20%] h-96 w-96 rounded-full bg-brand-teal/25 blur-3xl"
                ></div>
                <div
                    class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,hsl(var(--brand-teal)/0.15),transparent_50%)]"
                ></div>
            </div>

            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-semibold tracking-tight"
            >
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </Link>

            <div class="relative z-20 mt-auto">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium tracking-wider text-white/80 uppercase backdrop-blur-sm"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-brand-electric shadow-[0_0_8px_hsl(var(--brand-electric))]"
                        aria-hidden="true"
                    ></span>
                    Secure membership portal
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/70">
                    Access the Women Chamber of Commerce &amp; Industry
                    membership workspace.
                </p>
            </div>
        </aside>

        <main id="auth-content" class="lg:p-8" role="main">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[360px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1
                        class="text-2xl font-semibold tracking-tight"
                        v-if="title"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
