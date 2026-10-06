import { onBeforeUnmount, ref, watch } from 'vue';
import type { Ref } from 'vue';

export type AutosaveStatus = 'idle' | 'typing' | 'saving' | 'saved' | 'error';

type AutosaveOptions<T> = {
    data: Ref<T>;
    save: (payload: T) => Promise<void>;
    debounceMs?: number;
};

/**
 * Debounced autosave for form state. Call `flushNow()` on blur to force an
 * immediate save; the watcher handles the idle-timeout path.
 */
export function useAutosave<T>({
    data,
    save,
    debounceMs = 5000,
}: AutosaveOptions<T>) {
    const status = ref<AutosaveStatus>('idle');
    const lastSavedAt = ref<Date | null>(null);
    let timer: ReturnType<typeof setTimeout> | null = null;
    let inFlight = false;
    let dirty = false;

    function schedule() {
        dirty = true;
        status.value = 'typing';

        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            void run();
        }, debounceMs);
    }

    async function run() {
        if (!dirty || inFlight) {
            return;
        }

        if (timer) {
            clearTimeout(timer);
            timer = null;
        }

        inFlight = true;
        dirty = false;
        status.value = 'saving';

        try {
            await save(data.value);
            status.value = 'saved';
            lastSavedAt.value = new Date();
        } catch {
            status.value = 'error';
            dirty = true;
        } finally {
            inFlight = false;
        }
    }

    async function flushNow() {
        if (dirty) {
            await run();
        }
    }

    watch(
        () => data.value,
        () => schedule(),
        { deep: true },
    );

    onBeforeUnmount(() => {
        if (timer) {
            clearTimeout(timer);
        }
    });

    return { status, lastSavedAt, flushNow };
}
