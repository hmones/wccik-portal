<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    modelValue: string | number | null | undefined;
    label: string;
    id: string;
    type?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    autocomplete?: string;
    placeholder?: string;
    min?: string | number;
    max?: string | number;
    dir?: string;
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
        <input
            :id="id"
            :type="type ?? 'text'"
            :value="modelValue ?? ''"
            :autocomplete="autocomplete"
            :min="min"
            :max="max"
            :dir="dir"
            placeholder=" "
            class="peer w-full rounded border bg-white px-3 pt-5 pb-2 text-sm text-black transition-colors duration-150 focus:outline-none"
            :class="
                error
                    ? 'border-red-500 focus:border-red-500'
                    : focused
                      ? 'border-2 border-[hsl(164,100%,29%)]'
                      : 'border-gray-400 hover:border-gray-600'
            "
            @focus="focused = true"
            @blur="focused = false"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
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
        <p v-if="hint && !error" class="mt-1 px-0.5 text-xs text-gray-500">
            {{ hint }}
        </p>
        <p v-if="error" class="mt-1 px-0.5 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
