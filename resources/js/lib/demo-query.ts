import type { FormFieldProps } from '@/types/form-fields';

/** Keys allowed to merge from the URL into {@link FormFieldProps} (`type` is always ignored). */
const DEMO_QUERY_FIELD_KEYS = new Set([
    'label',
    'placeholder',
    'helperText',
    'defaultValue',
    'options',
    'multiple',
    'defaultChecked',
    'showFixedToolbar',
    'dateLabel',
    'timeLabel',
    'datePlaceholder',
    'timeDefaultValue',
]);

function safeDecodeURIComponent(raw: string): string {
    try {
        return decodeURIComponent(raw.replaceAll('+', ' '));
    } catch {
        return raw;
    }
}

export function coerceQueryParamValue(raw: string): unknown {
    const t = raw.trim();
    const lower = t.toLowerCase();
    if (lower === 'true' || lower === '1') {
        return true;
    }
    if (lower === 'false' || lower === '0') {
        return false;
    }
    if (t.startsWith('[') || t.startsWith('{')) {
        try {
            return JSON.parse(t) as unknown;
        } catch {
            return raw;
        }
    }
    return safeDecodeURIComponent(raw);
}

/** Normalizes Inertia/Laravel `query` into flat string values (last value wins for arrays). */
export function flattenDemoQuery(
    query: Record<string, unknown> | undefined | null,
): Record<string, string> {
    if (query == null || typeof query !== 'object' || Array.isArray(query)) {
        return {};
    }
    const out: Record<string, string> = {};
    for (const [k, v] of Object.entries(query)) {
        if (k === '') {
            continue;
        }
        if (v == null) {
            continue;
        }
        if (Array.isArray(v)) {
            const last = v[v.length - 1];
            if (last == null) {
                continue;
            }
            out[k] = String(last);
        } else {
            out[k] = String(v);
        }
    }
    return out;
}

/**
 * Merges optional URL query fields into catalog props. Skips `type` (routing only) and
 * unknown keys so arbitrary query params do not become stray React props.
 */
export function mergeQueryOverridesIntoFormFieldProps(
    props: FormFieldProps,
    flat: Record<string, string>,
): FormFieldProps {
    const patch: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(flat)) {
        if (k === 'type' || !DEMO_QUERY_FIELD_KEYS.has(k)) {
            continue;
        }
        patch[k] = coerceQueryParamValue(v);
    }
    return { ...props, ...patch, type: props.type } as FormFieldProps;
}
