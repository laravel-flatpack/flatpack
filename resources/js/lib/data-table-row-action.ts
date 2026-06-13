import { interpolateRowPlaceholders } from '@/lib/data-table-utils';
import type {
    DataTableRowActionPayload,
    FlatpackDataTableActionButton,
} from '@/types/data-table';

export function stableRowActionButtonKey(
    cfg: FlatpackDataTableActionButton,
): string {
    return [
        cfg.action ?? '',
        cfg.label,
        cfg.href ?? '',
        cfg.variant ?? '',
        cfg.icon ?? '',
    ].join('|');
}

export function resolveRowActionInterpolatedHref(
    button: FlatpackDataTableActionButton,
    row: Record<string, unknown>,
): string {
    const template = button.href ?? '';
    if (template === '') {
        return '';
    }
    return interpolateRowPlaceholders(template, row);
}

export function isExternalRowActionHref(href: string): boolean {
    return /^https?:\/\//i.test(href);
}

export function buildDataTableRowActionPayload(
    button: FlatpackDataTableActionButton,
    row: Record<string, unknown>,
): DataTableRowActionPayload {
    const slug = button.action ?? button.label ?? '';
    return {
        action:
            typeof button.action === 'string' ? button.action : String(slug),
        row,
        button,
    };
}
