<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicShell from '@/components/PublicShell.vue';
import { useTranslation } from '@/composables/useTranslation';

type JourneyState =
    | 'new_applicant'
    | 'application_in_progress'
    | 'active_member'
    | 'expired_member';

type ApplicationPayload = {
    id: number;
    type: string;
    status: string;
    status_label: string;
    status_color: 'neutral' | 'info' | 'warning' | 'success' | 'danger';
    company_name: string | null;
    submitted_at: string | null;
    updated_at: string;
    has_payment_proof: boolean;
};

type MemberPayload = {
    membership_number: string;
    authorized_representative_name: string;
    company_name: string;
    email: string;
    membership_class: string | null;
    active_until: string | null;
};

const props = defineProps<{
    applicant: {
        email: string;
        name: string | null;
        last_signed_in_at: string | null;
    };
    journey: {
        state: JourneyState;
        application?: ApplicationPayload;
        member?: MemberPayload;
    };
}>();

const { t, isUrdu } = useTranslation();

const statusBadgeClasses: Record<ApplicationPayload['status_color'], string> = {
    neutral: 'border-border bg-muted text-muted-foreground',
    info: 'border-brand-navy/30 bg-brand-navy/10 text-brand-navy dark:text-brand-teal',
    warning: 'border-warning/30 bg-warning/10 text-warning',
    success:
        'border-brand-teal/30 bg-brand-teal/10 text-brand-teal-deep dark:text-brand-teal',
    danger: 'border-destructive/30 bg-destructive/10 text-destructive',
};

const page = usePage();
const flash = computed(
    () => (page.props.flash as Record<string, string> | undefined)?.status,
);

const paymentProofFile = ref<File | null>(null);
const paymentUploadError = ref<string | null>(null);
const paymentUploadingProgress = ref<number | null>(null);

function onPaymentProofChange(e: Event) {
    paymentProofFile.value = (e.target as HTMLInputElement).files?.[0] ?? null;
    paymentUploadError.value = null;
}

function uploadPaymentProof() {
    if (!paymentProofFile.value) {
        paymentUploadError.value =
            t('portal_payment_upload_pick') || 'Please choose a file first.';

        return;
    }

    const payload = new FormData();
    payload.append('payment_proof', paymentProofFile.value);

    router.post('/portal/application/payment-proof', payload, {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => {
            paymentUploadingProgress.value = progress?.percentage ?? null;
        },
        onError: (errors) => {
            paymentUploadError.value =
                (errors as Record<string, string>).payment_proof ||
                t('portal_payment_upload_error') ||
                'Could not upload — please try again.';
        },
        onFinish: () => {
            paymentUploadingProgress.value = null;
        },
    });
}

