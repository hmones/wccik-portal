<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import { useTranslation } from '@/composables/useTranslation';

const props = defineProps<{
    captchaA: number;
    captchaB: number;
}>();

const { t, isUrdu } = useTranslation();

function switchLocale() {
    router.post(
        `/locale/${isUrdu() ? 'en' : 'ur'}`,
        {},
        { preserveScroll: true },
    );
}

const form = useForm({
    website: '',
    authorized_representative_name: '',
    cnic: '',
    company_name: '',
    company_classification: '',
    address: '',
    district: '',
    phone: '',
    cell: '',
    whatsapp: '',
    email: '',
    has_ntn: false,
    ntn_number: '',
    captcha_answer: '',
    confirm_10_days: false,
});

const classificationOptions = [
    { value: 'proprietorship', label: () => t('class_proprietorship') },
    { value: 'partnership', label: () => t('class_partnership') },
    { value: 'private_ltd', label: () => t('class_pvt_ltd') },
    { value: 'public_ltd', label: () => t('class_public_ltd') },
    { value: 'aop', label: () => t('class_aop') },
];

function submit() {
    form.post('/apply', { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('apply_title')" />

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
                    class="flex items-center gap-3 rounded-md"
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
                        href="/"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        {{ t('apply_back') }}
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
                </nav>
            </div>
        </header>

        <main id="main" class="flex-1" role="main">
            <!-- Page heading -->
            <section
                class="relative overflow-hidden bg-brand-navy-deep px-4 py-14 text-white sm:px-6 sm:py-16"
                aria-labelledby="apply-heading"
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
                <div class="relative mx-auto max-w-3xl">
                    <h1
                        id="apply-heading"
                        class="text-3xl font-bold tracking-tight sm:text-4xl"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('apply_title') }}
                    </h1>
                    <p
                        class="mt-3 max-w-xl text-base leading-relaxed text-white/80"
                        :class="isUrdu() ? 'text-right' : ''"
                    >
                        {{ t('apply_subtitle') }}
                    </p>
                </div>
            </section>

            <!-- Form card -->
            <section class="px-4 py-10 sm:px-6 sm:py-14">
                <div class="mx-auto max-w-3xl">
                    <div
                        class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10"
                    >
                        <form
                            @submit.prevent="submit"
                            novalidate
                            aria-labelledby="apply-heading"
                        >
                            <!-- Honeypot -->
                            <div class="sr-only" aria-hidden="true">
                                <label for="website"
                                    >Website (leave blank)</label
                                >
                                <input
                                    id="website"
                                    v-model="form.website"
                                    type="text"
                                    name="website"
                                    tabindex="-1"
                                    autocomplete="off"
                                />
                            </div>

                            <!-- Section: Applicant -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_applicant') }}
                                </legend>
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <FormInput
                                            id="authorized_representative_name"
                                            v-model="
                                                form.authorized_representative_name
                                            "
                                            :label="t('field_applicant_name')"
                                            :error="
                                                form.errors
                                                    .authorized_representative_name
                                            "
                                            autocomplete="name"
                                            required
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <FormInput
                                            id="cnic"
                                            v-model="form.cnic"
                                            :label="t('field_cnic')"
                                            :hint="t('field_cnic_hint')"
                                            :error="form.errors.cnic"
                                            autocomplete="off"
                                            required
                                        />
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Section: Company -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_company') }}
                                </legend>
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <FormInput
                                            id="company_name"
                                            v-model="form.company_name"
                                            :label="t('field_company_name')"
                                            :error="form.errors.company_name"
                                            autocomplete="organization"
                                            required
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <FormSelect
                                            id="company_classification"
                                            v-model="
                                                form.company_classification
                                            "
                                            :label="t('field_classification')"
                                            :options="
                                                classificationOptions.map(
                                                    (o) => ({
                                                        value: o.value,
                                                        label: o.label(),
                                                    }),
                                                )
                                            "
                                            :error="
                                                form.errors
                                                    .company_classification
                                            "
                                            required
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <FormTextarea
                                            id="address"
                                            v-model="form.address"
                                            :label="t('field_address')"
                                            :error="form.errors.address"
                                            autocomplete="street-address"
                                            :rows="3"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <FormInput
                                            id="district"
                                            v-model="form.district"
                                            :label="t('field_city')"
                                            :error="form.errors.district"
                                            autocomplete="address-level2"
                                            required
                                        />
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Section: Contact -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_contact') }}
                                </legend>
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <div>
                                        <FormInput
                                            id="cell"
                                            v-model="form.cell"
                                            :label="t('field_mobile')"
                                            :error="form.errors.cell"
                                            type="tel"
                                            autocomplete="tel"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <FormInput
                                            id="whatsapp"
                                            v-model="form.whatsapp"
                                            label="WhatsApp"
                                            :error="form.errors.whatsapp"
                                            type="tel"
                                            autocomplete="tel"
                                        />
                                    </div>
                                    <div>
                                        <FormInput
                                            id="phone"
                                            v-model="form.phone"
                                            :label="t('field_phone')"
                                            type="tel"
                                            autocomplete="tel"
                                        />
                                    </div>
                                    <div>
                                        <FormInput
                                            id="email"
                                            v-model="form.email"
                                            :label="t('field_email')"
                                            :error="form.errors.email"
                                            type="email"
                                            autocomplete="email"
                                            required
                                        />
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Section: NTN -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_ntn') }}
                                </legend>
                                <label
                                    class="group -m-2 flex cursor-pointer items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                                >
                                    <input
                                        v-model="form.has_ntn"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-input bg-background transition-colors peer-checked:border-brand-navy peer-checked:bg-brand-navy peer-focus-visible:ring-2 peer-focus-visible:ring-brand-navy/40 dark:peer-checked:border-brand-teal dark:peer-checked:bg-brand-teal"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            v-if="form.has_ntn"
                                            class="h-3 w-3 text-white dark:text-background"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                        >
                                            <path
                                                d="M2 6l3 3 5-5"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        class="text-sm text-foreground"
                                        :class="isUrdu() ? 'text-right' : ''"
                                    >
                                        {{ t('field_has_ntn') }}
                                    </span>
                                </label>

                                <div v-if="form.has_ntn" class="mt-6">
                                    <FormInput
                                        id="ntn_number"
                                        v-model="form.ntn_number"
                                        :label="t('field_ntn')"
                                        :hint="t('field_ntn_hint')"
                                        :error="form.errors.ntn_number"
                                        autocomplete="off"
                                        required
                                    />
                                </div>
                            </fieldset>

                            <!-- Section: Security -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_security') }}
                                </legend>
                                <p
                                    class="mb-4 text-sm text-muted-foreground"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('captcha_hint') }}
                                </p>
                                <div class="max-w-[220px]">
                                    <FormInput
                                        id="captcha_answer"
                                        v-model="form.captcha_answer"
                                        :label="
                                            t('captcha_label', {
                                                a: String(props.captchaA),
                                                b: String(props.captchaB),
                                            })
                                        "
                                        :error="form.errors.captcha_answer"
                                        type="number"
                                        min="0"
                                        max="99"
                                        autocomplete="off"
                                        dir="ltr"
                                        required
                                    />
                                </div>
                            </fieldset>

                            <!-- Section: Confirmation checkbox -->
                            <fieldset class="mb-10">
                                <legend
                                    class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="isUrdu() ? 'text-right' : ''"
                                >
                                    {{ t('section_confirm') }}
                                </legend>
                                <label
                                    class="group -m-2 flex cursor-pointer items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                                >
                                    <input
                                        v-model="form.confirm_10_days"
                                        type="checkbox"
                                        class="peer sr-only"
                                        :aria-invalid="
                                            form.errors.confirm_10_days
                                                ? 'true'
                                                : undefined
                                        "
                                        aria-describedby="confirm_10_days-error"
                                    />
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 bg-background transition-colors peer-checked:border-brand-navy peer-checked:bg-brand-navy peer-focus-visible:ring-2 peer-focus-visible:ring-brand-navy/40 dark:peer-checked:border-brand-teal dark:peer-checked:bg-brand-teal"
                                        :class="
                                            form.errors.confirm_10_days
                                                ? 'border-destructive'
                                                : 'border-input'
                                        "
                                        aria-hidden="true"
                                    >
                                        <svg
                                            v-if="form.confirm_10_days"
                                            class="h-3 w-3 text-white dark:text-background"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                        >
                                            <path
                                                d="M2 6l3 3 5-5"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </span>
                                    <span
                                        class="text-sm leading-relaxed text-foreground"
                                        :class="isUrdu() ? 'text-right' : ''"
                                    >
                                        {{ t('confirm_10_days') }}
                                    </span>
                                </label>
                                <p
                                    v-if="form.errors.confirm_10_days"
                                    id="confirm_10_days-error"
                                    class="mt-2 text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ form.errors.confirm_10_days }}
                                </p>
                            </fieldset>

                            <!-- Submit -->
                            <div :class="isUrdu() ? 'text-right' : ''">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    :aria-busy="
                                        form.processing ? 'true' : undefined
                                    "
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-8 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep disabled:cursor-not-allowed disabled:opacity-60 dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                                >
                                    <svg
                                        v-if="form.processing"
                                        class="h-4 w-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            opacity="0.25"
                                        />
                                        <path
                                            d="M22 12a10 10 0 0 1-10 10"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    {{
                                        form.processing
                                            ? t('submitting') || 'Submitting…'
                                            : t('submit_application')
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
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
