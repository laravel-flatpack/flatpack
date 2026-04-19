import type {
    FlatpackActionVariant,
    FlatpackFormTableToolbarAction,
} from '@/types/data-table';

function normalizeToolbarButtonVariant(raw: unknown): FlatpackActionVariant {
    if (raw == null || typeof raw !== 'string') {
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
 * Accepts {@code actions} (preferred), legacy {@code toolbar_actions} / {@code toolbarActions},
 * array or map keyed by action id, for embedded form {@code type: table} fields (Create / Add, etc.).
 */
export function normalizeFormTableToolbarActionsInput(
    field: Record<string, unknown>,
): FlatpackFormTableToolbarAction[] | undefined {
    const raw = field.actions ?? field.toolbar_actions ?? field.toolbarActions;
    if (raw == null) {
        return undefined;
    }

    const mapOne = (
        id: string,
        def: Record<string, unknown>,
    ): FlatpackFormTableToolbarAction | null => {
        const label = typeof def.label === 'string' ? def.label.trim() : '';
        const action = typeof def.action === 'string' ? def.action.trim() : '';
        if (label === '' || action === '') {
            return null;
        }
        const icon = typeof def.icon === 'string' ? def.icon.trim() : '';
        const out: FlatpackFormTableToolbarAction = {
            id: id.trim() !== '' ? id.trim() : action,
            label,
            action,
            variant: normalizeToolbarButtonVariant(def.variant),
        };
        if (icon !== '') {
            out.icon = icon;
        }
        return out;
    };

    if (Array.isArray(raw)) {
        const out: FlatpackFormTableToolbarAction[] = [];
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
        const out: FlatpackFormTableToolbarAction[] = [];
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
