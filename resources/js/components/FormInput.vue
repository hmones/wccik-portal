<script setup lang="ts">
import { computed, ref } from 'vue';

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
    blur: [event: FocusEvent];
}>();

const focused = ref(false);
const isActive = () =>
    focused.value ||
    (props.modelValue !== '' &&
        props.modelValue !== null &&
        props.modelValue !== undefined);

const describedBy = computed(() => {
    const ids: string[] = [];

    if (props.hint && !props.error) {
        ids.push(`${props.id}-hint`);
    }

    if (props.error) {
        ids.push(`${props.id}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});
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
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            placeholder=" "
            class="peer w-full rounded-lg border bg-background px-3 pt-5 pb-2 text-sm text-foreground transition-colors duration-150 placeholder:text-transparent focus:ring-2 focus:ring-offset-0 focus:outline-none"
            :class="
                error
                    ? 'border-destructive focus:border-destructive focus:ring-destructive/30'
                    : 'border-input hover:border-foreground/40 focus:border-brand-navy focus:ring-brand-navy/25 dark:focus:border-brand-teal dark:focus:ring-brand-teal/30'
            "
            @focus="focused = true"
            @blur="
                (e) => {
                    focused = false;
                    emit('blur', e);
                }
            "
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
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
        <p
            v-if="hint && !error"
            :id="`${id}-hint`"
            class="mt-1 px-0.5 text-xs text-muted-foreground"
        >
            {{ hint }}
        </p>
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
