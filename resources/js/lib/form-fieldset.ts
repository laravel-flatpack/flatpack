import type { FieldsetVariant, FormFieldProps } from '@/types/form-fields';
import type { SchemaFieldRenderEntry } from '@/types/schema-fields-renderer';

const VARIANT_NON_CARD: FieldsetVariant[] = ['minimal', 'plain', 'none'];

/** Resolved fieldset including explicit {@code variant} (defaults to {@code card}). */
export type ParsedFieldset = {
    label: string;
    icon?: string;
    variant: FieldsetVariant;
    /** Present when fieldset is collapsible (YAML {@code collapsed} boolean). */
    collapsed?: boolean;
};

export type FieldsetBlock =
    | { kind: 'plain'; entries: SchemaFieldRenderEntry[] }
    | {
          kind: 'fieldset';
          label: string;
          icon?: string;
          variant: FieldsetVariant;
          collapsed?: boolean;
          entries: SchemaFieldRenderEntry[];
      };

/** Matches PHP {@see FieldsetCanonicalizer} icon normalization for registry lookup. */
export function normalizeFieldsetIconKey(raw: string): string {
    return raw.trim().toLowerCase().replace(/_/g, '-');
}

function normalizeFieldsetVariant(raw: unknown): FieldsetVariant {
    if (typeof raw !== 'string') {
        return 'card';
    }
    const t = raw.trim().toLowerCase();
    if (t === '' || t === 'card') {
        return 'card';
    }
    if (VARIANT_NON_CARD.includes(t as FieldsetVariant)) {
        return t as FieldsetVariant;
    }

    return 'card';
}

/**
 * Resolves grouping label, optional icon, and variant from raw field definition (string, object, or PHP-normalized object).
 */
export function parseFieldsetFromField(
    field: FormFieldProps,
): ParsedFieldset | undefined {
    const raw = (field as Record<string, unknown>).fieldset;
    if (raw === undefined || raw === null) {
        return undefined;
    }

    if (typeof raw === 'string') {
        const trimmed = raw.trim();

        return trimmed === '' ? undefined : { label: trimmed, variant: 'card' };
    }

    if (typeof raw === 'object' && !Array.isArray(raw)) {
        const o = raw as Record<string, unknown>;
        const labelRaw = o.label;
        if (typeof labelRaw !== 'string') {
            return undefined;
        }
        const label = labelRaw.trim();
        if (label === '') {
            return undefined;
        }

        const iconRaw = o.icon;
        let icon: string | undefined;
        if (typeof iconRaw === 'string') {
            const iconNorm = normalizeFieldsetIconKey(iconRaw);
            if (iconNorm !== '') {
                icon = iconNorm;
            }
        }

        const variant = normalizeFieldsetVariant(o.variant);

        let collapsed: boolean | undefined;
        if (variant !== 'none' && typeof o.collapsed === 'boolean') {
            collapsed = o.collapsed;
        }

        const base = {
            label,
            variant,
            ...(collapsed !== undefined ? { collapsed } : {}),
        };

        return icon !== undefined ? { ...base, icon } : base;
    }

    return undefined;
}

/**
 * Partitions ordered field entries into plain runs and consecutive runs sharing the same fieldset identity
 * (label, variant, and collapsible flag).
 */
export function groupEntriesByFieldset(
    entries: SchemaFieldRenderEntry[],
): FieldsetBlock[] {
    const blocks: FieldsetBlock[] = [];
    let plainBuf: SchemaFieldRenderEntry[] = [];
    let fieldsetBuf: {
        label: string;
        icon?: string;
        variant: FieldsetVariant;
        collapsed?: boolean;
        entries: SchemaFieldRenderEntry[];
    } | null = null;

    const flushPlain = (): void => {
        if (plainBuf.length === 0) {
            return;
        }
        blocks.push({ kind: 'plain', entries: plainBuf });
        plainBuf = [];
    };

    const flushFieldset = (): void => {
        if (fieldsetBuf === null) {
            return;
        }
        blocks.push({
            kind: 'fieldset',
            label: fieldsetBuf.label,
            icon: fieldsetBuf.icon,
            variant: fieldsetBuf.variant,
            ...(fieldsetBuf.collapsed !== undefined
                ? { collapsed: fieldsetBuf.collapsed }
                : {}),
            entries: fieldsetBuf.entries,
        });
        fieldsetBuf = null;
    };

    for (const entry of entries) {
        const parsed = parseFieldsetFromField(entry.field);
        if (parsed === undefined) {
            flushFieldset();
            plainBuf.push(entry);
            continue;
        }

        flushPlain();
        const { label, icon, variant, collapsed } = parsed;

        if (
            fieldsetBuf !== null &&
            fieldsetBuf.label === label &&
            fieldsetBuf.variant === variant &&
            fieldsetBuf.collapsed === collapsed
        ) {
            fieldsetBuf.entries.push(entry);
            if (fieldsetBuf.icon === undefined && icon !== undefined) {
                fieldsetBuf.icon = icon;
            }
        } else {
            flushFieldset();
            fieldsetBuf = {
                label,
                icon,
                variant,
                ...(collapsed !== undefined ? { collapsed } : {}),
                entries: [entry],
            };
        }
    }

    flushPlain();
    flushFieldset();

    return blocks;
}