function signOut() {
    router.post('/portal/sign-out');
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="t('portal_dashboard_title')" />

    <PublicShell>
        <section class="px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-3xl space-y-8">
                <!-- Header: identity + sign out -->
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div :class="isUrdu() ? 'text-right' : ''">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ t('portal_signed_in_as') }}
                        </p>
                        <h1
                            class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                        >
                            {{ props.applicant.name || props.applicant.email }}
                        </h1>
                        <p
                            v-if="props.applicant.name"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ props.applicant.email }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                        @click="signOut"
                    >
                        {{ t('portal_sign_out') }}
                    </button>
                </div>

                <!-- 1. new_applicant → start application -->
                <div
                    v-if="props.journey.state === 'new_applicant'"
                    class="rounded-2xl border border-border bg-card p-8 text-center shadow-sm sm:p-10"
                >
                    <div
                        class="mx-auto mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-navy/10 text-brand-navy dark:bg-brand-teal/15 dark:text-brand-teal"
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
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-foreground">
                        {{ t('portal_journey_new_title') }}
                    </h2>
                    <p
                        class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ t('portal_journey_new_body') }}
                    </p>
                    <a
                        href="/portal/apply"
                        class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                    >
                        {{ t('portal_journey_new_cta') }}
                    </a>
                </div>

                <!-- 2. application_in_progress → status/continue card -->
                <div
                    v-else-if="
                        props.journey.state === 'application_in_progress' &&
                        props.journey.application
                    "
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold tracking-wider uppercase',
                                    statusBadgeClasses[
                                        props.journey.application.status_color
                                    ],
                                ]"
                            >
                                {{ props.journey.application.status_label }}
                            </span>
                            <h2
                                class="mt-3 text-xl font-semibold text-foreground"
                            >
                                {{
                                    props.journey.application.company_name ||
                                    t('portal_untitled_application')
                                }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('portal_last_updated') }}:
                                {{
                                    new Date(
                                        props.journey.application.updated_at,
                                    ).toLocaleString()
                                }}
                            </p>
                        </div>
                        <a
                            v-if="props.journey.application.status === 'draft'"
                            :href="
                                props.journey.application.type === 'renewal'
                                    ? '/portal/renew'
                                    : '/portal/apply'
                            "
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                        >
                            {{ t('portal_continue_editing') }}
                        </a>
                    </div>
                    <div
                        v-if="props.journey.application.status !== 'draft'"
                        class="mt-6 space-y-5"
                    >
                        <p
                            v-if="flash === 'payment_proof_uploaded'"
                            class="rounded-lg border border-brand-teal/30 bg-brand-teal/10 px-4 py-3 text-sm font-medium text-brand-teal-deep dark:text-brand-teal"
                            role="status"
                            aria-live="polite"
                        >
                            {{ t('portal_payment_upload_success') }}
                        </p>

                        <p
                            class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground"
                        >
                            {{ t('portal_status_locked_hint') }}
                        </p>

                        <!-- Awaiting payment: upload card is the primary CTA -->
                        <div
                            v-if="
                                props.journey.application.status ===
                                'awaiting_payment'
                            "
                            class="rounded-lg border-2 border-brand-navy bg-brand-navy/5 p-5 dark:border-brand-teal dark:bg-brand-teal/10"
                        >
                            <h3 class="text-sm font-semibold text-foreground">
                                {{ t('portal_payment_upload_title') }}
                            </h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ t('portal_payment_upload_body') }}
                            </p>

                            <label
                                for="payment_proof_upload"
                                class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border bg-background p-6 text-center transition-colors hover:border-brand-navy hover:bg-muted dark:hover:border-brand-teal"
                            >
                                <svg
                                    aria-hidden="true"
                                    class="h-8 w-8 text-muted-foreground"
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
                                <span
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{
                                        paymentProofFile?.name ||
                                        t('portal_payment_upload_choose')
                                    }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ t('portal_payment_upload_formats') }}
                                </span>
                                <input
                                    id="payment_proof_upload"
                                    type="file"
                                    accept="application/pdf,image/jpeg,image/png"
                                    class="sr-only"
                                    @change="onPaymentProofChange"
                                />
                            </label>

                            <p
                                v-if="paymentUploadError"
                                class="mt-2 text-xs text-destructive"
                                role="alert"
                            >
                                {{ paymentUploadError }}
                            </p>
                            <p
                                v-if="paymentUploadingProgress !== null"
                                class="mt-2 text-xs text-muted-foreground"
                                aria-live="polite"
                            >
                                {{ t('portal_payment_upload_progress') }}:
                                {{ paymentUploadingProgress }}%
                            </p>

                            <button
                                type="button"
                                :disabled="
                                    !paymentProofFile ||
                                    paymentUploadingProgress !== null
                                "
                                class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep disabled:cursor-not-allowed disabled:opacity-60 dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                                @click="uploadPaymentProof"
                            >
                                {{
                                    props.journey.application.has_payment_proof
                                        ? t('portal_payment_upload_replace_cta')
                                        : t('portal_payment_upload_cta')
                                }}
                            </button>
                        </div>

                        <!-- Required documents checklist -->
                        <div
                            class="rounded-lg border border-border bg-background p-5"
                        >
                            <h3
                                class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                {{ t('portal_checklist_title') }}
                            </h3>
                            <p class="mb-4 text-sm text-muted-foreground">
                                {{ t('portal_checklist_body') }}
                            </p>
                            <ul class="space-y-2 text-sm">
                                <li
                                    v-for="item in [
                                        t('portal_checklist_item_cnic'),
                                        t('portal_checklist_item_photos'),
                                        t('portal_checklist_item_profile'),
                                        t('portal_checklist_item_tax'),
                                        t('portal_checklist_item_ntn'),
                                        t('portal_checklist_item_fee'),
                                        t('portal_checklist_item_signature'),
                                    ]"
                                    :key="item"
                                    class="flex items-start gap-2 text-foreground"
                                >
                                    <svg
                                        aria-hidden="true"
                                        class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect
                                            x="3"
                                            y="3"
                                            width="18"
                                            height="18"
                                            rx="3"
                                        />
                                    </svg>
                                    <span>{{ item }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Download filled PDF -->
                        <a
                            href="/portal/application/pdf"
                            class="inline-flex items-center gap-2 rounded-lg border border-brand-navy bg-background px-5 py-2.5 text-sm font-semibold text-brand-navy transition-colors hover:bg-brand-navy hover:text-white dark:border-brand-teal dark:text-brand-teal dark:hover:bg-brand-teal dark:hover:text-background"
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
                                <path
                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            {{ t('portal_download_filled_pdf') }}
                        </a>
                    </div>
                </div>

                <!-- 3. active_member → details + expiry, no action -->
                <div
                    v-else-if="
                        props.journey.state === 'active_member' &&
                        props.journey.member
                    "
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-brand-teal/30 bg-brand-teal/10 px-2.5 py-1 text-xs font-semibold tracking-wider text-brand-teal-deep uppercase dark:text-brand-teal"
                    >
                        <svg
                            aria-hidden="true"
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        {{ t('portal_journey_active_badge') }}
                    </span>
                    <h2 class="mt-3 text-2xl font-bold text-foreground">
                        {{ props.journey.member.company_name }}
                    </h2>
                    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('portal_journey_member_number') }}
                            </dt>
                            <dd class="mt-0.5 font-mono text-foreground">
                                {{ props.journey.member.membership_number }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                {{ t('portal_journey_member_rep') }}
                            </dt>
                            <dd class="mt-0.5 text-foreground">
                                {{
                                    props.journey.member
                                        .authorized_representative_name
                                }}
                            </dd>
                        </div>
                        <div v-if="props.journey.member.membership_class">
                            <dt class="text-muted-foreground">
                                {{ t('field_membership_class') }}
                            </dt>
                            <dd class="mt-0.5 text-foreground">
                                {{
                                    props.journey.member.membership_class ===
                                    'corporate'
                                        ? t('class_corporate')
                                        : t('class_associate')
                                }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground">
                                {{ t('portal_journey_expires_on') }}
                            </dt>
                            <dd
                                class="mt-0.5 text-lg font-semibold text-foreground"
                            >
                                {{
                                    formatDate(
                                        props.journey.member.active_until,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                    <p
                        class="mt-5 rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground"
                    >
                        {{ t('portal_journey_active_hint') }}
                    </p>
                </div>

                <!-- 4. expired_member → details + Renew Now -->
                <div
                    v-else-if="
                        props.journey.state === 'expired_member' &&
                        props.journey.member
                    "
                    class="rounded-2xl border border-destructive/30 bg-card p-6 shadow-sm sm:p-8"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-destructive/30 bg-destructive/10 px-2.5 py-1 text-xs font-semibold tracking-wider text-destructive uppercase"
                    >
                        <svg
                            aria-hidden="true"
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 8v4M12 16h.01" />
                        </svg>
                        {{ t('portal_journey_expired_badge') }}
                    </span>
                    <h2 class="mt-3 text-xl font-semibold text-foreground">
                        {{ props.journey.member.company_name }}
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ t('portal_journey_member_number') }}:
                        <span class="font-mono">{{
                            props.journey.member.membership_number
                        }}</span>
                    </p>
                    <p
                        class="mt-4 rounded-lg border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                    >
                        {{ t('portal_journey_expired_body') }}
                        <strong>{{
                            formatDate(props.journey.member.active_until)
                        }}</strong
                        >.
                    </p>
                    <a
                        href="/portal/renew"
                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-brand-navy-deep dark:bg-brand-teal dark:text-background dark:hover:bg-brand-teal-deep"
                    >
                        {{ t('renewal_cta') }}
                    </a>
                </div>

                <p
                    v-if="props.applicant.last_signed_in_at"
                    class="text-center text-xs text-muted-foreground"
                >
                    {{ t('portal_last_signed_in') }}:
                    {{
                        new Date(
                            props.applicant.last_signed_in_at,
                        ).toLocaleString()
                    }}
                </p>
            </div>
        </section>
    </PublicShell>
</template>
