/**
 * Helpers for RelationRow-shaped form values ({@code id}, optional {@code pivot}, …).
 */

export function relationRowIdentityKey(schemaKey?: unknown): string {
    if (typeof schemaKey === 'string' && schemaKey.trim() !== '') {
        return schemaKey.trim();
    }

    return 'id';
}

/** Extract primitive ids from RelationRow[], legacy string[], or mixed. */
export function relationRowsToIds(
    value: unknown,
    identityKey = 'id',
): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    const out: string[] = [];

    for (const item of value) {
        if (typeof item === 'string' || typeof item === 'number') {
            const s = String(item).trim();
            if (s !== '') {
                out.push(s);
            }

            continue;
        }

        if (typeof item === 'object' && item !== null) {
            const row = item as Record<string, unknown>;
            const raw =
                identityKey in row ? row[identityKey] : (row.id ?? undefined);
            if (raw === null || raw === undefined) {
                continue;
            }

            const s = String(raw).trim();
            if (s !== '') {
                out.push(s);
            }
        }
    }

    return out;
}

/** Wrap selected ids as minimal RelationRow objects for submit. */
export function idsToRelationRows(
    ids: readonly string[],
    identityKey = 'id',
): Array<Record<string, string>> {
    return ids.map((id) => ({ [identityKey]: id }));
}
