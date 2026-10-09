<script setup lang="ts">
import FormInput from '@/components/FormInput.vue';
import FormSelect from '@/components/FormSelect.vue';
import { useTranslation } from '@/composables/useTranslation';

defineProps<{
    options: { value: string; label: string }[];
    errors?: Record<string, string>;
}>();
const paymentDate = defineModel<string>('paymentDate', { required: true });
const paymentMethod = defineModel<string>('paymentMethod', { required: true });
const { t } = useTranslation();
</script>

<template>
    <div class="mt-5 grid gap-6 sm:grid-cols-2">
        <FormInput
            id="payment_date"
            v-model="paymentDate"
            type="date"
            :label="t('field_payment_date')"
            :hint="t('field_payment_date_hint')"
            :error="errors?.payment_date"
            required
        />
        <FormSelect
            id="payment_method"
            v-model="paymentMethod"
            :label="t('field_payment_method')"
            :options="
                options.map((option) => ({
                    value: option.value,
                    label: t(`payment_method_${option.value}`),
                }))
            "
            :error="errors?.payment_method"
            required
        />
    </div>
</template>
