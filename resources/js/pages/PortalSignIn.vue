<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormInput from '@/components/FormInput.vue';
import PublicShell from '@/components/PublicShell.vue';
import { useTranslation } from '@/composables/useTranslation';

const { t, isUrdu } = useTranslation();

const form = useForm({
    email: '',
});

function submit() {
    form.post('/portal/sign-in', { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('portal_sign_in_title') || 'Sign in'" />

    <PublicShell back-href="/" :back-label="t('apply_back')">
        <section
            class="relative overflow-hidden bg-brand-navy-deep px-4 py-14 text-white sm:px-6 sm:py-16"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
            >
                <div
                    class="absolute -top-16 right-[-6%] h-64 w-64 rounded-full bg-brand-electric/10 blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-24 left-[-6%] h-72 w-72 rounded-full bg-brand-teal/20 blur-3xl"
                ></div>
            </div>
            <div class="relative mx-auto max-w-xl">
                <h1
                    class="text-3xl font-bold tracking-tight sm:text-4xl"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('portal_sign_in_title') || 'Sign in to the portal' }}
                </h1>
                <p
                    class="mt-3 text-base leading-relaxed text-white/80"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{
                        t('portal_sign_in_subtitle') ||
                        'Enter your email. We will send a one-time code to sign you in, no password required.'
                    }}
                </p>
            </div>
        </section>

        <section class="px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-xl">
                <div
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8"
                >
                    <form @submit.prevent="submit" novalidate class="space-y-6">
                        <FormInput
                            id="email"
                            v-model="form.email"
                            :label="t('field_email')"
                            :error="form.errors.email"
                            type="email"
                            autocomplete="email"
                            required
                        />
                        <button
                            type="submit"
                            :disabled="form.processing"
                            :aria-busy="form.processing ? 'true' : undefined"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep disabled:cursor-not-allowed disabled:opacity-60 dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                        >
                            {{
                                form.processing
                                    ? t('submitting')
                                    : t('portal_sign_in_cta') ||
                                      'Send sign-in code'
                            }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </PublicShell>
</template>
