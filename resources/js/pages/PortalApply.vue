<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import FormTextarea from '@/components/FormTextarea.vue';
import PublicShell from '@/components/PublicShell.vue';
import { useAutosave } from '@/composables/useAutosave';
import { useTranslation } from '@/composables/useTranslation';

type Draft = {
    id: number;
    membership_class: string | null;
    industry: string | null;
    authorized_representative_name: string | null;
    cnic: string | null;
    cnic_expiry_date: string | null;
    company_name: string | null;
    website: string | null;
    established_year: number | null;
    company_classification: string | null;
    turnover_pkr: number | null;
    employees_count: number | null;
    address: string | null;
    postal_code: string | null;
    district: string | null;
    phone: string | null;
    cell: string | null;
    whatsapp: string | null;
    alternate_no: string | null;
    has_ntn: boolean;
    ntn_number: string | null;
    sales_tax_no: string | null;
    other_chamber_memberships: string | null;
    terms_confirmed: boolean;
    updated_at: string;
};

const props = defineProps<{
    draft: Draft | null;
    applicantEmail: string;
}>();

const { t, isUrdu } = useTranslation();

const data = reactive({
    membership_class: props.draft?.membership_class ?? 'corporate',
    industry: props.draft?.industry ?? 'services',
    authorized_representative_name:
        props.draft?.authorized_representative_name ?? '',
    cnic: props.draft?.cnic ?? '',
    cnic_expiry_date: props.draft?.cnic_expiry_date ?? '',
    company_name: props.draft?.company_name ?? '',
    website: props.draft?.website ?? '',
    established_year: props.draft?.established_year ?? '',
    company_classification: props.draft?.company_classification ?? '',
    turnover_pkr: props.draft?.turnover_pkr ?? '',
    employees_count: props.draft?.employees_count ?? '',
    address: props.draft?.address ?? '',
    postal_code: props.draft?.postal_code ?? '',
    district: props.draft?.district ?? 'Karachi',
    phone: props.draft?.phone ?? '',
    cell: props.draft?.cell ?? '',
    whatsapp: props.draft?.whatsapp ?? '',
    alternate_no: props.draft?.alternate_no ?? '',
    has_ntn: props.draft?.has_ntn ?? false,
    ntn_number: props.draft?.ntn_number ?? '',
    sales_tax_no: props.draft?.sales_tax_no ?? '',
    other_chamber_memberships: props.draft?.other_chamber_memberships ?? '',
    terms_confirmed: props.draft?.terms_confirmed ?? false,
});

const dataRef = computed(() => data);
const submitErrors = ref<Record<string, string>>({});

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
        const res = await fetch('/portal/apply/autosave', {
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

watch(
    () => data.has_ntn,
    (val) => {
        if (!val) {
            data.ntn_number = '';
        }
    },
);

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

    router.post('/portal/apply/submit', payload, {
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
    <Head :title="t('apply_title')" />

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
                <h1
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

        <section class="px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-3xl">
                <!-- Autosave status bar -->
                <div
                    class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/40 px-4 py-2 text-sm"
                    role="status"
                    aria-live="polite"
                    v-if="saveLabel !== ''"
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

                        <!-- Company + Representative -->
                        <fieldset class="mb-10">
                            <legend
                                class="mb-6 w-full border-b border-border pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('renew_section_company') }}
                            </legend>
                            <!-- Email (locked to applicant.email) -->
                            <div class="mb-6">
                                <label
                                    class="block text-sm font-medium text-foreground"
                                >
                                    {{ t('field_email') }}
                                </label>
                                <div
                                    class="mt-1 flex items-center gap-2 rounded-lg border border-input bg-muted px-3 py-2 text-sm text-foreground"
                                >
                                    <svg
                                        aria-hidden="true"
                                        class="h-4 w-4 text-muted-foreground"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect
                                            x="3"
                                            y="5"
                                            width="18"
                                            height="14"
                                            rx="2"
                                        />
                                        <polyline points="3 7 12 13 21 7" />
                                    </svg>
                                    <span>{{ props.applicantEmail }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ t('portal_apply_email_locked') }}
                                </p>
                            </div>

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
                                <div class="sm:col-span-2">
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

                        <!-- Identity & Tax -->
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
                            </div>

                            <!-- NTN toggle -->
                            <label
                                class="group -m-2 mt-6 flex cursor-pointer items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/50"
                            >
                                <input
                                    v-model="data.has_ntn"
                                    type="checkbox"
                                    class="peer sr-only"
                                    @change="onBlur"
                                />
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-input bg-background transition-colors peer-checked:border-brand-navy peer-checked:bg-brand-navy peer-focus-visible:ring-2 peer-focus-visible:ring-brand-navy/40 dark:peer-checked:border-brand-teal dark:peer-checked:bg-brand-teal"
                                    aria-hidden="true"
                                >
                                    <svg
                                        v-if="data.has_ntn"
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

                            <div
                                v-if="data.has_ntn"
                                class="mt-6 grid gap-6 sm:grid-cols-2"
                            >
                                <div>
                                    <FormInput
                                        id="ntn_number"
                                        v-model="data.ntn_number"
                                        :label="t('field_ntn')"
                                        :hint="t('field_ntn_hint')"
                                        :error="submitErrors.ntn_number"
                                        dir="ltr"
                                        required
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

                        <p
                            class="mb-8 rounded-lg border border-border bg-muted/40 p-4 text-sm text-muted-foreground"
                        >
                            {{ t('portal_payment_after_acceptance') }}
                        </p>

                        <!-- Confirmation -->
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
                                    {{ t('confirm_10_days') }}
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
                                        : t('submit_application')
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </PublicShell>
</template>
