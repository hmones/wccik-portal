<script setup lang="ts">
import { ref } from 'vue';

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
</script>

<template>
    <div class="relative mt-1">
        <select
            :id="id"
            :value="modelValue ?? ''"
            class="peer w-full appearance-none rounded border bg-white px-3 pt-5 pb-2 text-sm text-black transition-colors duration-150 focus:outline-none"
            :class="
                error
                    ? 'border-red-500 focus:border-red-500'
                    : focused
                      ? 'border-2 border-[hsl(164,100%,29%)]'
                      : 'border-gray-400 hover:border-gray-600'
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
            class="pointer-events-none absolute left-3 bg-white px-0.5 text-sm transition-all duration-150"
            :class="[
                isActive()
                    ? '-top-2 text-xs ' +
                      (error
                          ? 'text-red-500'
                          : focused
                            ? 'text-[hsl(164,100%,29%)]'
                            : 'text-gray-500')
                    : 'top-4 text-gray-500',
            ]"
        >
            {{ label
            }}<span v-if="required" class="ml-0.5 text-red-500">*</span>
        </label>
        <!-- Chevron icon -->
        <div
            class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
        <p v-if="error" class="mt-1 px-0.5 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
