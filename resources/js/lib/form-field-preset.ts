import { serializeFieldValue } from '@/lib/form-page-field-values';
import type { FormFieldEntry } from '@/lib/form-schema';
import { FORM_PRESET_TYPES } from '@/lib/generated/composition-schema-keys';
import type {
    FormFieldInputFormat,
    FormFieldPreset,
    FormFieldPresetType,
    FormFieldProps,
} from '@/types/form-fields';

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export function parsePreset(field: FormFieldProps): FormFieldPreset | null {
    const raw = (field as Record<string, unknown>).preset;
    if (!isRecord(raw)) {
        return null;
    }

    const sourceField = raw.field;
    const presetType = raw.type;
    if (typeof sourceField !== 'string' || sourceField.trim() === '') {
        return null;
    }
    if (
        typeof presetType !== 'string' ||
        !(FORM_PRESET_TYPES as readonly string[]).includes(presetType)
    ) {
        return null;
    }

    if (field.type !== 'text' && field.type !== 'textarea') {
        return null;
    }

    return {
        field: sourceField.trim(),
        type: presetType as FormFieldPresetType,
    };
}

export function isPresetDestinationValueEmpty(value: unknown): boolean {
    if (value === null || value === undefined) {
        return true;
    }
    if (typeof value === 'string') {
        return value.trim() === '';
    }
    return false;
}

function slugifySegment(input: string): string {
    return input
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+|-+$/g, '');
}

function toPresetString(source: unknown): string {
    if (source === null || source === undefined) {
        return '';
    }
    return typeof source === 'string' ? source : String(source);
}

function formatCamelPreset(raw: string): string {
    const parts = raw
        .trim()
        .split(/[^a-zA-Z0-9]+/)
        .filter((p) => p.length > 0);
    if (parts.length === 0) {
        return '';
    }
    const [first, ...rest] = parts;
    return (
        first.charAt(0).toLowerCase() +
        first.slice(1).toLowerCase() +
        rest
            .map(
                (word) =>
                    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase(),
            )
            .join('')
    );
}

function formatFilePreset(raw: string): string {
    const trimmed = raw.trim();
    if (trimmed === '') {
        return '';
    }
    return trimmed
        .replace(/\s+/g, '-')
        .replace(/[/\\?%*:|"<>]/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+|-+$/g, '');
}

function formatSlugInput(raw: string): string {
    return raw
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-{2,}/g, '-');
}

const presetFormatters: Record<FormFieldPresetType, (raw: string) => string> = {
    exact: (raw) => raw,
    slug: (raw) => slugifySegment(raw),
    url: (raw) => {
        const slug = slugifySegment(raw);
        return slug === '' ? '' : `/${slug}`;
    },
    camel: (raw) => formatCamelPreset(raw),
    file: (raw) => formatFilePreset(raw),
};

/**
 * Converts a source value for a destination text field according to the preset kind.
 */
export function formatPresetValue(
    source: unknown,
    presetType: FormFieldPresetType,
): string {
    const raw = toPresetString(source);
    return presetFormatters[presetType](raw);
}

export function formatInputValue(
    source: unknown,
    format: FormFieldInputFormat,
): string {
    const raw = toPresetString(source);
    if (format === 'slug') {
        return formatSlugInput(raw);
    }
    if (format === 'url') {
        const slug = formatSlugInput(raw);
        return slug === '' ? '' : `/${slug.replace(/^\/+/, '')}`;
    }
    return formatPresetValue(raw, format);
}

export function formatInputValueOnBlur(
    source: unknown,
    format: FormFieldInputFormat,
): string {
    if (format === 'slug') {
        return formatPresetValue(source, 'slug');
    }
    if (format === 'url') {
        return formatPresetValue(source, 'url');
    }
    return formatInputValue(source, format);
}

export type PresetEdge = {
    destId: string;
    preset: FormFieldPreset;
};

/**
 * Maps source field id -> edges that copy from that source into a destination field.
 */
export function buildPresetEdgesBySourceId(
    fields: FormFieldEntry[],
): Map<string, PresetEdge[]> {
    const ids = new Set(fields.map(({ id }) => id));
    const bySource = new Map<string, PresetEdge[]>();

    for (const { id: destId, field } of fields) {
        const preset = parsePreset(field);
        if (preset === null) {
            continue;
        }
        if (!ids.has(preset.field)) {
            continue;
        }
        if (preset.field === destId) {
            continue;
        }

        const list = bySource.get(preset.field) ?? [];
        list.push({ destId, preset });
        bySource.set(preset.field, list);
    }

    return bySource;
}

/**
 * Destinations that were non-empty on initial load do not receive auto preset (edit mode protection).
 */
export function buildInitialPresetBlockedIds(
    fields: FormFieldEntry[],
    initialValues: Record<string, unknown>,
): Set<string> {
    const blocked = new Set<string>();
    for (const { id, field } of fields) {
        if (parsePreset(field) === null) {
            continue;
        }
        if (!isPresetDestinationValueEmpty(initialValues[id])) {
            blocked.add(id);
        }
    }
    return blocked;
}

export function applyPresetCascade(params: {
    changedSourceId: string;
    values: Record<string, unknown>;
    fieldsById: Map<string, FormFieldProps>;
    edgesBySource: Map<string, PresetEdge[]>;
    userTouchedDest: ReadonlySet<string>;
    initialBlockedDest: ReadonlySet<string>;
}): Record<string, unknown> {
    const {
        changedSourceId,
        values,
        fieldsById,
        edgesBySource,
        userTouchedDest,
        initialBlockedDest,
    } = params;

    const queue: string[] = [changedSourceId];
    const seenSources = new Set<string>();
    let next: Record<string, unknown> = values;

    while (queue.length > 0) {
        const sourceId = queue.shift();
        if (sourceId === undefined) {
            break;
        }
        if (seenSources.has(sourceId)) {
            continue;
        }
        seenSources.add(sourceId);

        const edges = edgesBySource.get(sourceId);
        if (edges === undefined || edges.length === 0) {
            continue;
        }

        for (const { destId, preset } of edges) {
            if (userTouchedDest.has(destId) || initialBlockedDest.has(destId)) {
                continue;
            }

            const destField = fieldsById.get(destId);
            if (destField === undefined) {
                continue;
            }

            const sourceValue = next[sourceId];
            const formatted = formatPresetValue(sourceValue, preset.type);
            const serialized = serializeFieldValue(destField, formatted);

            if (Object.is(next[destId], serialized)) {
                continue;
            }

            next = {
                ...next,
                [destId]: serialized,
            };
            queue.push(destId);
        }
    }

    return next;
}
