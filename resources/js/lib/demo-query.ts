import type { FormFieldProps } from '@/types/form-fields';

/** Query keys used only to pick which demo field to show — never merged into field props. */
const DEMO_ROUTING_QUERY_KEYS = new Set([
    'type',
    'demo',
    'field',
    'widget',
    'column',
]);

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

function coerceQueryParamValue(raw: string): unknown {
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

/**
 * Parse the current visit URL’s query string into flat string values (last value wins per key).
 * Use with Inertia’s `page.url` so demo params match the address bar even if server props differ.
 */
export function parseSearchParamsFromUrl(url: string): Record<string, string> {
    try {
        const base =
            typeof globalThis !== 'undefined' &&
            typeof (globalThis as unknown as { location?: { origin?: string } })
                .location?.origin === 'string'
                ? (globalThis as unknown as { location: { origin: string } })
                      .location.origin
                : 'http://localhost';
        const u = new URL(url, base);
        const out: Record<string, string> = {};
        for (const key of new Set(u.searchParams.keys())) {
            const values = u.searchParams.getAll(key);
            const last = values[values.length - 1];
            if (last !== undefined) {
                out[key] = last;
            }
        }
        return out;
    } catch {
        return {};
    }
}

/**
 * Reads the demo “which field” selector from merged query params.
 */
export function pickDemoComponentSelector(
    flat: Record<string, string>,
): string {
    for (const key of DEMO_ROUTING_QUERY_KEYS) {
        const v = flat[key];
        if (typeof v === 'string' && v.trim() !== '') {
            return v.trim();
        }
    }
    return '';
}

/** Parse `window.location.search` (or the same shape) into a flat map. */
export function parseLocationSearch(search: string): Record<string, string> {
    if (search === '' || search === '?') {
        return {};
    }
    const q = search.startsWith('?') ? search : `?${search}`;
    return parseSearchParamsFromUrl(`http://localhost${q}`);
}

/**
 * Merges demo query sources: address bar wins, then Inertia URL, then server props.
 */
export function mergeDemoFlatQuery(input: {
    query: Record<string, unknown>;
    inertiaUrl: string;
    locationSearch: string;
}): Record<string, string> {
    const fromServer = flattenDemoQuery(input.query);
    const fromInertiaUrl = parseSearchParamsFromUrl(input.inertiaUrl);
    const fromAddressBar = parseLocationSearch(input.locationSearch);
    return { ...fromServer, ...fromInertiaUrl, ...fromAddressBar };
}

/** Normalizes Inertia/Laravel query maps into flat string values (last value wins for arrays). */
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
 * Merges optional URL query fields into catalog props. Skips routing keys (`type`, `demo`, …)
 * and unknown keys so arbitrary query params do not become stray React props.
 */
export function mergeQueryOverridesIntoFormFieldProps(
    props: FormFieldProps,
    flat: Record<string, string>,
): FormFieldProps {
    const patch: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(flat)) {
        if (DEMO_ROUTING_QUERY_KEYS.has(k) || !DEMO_QUERY_FIELD_KEYS.has(k)) {
            continue;
        }
        patch[k] = coerceQueryParamValue(v);
    }
    return { ...props, ...patch, type: props.type } as FormFieldProps;
}

/**
 * When `showValue` is present in the merged query (e.g. `?showValue=true`), overrides the
 * catalog’s live-value panel. Omit the key to use the catalog default. Not merged into field props.
 */
export function resolveDemoShowValue(
    entry: { showValue: boolean },
    flat: Record<string, string>,
): boolean {
    if (!Object.hasOwn(flat, 'showValue')) {
        return entry.showValue;
    }
    const coerced = coerceQueryParamValue(flat.showValue);
    if (typeof coerced === 'boolean') {
        return coerced;
    }
    return entry.showValue;
}
