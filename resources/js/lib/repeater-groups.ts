/**
 * Parses repeater `groups` from normalized form schema (inline map or array).
 * A string value means an unresolved YAML path — the UI cannot render until the
 * composition loader inlines groups on the server.
 */

export type ParsedRepeaterGroup = {
    /** Stable id stored on each row under {@see groupKeyFrom} */
    id: string;
    label: string;
    icon?: string;
    fields: Record<string, Record<string, unknown>>;
};

export type ParseRepeaterGroupsResult =
    | { status: 'inline'; groups: ParsedRepeaterGroup[] }
    | { status: 'yaml-path'; path: string }
    | { status: 'invalid' };

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/**
 * Returns inline group definitions, or why the payload cannot be used in the browser.
 */
export function parseRepeaterGroups(
    groups: unknown,
): ParseRepeaterGroupsResult {
    if (groups == null) {
        return { status: 'invalid' };
    }
    if (typeof groups === 'string') {
        const path = groups.trim();
        if (path === '') {
            return { status: 'invalid' };
        }
        return { status: 'yaml-path', path };
    }
    if (Array.isArray(groups)) {
        const out: ParsedRepeaterGroup[] = [];
        groups.forEach((item, index) => {
            if (!isRecord(item)) {
                return;
            }
            const fieldsRaw = item.fields;
            if (!isRecord(fieldsRaw) || Object.keys(fieldsRaw).length === 0) {
                return;
            }
            const fields = fieldsRaw as Record<string, Record<string, unknown>>;
            const label =
                typeof item.label === 'string' ? item.label.trim() : '';
            if (label === '') {
                return;
            }
            const keyRaw = typeof item.key === 'string' ? item.key.trim() : '';
            const id =
                keyRaw !== ''
                    ? keyRaw
                    : `group-${index}-${label.replace(/\s+/g, '-').toLowerCase()}`;
            const icon =
                typeof item.icon === 'string' && item.icon.trim() !== ''
                    ? item.icon.trim()
                    : undefined;
            out.push({ id, label, icon, fields });
        });
        if (out.length === 0) {
            return { status: 'invalid' };
        }
        const seen = new Set<string>();
        for (const g of out) {
            if (seen.has(g.id)) {
                return { status: 'invalid' };
            }
            seen.add(g.id);
        }
        return { status: 'inline', groups: out };
    }
    if (!isRecord(groups)) {
        return { status: 'invalid' };
    }
    const keys = Object.keys(groups);
    if (keys.length === 0) {
        return { status: 'invalid' };
    }
    const out: ParsedRepeaterGroup[] = [];
    for (const mapKey of keys) {
        const item = groups[mapKey];
        if (!isRecord(item)) {
            continue;
        }
        const fieldsRaw = item.fields;
        if (!isRecord(fieldsRaw) || Object.keys(fieldsRaw).length === 0) {
            continue;
        }
        const fields = fieldsRaw as Record<string, Record<string, unknown>>;
        const label = typeof item.label === 'string' ? item.label.trim() : '';
        if (label === '') {
            continue;
        }
        const keyRaw = typeof item.key === 'string' ? item.key.trim() : '';
        const id = keyRaw !== '' ? keyRaw : mapKey;
        const icon =
            typeof item.icon === 'string' && item.icon.trim() !== ''
                ? item.icon.trim()
                : undefined;
        out.push({ id, label, icon, fields });
    }
    if (out.length === 0) {
        return { status: 'invalid' };
    }
    const seen = new Set<string>();
    for (const g of out) {
        if (seen.has(g.id)) {
            return { status: 'invalid' };
        }
        seen.add(g.id);
    }
    return { status: 'inline', groups: out };
}

export function mapRepeaterGroupsById(
    groups: ParsedRepeaterGroup[],
): Map<string, ParsedRepeaterGroup> {
    return new Map(groups.map((g) => [g.id, g]));
}
