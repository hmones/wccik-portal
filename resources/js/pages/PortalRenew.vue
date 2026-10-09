<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import PaymentDetailsFields from '@/components/PaymentDetailsFields.vue';
import PublicShell from '@/components/PublicShell.vue';
import { useAutosave } from '@/composables/useAutosave';
import { useTranslation } from '@/composables/useTranslation';

type Member = {
    membership_number: string;
    membership_class: string | null;
    authorized_representative_name: string;
    company_name: string;
    email: string;
    website: string | null;
    established_year: number | null;
    industry: string | null;
    company_classification: string | null;
    cnic: string;
    cnic_expiry_date: string | null;
    turnover_pkr: number | null;
    employees_count: number | null;
    ntn_number: string | null;
    sales_tax_no: string | null;
    address: string | null;
    postal_code: string | null;
    district: string | null;
    phone: string | null;
    cell: string;
    whatsapp: string | null;
    alternate_no: string | null;
    other_chamber_memberships: string | null;
};

type Draft = {
    id: number;
    membership_class: string | null;
    industry: string | null;
    authorized_representative_name: string | null;
    company_name: string | null;
    email: string | null;
    website: string | null;
    established_year: number | null;
    company_classification: string | null;
    cnic: string | null;
    cnic_expiry_date: string | null;
    turnover_pkr: number | null;
    employees_count: number | null;
    ntn_number: string | null;
    sales_tax_no: string | null;
    address: string | null;
    postal_code: string | null;
    district: string | null;
    phone: string | null;
    cell: string | null;
    whatsapp: string | null;
    alternate_no: string | null;
    other_chamber_memberships: string | null;
    terms_confirmed: boolean;
    updated_at: string;
};

const props = defineProps<{
    paymentMethods: { value: string; label: string }[];
    member: Member;
    draft: Draft | null;
}>();

const { t, isUrdu } = useTranslation();

// Draft wins over member when present; otherwise pre-fill from member record.
const source = props.draft ?? props.member;

const data = reactive({
    membership_class:
        source.membership_class ?? props.member.membership_class ?? 'corporate',
    industry: source.industry ?? props.member.industry ?? 'services',
    authorized_representative_name:
        source.authorized_representative_name ??
        props.member.authorized_representative_name,
    company_name: source.company_name ?? props.member.company_name,
    email: source.email ?? props.member.email,
    website: source.website ?? props.member.website ?? '',
    established_year:
        source.established_year ?? props.member.established_year ?? '',
    company_classification:
        source.company_classification ??
        props.member.company_classification ??
        '',
    cnic: source.cnic ?? props.member.cnic,
    cnic_expiry_date:
        source.cnic_expiry_date ?? props.member.cnic_expiry_date ?? '',
    turnover_pkr: source.turnover_pkr ?? props.member.turnover_pkr ?? '',
    employees_count:
        source.employees_count ?? props.member.employees_count ?? '',
    ntn_number: source.ntn_number ?? props.member.ntn_number ?? '',
    sales_tax_no: source.sales_tax_no ?? props.member.sales_tax_no ?? '',
    address: source.address ?? props.member.address ?? '',
    postal_code: source.postal_code ?? props.member.postal_code ?? '',
    district: source.district ?? props.member.district ?? 'Karachi',
    phone: source.phone ?? props.member.phone ?? '',
    cell: source.cell ?? props.member.cell,
    whatsapp: source.whatsapp ?? props.member.whatsapp ?? '',
    alternate_no: source.alternate_no ?? props.member.alternate_no ?? '',
    other_chamber_memberships:
        source.other_chamber_memberships ??
        props.member.other_chamber_memberships ??
        '',
    terms_confirmed: props.draft?.terms_confirmed ?? false,
});

const paymentProof = ref<File | null>(null);
const paymentProofName = ref<string>('');
const paymentDate = ref('');
const paymentMethod = ref('');
const submitErrors = ref<Record<string, string>>({});

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    paymentProof.value = file;
    paymentProofName.value = file?.name ?? '';
}

const dataRef = computed(() => data);

function getCsrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

const {
    status: saveStatus,
    lastSavedAt,
    flushNow,
} = useAutosave({
    data: dataRef,
    save: async (payload) => {
        const res = await fetch('/portal/renew/autosave', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            throw new Error(`autosave failed: ${res.status}`);
        }
    },
    debounceMs: 5000,
});

const saveLabel = computed(() => {
    switch (saveStatus.value) {
        case 'saving':
            return t('portal_apply_saving');
        case 'saved':
            if (!lastSavedAt.value) {
                return '';
            }

            return t('portal_apply_saved');
        case 'error':
            return t('portal_apply_save_error');
        case 'typing':
            return t('portal_apply_typing');
        default:
            return props.draft ? t('portal_apply_saved_earlier') : '';
    }
});

