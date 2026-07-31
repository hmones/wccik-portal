import { usePage } from '@inertiajs/vue3';

export function useTranslation() {
    const page = usePage();

    function t(key: string, replacements: Record<string, string> = {}): string {
        const translations = page.props.translations as Record<string, string>;
        let value = translations?.[key] ?? key;

        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(`:${placeholder}`, replacement);
        }

        return value;
    }

    const locale = () => page.props.locale as string;
    const isUrdu = () => locale() === 'ur';

    return { t, locale, isUrdu };
}
