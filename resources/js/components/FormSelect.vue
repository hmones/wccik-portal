<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    modelValue: string | null | undefined;
    label: string;
    id: string;
    options: { value: string; label: string }[];
    error?: string;
    required?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const focused = ref(false);
const isActive = () =>
    focused.value ||
    (props.modelValue !== '' &&
        props.modelValue !== null &&
        props.modelValue !== undefined);

const describedBy = computed(() =>
    props.error ? `${props.id}-error` : undefined,
);
</script>

<template>
    <div class="relative mt-1">
        <select
            :id="id"
            :value="modelValue ?? ''"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="peer w-full appearance-none rounded-lg border bg-background px-3 pe-10 pt-5 pb-2 text-sm text-foreground transition-colors duration-150 focus:ring-2 focus:outline-none"
            :class="
                error
                    ? 'border-destructive focus:border-destructive focus:ring-destructive/30'
                    : 'border-input hover:border-foreground/40 focus:border-brand-navy focus:ring-brand-navy/25 dark:focus:border-brand-teal dark:focus:ring-brand-teal/30'
            "
            @focus="focused = true"
            @blur="focused = false"
            @change="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLSelectElement).value,
                )
            "
        >
            <option value="" disabled />
            <option v-for="opt in options" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>
        <label
            :for="id"
            class="pointer-events-none absolute left-3 bg-background px-1 text-sm transition-all duration-150"
            :class="[
                isActive()
                    ? '-top-2 text-xs ' +
                      (error
                          ? 'text-destructive'
                          : focused
                            ? 'text-brand-navy dark:text-brand-teal'
                            : 'text-muted-foreground')
                    : 'top-4 text-muted-foreground',
            ]"
        >
            {{ label
            }}<span
                v-if="required"
                class="ml-0.5 text-destructive"
                aria-hidden="true"
                >*</span
            >
            <span v-if="required" class="sr-only">(required)</span>
        </label>
        <div
            class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-muted-foreground"
            aria-hidden="true"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
        <p
            v-if="error"
            :id="`${id}-error`"
            class="mt-1 px-0.5 text-xs text-destructive"
            role="alert"
        >
            {{ error }}
        </p>
    </div>
</template>
