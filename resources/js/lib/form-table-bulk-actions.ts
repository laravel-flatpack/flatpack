import { SUCCESS_REDIRECT_VALUES } from '@/lib/generated/composition-schema-keys';
import type {
    FlatpackActionVariant,
    FlatpackDataTableBulkAction,
    FlatpackSuccessRedirect,
} from '@/types/data-table';

function normalizeBulkSuccessRedirect(
    raw: unknown,
): FlatpackSuccessRedirect | undefined {
    if (raw === true) {
        return 'list';
    }
    if (typeof raw !== 'string') {
        return undefined;
    }
    const v = raw.trim();
    if (v.toLowerCase() === 'true') {
        return 'list';
    }
    return (SUCCESS_REDIRECT_VALUES as readonly string[]).includes(v)
        ? (v as FlatpackSuccessRedirect)
        : undefined;
}

function normalizeBulkVariant(raw: unknown): FlatpackActionVariant {
    if (typeof raw !== 'string') {
        return 'outline';
    }
    const value = raw.trim();
    if (value === 'primary') {
        return 'default';
    }
    if (
        value === 'default' ||
        value === 'destructive' ||
        value === 'outline' ||
        value === 'secondary' ||
        value === 'ghost' ||
        value === 'link'
    ) {
        return value;
    }
    return 'outline';
}

/**
 * Accepts {@code bulkActions} or list-style {@code bulk_actions}, array or map keyed by action id,
 * for embedded form {@code type: table} fields.
 */
export function normalizeFormTableBulkActionsInput(
    field: Record<string, unknown>,
): FlatpackDataTableBulkAction[] | undefined {
    const raw = field.bulkActions ?? field.bulk_actions;
    if (raw == null) {
        return undefined;
    }

    const mapOne = (
        id: string,
        def: Record<string, unknown>,
    ): FlatpackDataTableBulkAction | null => {
        const label = typeof def.label === 'string' ? def.label.trim() : '';
        const action = typeof def.action === 'string' ? def.action.trim() : '';
        if (label === '' || action === '') {
            return null;
        }
        const icon = typeof def.icon === 'string' ? def.icon.trim() : '';
        const out: FlatpackDataTableBulkAction = {
            id: id.trim() !== '' ? id.trim() : action,
            label,
            action,
            variant: normalizeBulkVariant(def.variant),
        };
        if (icon !== '') {
            out.icon = icon;
        }
        if ((def.confirm ?? null) === true) {
            out.confirm = true;
        }
        if (
            typeof def.success_message === 'string' &&
            def.success_message.trim() !== ''
        ) {
            out.success_message = def.success_message.trim();
        }
        const sr = normalizeBulkSuccessRedirect(def.success_redirect);
        if (sr !== undefined) {
            out.success_redirect = sr;
        }
        return out;
    };

    if (Array.isArray(raw)) {
        const out: FlatpackDataTableBulkAction[] = [];
        raw.forEach((item, index) => {
            if (item == null || typeof item !== 'object') {
                return;
            }
            const rec = item as Record<string, unknown>;
            const idRaw = rec.id;
            const id =
                typeof idRaw === 'string' && idRaw.trim() !== ''
                    ? idRaw.trim()
                    : typeof rec.action === 'string' && rec.action.trim() !== ''
                      ? rec.action.trim()
                      : String(index);
            const mapped = mapOne(id, rec);
            if (mapped !== null) {
                out.push({ ...mapped, id });
            }
        });
        return out.length > 0 ? out : undefined;
    }

    if (typeof raw === 'object') {
        const out: FlatpackDataTableBulkAction[] = [];
        for (const [key, def] of Object.entries(
            raw as Record<string, unknown>,
        )) {
            if (def == null || typeof def !== 'object') {
                continue;
            }
            const mapped = mapOne(key, def as Record<string, unknown>);
            if (mapped !== null) {
                out.push(mapped);
            }
        }
        return out.length > 0 ? out : undefined;
    }

    return undefined;
}