const classificationOptions = [
    { value: 'proprietorship', label: t('class_proprietorship') },
    { value: 'partnership', label: t('class_partnership') },
    { value: 'private_ltd', label: t('class_pvt_ltd') },
    { value: 'public_ltd', label: t('class_public_ltd') },
    { value: 'aop', label: t('class_aop') },
];

const submitForm = useForm({});

function onBlur() {
    void flushNow();
}

async function submit() {
    await flushNow();
    submitErrors.value = {};

    const payload = new FormData();

    for (const [k, v] of Object.entries(data)) {
        if (typeof v === 'boolean') {
            payload.append(k, v ? '1' : '0');
        } else if (v !== null && v !== undefined) {
            payload.append(k, String(v));
        }
    }

    if (paymentProof.value) {
        payload.append('payment_proof', paymentProof.value);
        payload.append('payment_date', paymentDate.value);
        payload.append('payment_method', paymentMethod.value);
    }

    router.post('/portal/renew/submit', payload, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            submitErrors.value = errors as Record<string, string>;
        },
        onStart: () => {
            submitForm.processing = true;
        },
        onFinish: () => {
            submitForm.processing = false;
        },
    });
}
</script>

<template>
    <Head :title="t('renew_form_title')" />

    <PublicShell
        back-href="/portal"
        :back-label="t('portal_dashboard_title')"
        show-sign-out
        :before-sign-out="flushNow"
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
            <div class="relative mx-auto max-w-3xl">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium tracking-wider text-white/80 uppercase backdrop-blur-sm"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full bg-brand-electric shadow-[0_0_8px_hsl(var(--brand-electric))]"
                        aria-hidden="true"
                    ></span>
                    {{ props.member.membership_number }}
                </div>
                <h1
                    class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('renew_form_title') }}
                </h1>
                <p
                    class="mt-3 max-w-xl text-base leading-relaxed text-white/80"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('renew_form_subtitle') }}
                </p>
            </div>
        </section>

        <section class="px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-3xl">
                <!-- Autosave status bar -->
                <div
                    class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/40 px-4 py-2 text-sm"
                    role="status"
                    aria-live="polite"
                >
                    <span class="flex items-center gap-2 text-muted-foreground">
                        <svg
                            v-if="saveStatus === 'saving'"
                            class="h-4 w-4 animate-spin text-brand-navy dark:text-brand-teal"
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
                        <svg
                            v-else-if="
                                saveStatus === 'saved' ||
                                ((!saveStatus || saveStatus === 'idle') &&
                                    props.draft)
                            "
                            class="h-4 w-4 text-brand-teal-deep dark:text-brand-teal"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        <svg
                            v-else-if="saveStatus === 'error'"
                            class="h-4 w-4 text-destructive"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <path
                                d="M12 8v4M12 16h.01"
                                stroke-linecap="round"
                            />
                        </svg>
                        {{ saveLabel }}
                    </span>
                </div>

                <div
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-10"
                >
                    <form
                        @submit.prevent="submit"
                        novalidate
                        enctype="multipart/form-data"
                    >
                        <!-- Membership class + industry -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_membership') }}
                            </legend>

                            <fieldset>
                                <legend
                                    class="mb-3 text-sm font-medium text-foreground"
                                >
                                    {{ t('field_membership_class') }}
                                </legend>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label
                                        v-for="opt in [
                                            {
                                                value: 'corporate',
                                                label: t('class_corporate'),
                                                desc: t('class_corporate_desc'),
                                            },
                                            {
                                                value: 'associate',
                                                label: t('class_associate'),
                                                desc: t('class_associate_desc'),
                                            },
                                        ]"
                                        :key="opt.value"
                                        class="relative flex cursor-pointer gap-3 rounded-lg border p-4 transition-colors"
                                        :class="
                                            data.membership_class === opt.value
                                                ? 'border-brand-navy bg-brand-navy/5 dark:border-brand-teal dark:bg-brand-teal/10'
                                                : 'border-border hover:bg-muted/50'
                                        "
                                    >
                                        <input
                                            v-model="data.membership_class"
                                            type="radio"
                                            :value="opt.value"
                                            class="peer sr-only"
                                            @change="onBlur"
                                        />
                                        <span
                                            class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                            :class="
                                                data.membership_class ===
                                                opt.value
                                                    ? 'border-brand-navy dark:border-brand-teal'
                                                    : 'border-input'
                                            "
                                            aria-hidden="true"
                                        >
                                            <span
                                                v-if="
                                                    data.membership_class ===
                                                    opt.value
                                                "
                                                class="block h-2 w-2 rounded-full bg-brand-navy dark:bg-brand-teal"
                                            ></span>
                                        </span>
                                        <div class="min-w-0">
                                            <div
                                                class="text-sm font-semibold text-foreground"
                                            >
                                                {{ opt.label }}
                                            </div>
                                            <div
                                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                                            >
                                                {{ opt.desc }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p
                                    v-if="submitErrors.membership_class"
                                    class="mt-2 text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ submitErrors.membership_class }}
                                </p>
                            </fieldset>

                            <fieldset class="mt-6">
                                <legend
                                    class="mb-3 text-sm font-medium text-foreground"
                                >
                                    {{ t('field_industry') }}
                                </legend>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="opt in [
                                            {
                                                value: 'trading',
                                                label: t('industry_trading'),
                                            },
                                            {
                                                value: 'services',
                                                label: t('industry_services'),
                                            },
                                            {
                                                value: 'manufacturing',
                                                label: t(
                                                    'industry_manufacturing',
                                                ),
                                            },
                                        ]"
                                        :key="opt.value"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-full border px-4 py-2 text-sm transition-colors"
                                        :class="
                                            data.industry === opt.value
                                                ? 'border-brand-navy bg-brand-navy text-white dark:border-brand-teal dark:bg-brand-teal dark:text-background'
                                                : 'border-border text-foreground hover:bg-muted/50'
                                        "
                                    >
                                        <input
                                            v-model="data.industry"
                                            type="radio"
                                            :value="opt.value"
                                            class="sr-only"
                                            @change="onBlur"
                                        />
                                        {{ opt.label }}
                                    </label>
                                </div>
                                <p
                                    v-if="submitErrors.industry"
                                    class="mt-2 text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ submitErrors.industry }}
                                </p>
                            </fieldset>
                        </fieldset>

                        <!-- Company -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_company') }}
                            </legend>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <FormInput
                                        id="authorized_representative_name"
                                        v-model="
                                            data.authorized_representative_name
                                        "
                                        :label="t('field_applicant_name')"
                                        :error="
                                            submitErrors.authorized_representative_name
                                        "
                                        autocomplete="name"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <FormInput
                                        id="company_name"
                                        v-model="data.company_name"
                                        :label="t('field_company_name')"
                                        :error="submitErrors.company_name"
                                        autocomplete="organization"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="email"
                                        v-model="data.email"
                                        :label="t('field_email')"
                                        :error="submitErrors.email"
                                        type="email"
                                        autocomplete="email"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="website"
                                        v-model="data.website"
                                        :label="t('field_website')"
                                        :error="submitErrors.website"
                                        type="url"
                                        autocomplete="url"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="established_year"
                                        v-model="data.established_year"
                                        :label="t('field_established_year')"
                                        :error="submitErrors.established_year"
                                        type="number"
                                        min="1900"
                                        :max="String(new Date().getFullYear())"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormSelect
                                        id="company_classification"
                                        v-model="data.company_classification"
                                        :label="t('field_classification')"
                                        :options="classificationOptions"
                                        :error="
                                            submitErrors.company_classification
                                        "
                                        required
                                        @change="onBlur"
                                    />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Identity / tax -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_identity') }}
                            </legend>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <FormInput
                                        id="cnic"
                                        v-model="data.cnic"
                                        :label="t('field_cnic')"
                                        :hint="t('field_cnic_hint')"
                                        :error="submitErrors.cnic"
                                        dir="ltr"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="cnic_expiry_date"
                                        v-model="data.cnic_expiry_date"
                                        :label="t('field_cnic_expiry')"
                                        :error="submitErrors.cnic_expiry_date"
                                        type="date"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="turnover_pkr"
                                        v-model="data.turnover_pkr"
                                        :label="t('field_turnover')"
                                        :error="submitErrors.turnover_pkr"
                                        type="number"
                                        min="0"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="employees_count"
                                        v-model="data.employees_count"
                                        :label="t('field_employees')"
                                        :error="submitErrors.employees_count"
                                        type="number"
                                        min="0"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="ntn_number"
                                        v-model="data.ntn_number"
                                        :label="t('field_ntn')"
                                        :error="submitErrors.ntn_number"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="sales_tax_no"
                                        v-model="data.sales_tax_no"
                                        :label="t('field_sales_tax')"
                                        :error="submitErrors.sales_tax_no"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Address -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_address') }}
                            </legend>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <FormTextarea
                                        id="address"
                                        v-model="data.address"
                                        :label="t('field_address')"
                                        :error="submitErrors.address"
                                        autocomplete="street-address"
                                        :rows="3"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="postal_code"
                                        v-model="data.postal_code"
                                        :label="t('field_postal_code')"
                                        :error="submitErrors.postal_code"
                                        autocomplete="postal-code"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="district"
                                        v-model="data.district"
                                        :label="t('field_city')"
                                        :error="submitErrors.district"
                                        autocomplete="address-level2"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Contact -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_contact') }}
                            </legend>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <FormInput
                                        id="cell"
                                        v-model="data.cell"
                                        :label="t('field_mobile')"
                                        :error="submitErrors.cell"
                                        type="tel"
                                        autocomplete="tel"
                                        dir="ltr"
                                        required
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="whatsapp"
                                        v-model="data.whatsapp"
                                        label="WhatsApp"
                                        :error="submitErrors.whatsapp"
                                        type="tel"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="phone"
                                        v-model="data.phone"
                                        :label="t('field_phone')"
                                        :error="submitErrors.phone"
                                        type="tel"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                                <div>
                                    <FormInput
                                        id="alternate_no"
                                        v-model="data.alternate_no"
                                        :label="t('field_alternate_no')"
                                        :error="submitErrors.alternate_no"
                                        type="tel"
                                        dir="ltr"
                                        @blur="onBlur"
                                    />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Other memberships -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_other') }}
                            </legend>
                            <FormTextarea
                                id="other_chamber_memberships"
                                v-model="data.other_chamber_memberships"
                                :label="t('field_other_chambers')"
                                :error="submitErrors.other_chamber_memberships"
                                :rows="2"
                                @blur="onBlur"
                            />
                        </fieldset>

                        <!-- Payment proof (not autosaved, attached on submit) -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_payment') }}
                            </legend>
                            <label
                                for="payment_proof"
                                class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-border bg-muted/40 p-8 text-center transition-colors hover:border-brand-navy/40 hover:bg-muted dark:hover:border-brand-teal/40"
                            >
                                <svg
                                    aria-hidden="true"
                                    class="h-10 w-10 text-muted-foreground"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                    />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                <div>
                                    <p
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{
                                            paymentProofName ||
                                            t('field_payment_proof')
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ t('field_payment_proof_hint') }}
                                    </p>
                                    <p
                                        class="mt-2 text-xs text-muted-foreground italic"
                                    >
                                        {{
                                            t(
                                                'portal_renew_payment_not_autosaved',
                                            )
                                        }}
                                    </p>
                                </div>
                                <input
                                    id="payment_proof"
                                    type="file"
                                    accept="application/pdf,image/jpeg,image/png"
                                    class="sr-only"
                                    required
                                    @change="onFileChange"
                                />
                            </label>
                            <p
                                v-if="submitErrors.payment_proof"
                                class="mt-2 text-xs text-destructive"
                                role="alert"
                            >
                                {{ submitErrors.payment_proof }}
                            </p>
                            <PaymentDetailsFields
                                v-if="paymentProof"
                                v-model:payment-date="paymentDate"
                                v-model:payment-method="paymentMethod"
                                :options="props.paymentMethods"
                                :errors="submitErrors"
                            />
                        </fieldset>

                        <!-- Confirmation -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_confirm') }}
                            </legend>
                            <label
                                class="group -m-2 flex cursor-pointer items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                            >
                                <input
                                    v-model="data.terms_confirmed"
                                    type="checkbox"
                                    class="peer sr-only"
                                    @change="onBlur"
                                />
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 bg-background transition-colors peer-checked:border-brand-navy peer-checked:bg-brand-navy peer-focus-visible:ring-2 peer-focus-visible:ring-brand-navy/40 dark:peer-checked:border-brand-teal dark:peer-checked:bg-brand-teal"
                                    :class="
                                        submitErrors.terms_confirmed
                                            ? 'border-destructive'
                                            : 'border-input'
                                    "
                                    aria-hidden="true"
                                >
                                    <svg
                                        v-if="data.terms_confirmed"
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
                                    {{ t('confirm_renewal_terms') }}
                                </span>
                            </label>
                            <p
                                v-if="submitErrors.terms_confirmed"
                                class="mt-2 text-xs text-destructive"
                                role="alert"
                            >
                                {{ submitErrors.terms_confirmed }}
                            </p>
                        </fieldset>

                        <div :class="isUrdu() ? 'text-right' : ''">
                            <button
                                type="submit"
                                :disabled="submitForm.processing"
                                :aria-busy="
                                    submitForm.processing ? 'true' : undefined
                                "
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-8 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep disabled:cursor-not-allowed disabled:opacity-60 dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                            >
                                {{
                                    submitForm.processing
                                        ? t('submitting')
                                        : t('submit_renewal')
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </PublicShell>
</template>
