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

    <div
        :dir="isUrdu() ? 'rtl' : 'ltr'"
        :class="isUrdu() ? 'font-[\'Noto_Nastaliq_Urdu\',serif]' : ''"
        class="flex min-h-screen flex-col bg-white"
    >
        <!-- Header -->
        <header class="bg-white">
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
                        href="/"
                        class="text-sm text-gray-500 transition-colors hover:text-black"
                    >
                        {{ t('apply_back') }}
                    </a>
                    <!-- Material outlined button -->
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

        <!-- Page heading -->
        <section
            class="px-6 py-12 text-white"
            style="background-color: hsl(164, 100%, 29%)"
        >
            <div class="mx-auto max-w-3xl">
                <h1
                    class="mb-2 text-3xl font-bold"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('apply_title') }}
                </h1>
                <p
                    class="text-sm leading-relaxed text-white/85"
                    :class="isUrdu() ? 'text-right' : ''"
                >
                    {{ t('apply_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Form -->
        <section class="flex-1 px-6 py-14">
            <div class="mx-auto max-w-3xl">
                <form @submit.prevent="submit" novalidate>
                    <!-- Honeypot -->
                    <div
                        style="position: absolute; left: -9999px; top: -9999px"
                        aria-hidden="true"
                    >
                        <input
                            v-model="form.website"
                            type="text"
                            name="website"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>

                    <!-- Section: Applicant -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_applicant') }}
                        </h2>
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
                    </div>

                    <!-- Section: Company -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_company') }}
                        </h2>
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
                                    v-model="form.company_classification"
                                    :label="t('field_classification')"
                                    :options="
                                        classificationOptions.map((o) => ({
                                            value: o.value,
                                            label: o.label(),
                                        }))
                                    "
                                    :error="form.errors.company_classification"
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
                    </div>

                    <!-- Section: Contact -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_contact') }}
                        </h2>
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
                    </div>

                    <!-- Section: NTN -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_ntn') }}
                        </h2>
                        <label class="flex cursor-pointer items-start gap-3">
                            <!-- Material-style checkbox -->
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center border-2 transition-colors duration-150"
                                :style="
                                    form.has_ntn
                                        ? 'background-color:hsl(164,100%,29%);border-color:hsl(164,100%,29%)'
                                        : 'border-color:#9ca3af'
                                "
                            >
                                <svg
                                    v-if="form.has_ntn"
                                    class="h-3 w-3 text-white"
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
                            <input
                                v-model="form.has_ntn"
                                type="checkbox"
                                class="sr-only"
                            />
                            <span
                                class="text-sm text-black"
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
                    </div>

                    <!-- Section: Security -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_security') }}
                        </h2>
                        <p
                            class="mb-4 text-xs text-gray-500"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('captcha_hint') }}
                        </p>
                        <div class="max-w-[200px]">
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
                    </div>

                    <!-- Section: Confirmation checkbox -->
                    <div class="mb-12">
                        <h2
                            class="mb-8 border-b border-gray-200 pb-2 text-xs font-bold tracking-widest text-gray-500 uppercase"
                            :class="isUrdu() ? 'text-right' : ''"
                        >
                            {{ t('section_confirm') }}
                        </h2>
                        <label class="flex cursor-pointer items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center border-2 transition-colors duration-150"
                                :style="
                                    form.confirm_10_days
                                        ? 'background-color:hsl(164,100%,29%);border-color:hsl(164,100%,29%)'
                                        : form.errors.confirm_10_days
                                          ? 'border-color:#ef4444'
                                          : 'border-color:#9ca3af'
                                "
                            >
                                <svg
                                    v-if="form.confirm_10_days"
                                    class="h-3 w-3 text-white"
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
                            <input
                                v-model="form.confirm_10_days"
                                type="checkbox"
                                class="sr-only"
                            />
                            <span
                                class="text-sm leading-relaxed text-black"
                                :class="isUrdu() ? 'text-right' : ''"
                            >
                                {{ t('confirm_10_days') }}
                            </span>
                        </label>
                        <p
                            v-if="form.errors.confirm_10_days"
                            class="mt-2 text-xs text-red-600"
                        >
                            {{ form.errors.confirm_10_days }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <div :class="isUrdu() ? 'text-right' : ''">
                        <!-- Material contained button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded px-8 py-3 text-xs font-bold tracking-widest text-white uppercase shadow-sm transition-all duration-150 hover:shadow-md focus:outline-none disabled:opacity-60"
                            style="background-color: hsl(164, 100%, 29%)"
                        >
                            {{
                                form.processing ? '…' : t('submit_application')
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-auto border-t border-gray-200 bg-white px-6 py-8">
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
