<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslation } from '@/composables/useTranslation';

const { resolvedAppearance, updateAppearance } = useAppearance();
const { t } = useTranslation();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="resolvedAppearance === 'dark'"
        :aria-label="t('theme_dark_mode')"
        :title="
            t(
                resolvedAppearance === 'dark'
                    ? 'theme_switch_light'
                    : 'theme_switch_dark',
            )
        "
        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-brand-navy/20 bg-background text-brand-navy transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-brand-navy focus-visible:outline-none dark:border-brand-teal/40 dark:text-brand-teal dark:focus-visible:ring-brand-teal"
        @click="
            updateAppearance(resolvedAppearance === 'dark' ? 'light' : 'dark')
        "
    >
        <Sun
            v-if="resolvedAppearance === 'dark'"
            class="h-5 w-5"
            aria-hidden="true"
        />
        <Moon v-else class="h-5 w-5" aria-hidden="true" />
    </button>
</template>
