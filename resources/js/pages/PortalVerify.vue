<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormInput from '@/components/FormInput.vue';
import PublicShell from '@/components/PublicShell.vue';
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    maskedEmail: string;
}>();

const { t, isUrdu } = useTranslation();
const page = usePage();
const flash = computed(
    () => (page.props.flash as Record<string, string> | undefined)?.status,
);

const form = useForm({
    code: '',
});

function submit() {
    form.post('/portal/verify', { preserveScroll: true });
}

function resend() {
    router.post('/portal/resend', {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('portal_verify_title') || 'Enter sign-in code'" />

    <PublicShell
        back-href="/portal/sign-in"
        :back-label="t('portal_verify_change_email') || 'Change email'"
    >
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
                    {{ t('portal_verify_title') || 'Enter your sign-in code' }}
                </h1>
                <p
                    class="mt-3 text-base leading-relaxed text-white/80"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{
                        (
                            t('portal_verify_subtitle') ||
                            'We sent a 6-digit code to :email. The code expires in 10 minutes.'
                        ).replace(':email', props.maskedEmail)
                    }}
                </p>
            </div>
        </section>

        <section class="px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-xl">
                <div
                    v-if="flash"
                    class="mb-4 rounded-md border border-brand-teal/30 bg-brand-teal/10 px-3 py-2 text-sm font-medium text-brand-teal-deep dark:text-brand-teal"
                    role="status"
                    aria-live="polite"
                >
                    {{ flash }}
                </div>
                <div
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8"
                >
                    <form @submit.prevent="submit" novalidate class="space-y-6">
                        <FormInput
                            id="code"
                            v-model="form.code"
                            :label="
                                t('renew_verify_code') || 'Verification code'
                            "
                            :error="form.errors.code"
                            type="text"
                            autocomplete="one-time-code"
                            dir="ltr"
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
                                    : t('portal_verify_cta') ||
                                      'Verify & sign in'
                            }}
                        </button>
                        <button
                            type="button"
                            class="w-full rounded-md px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            @click="resend"
                        >
                            {{ t('portal_verify_resend') || 'Resend code' }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </PublicShell>
</template>
