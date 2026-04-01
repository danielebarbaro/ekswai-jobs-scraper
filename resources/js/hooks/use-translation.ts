import { usePage } from '@inertiajs/react';

interface TranslationProps {
    locale: string;
    translations: Record<string, unknown>;
}

function getNestedValue(obj: Record<string, unknown>, path: string): string {
    const keys = path.split('.');
    let current: unknown = obj;

    for (const key of keys) {
        if (current === null || current === undefined || typeof current !== 'object') {
            return path;
        }
        current = (current as Record<string, unknown>)[key];
    }

    return typeof current === 'string' ? current : path;
}

export function useTranslation() {
    const { locale, translations } = usePage<TranslationProps>().props;

    const t = (key: string): string => {
        return getNestedValue(translations as Record<string, unknown>, key);
    };

    return { t, locale };
}
