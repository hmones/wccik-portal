import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = ref<Appearance>('system');
const systemDark = ref(false);
let initialized = false;

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const dark =
        value === 'system'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
            : value === 'dark';
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
}

function readStorage(key: string): string | null {
    try {
        return window.localStorage.getItem(key);
    } catch {
        return null;
    }
}

function persistAppearance(value: Appearance): void {
    try {
        if (value === 'system') {
            window.localStorage.removeItem('appearance');
            window.localStorage.removeItem('appearance-system-theme');
        } else {
            window.localStorage.setItem('appearance', value);
            window.localStorage.setItem(
                'appearance-system-theme',
                systemDark.value ? 'dark' : 'light',
            );
        }
    } catch {
        // The switch still works when browser storage is unavailable.
    }

    document.cookie = `appearance=${value};path=/;max-age=31536000;SameSite=Lax`;
}

function updateAppearance(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    systemDark.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    appearance.value = value;
    persistAppearance(value);
    updateTheme(value);
}

function syncStoredAppearance(): void {
    systemDark.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    const stored = readStorage('appearance');
    const savedSystem = readStorage('appearance-system-theme');
    const system = systemDark.value ? 'dark' : 'light';
    const manual = stored === 'light' || stored === 'dark';

    // Detect computer theme changes that happened while the site was closed.
    updateAppearance(
        manual && (!savedSystem || savedSystem === system) ? stored : 'system',
    );
}

export function initializeTheme(): void {
    if (typeof window === 'undefined' || initialized) {
        return;
    }

    initialized = true;
    syncStoredAppearance();
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            updateAppearance('system');
        });
    window.addEventListener('storage', (event) => {
        if (
            event.key === null ||
            event.key === 'appearance' ||
            event.key === 'appearance-system-theme'
        ) {
            syncStoredAppearance();
        }
    });
}

export function useAppearance(): UseAppearanceReturn {
    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        appearance.value === 'system'
            ? systemDark.value
                ? 'dark'
                : 'light'
            : appearance.value,
    );

    return { appearance, resolvedAppearance, updateAppearance };
}
