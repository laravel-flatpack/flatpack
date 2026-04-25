import type { FlatpackDataTableActionButton } from '@/types/data-table';

/**
 * Toolbar `action` values that open the blank draft row in the row detail drawer.
 * `create` and `add` share this path so the default UI is usable without a host
 * `onToolbarAction`. (BelongsToMany “attach existing” still needs ids in the payload;
 * authors may use custom actions for pure attach flows.)
 */
const EMBEDDED_TABLE_DRAFT_ROW_TOOLBAR_ACTIONS = new Set(['create', 'add']);

const BTM_ATTACH_TOOLBAR_ACTIONS = new Set(['attach']);

const DESTRUCTIVE_ACTIONS = new Set(['delete', 'remove', 'destroy']);
const DESTRUCTIVE_ICONS = new Set(['delete', 'trash', 'remove']);

function normalized(value: string | undefined): string {
    return value?.trim().toLowerCase() ?? '';
}

/**
 * Toolbar `action: attach` (case-insensitive): BelongsToMany “attach existing” path — same draft
 * drawer as create/add, with `attachExisting` body variant for optional `DataTable` `renderRowDrawerAttachBody`.
 * Saved rows should include the relation value key and optional `pivot` (see `RelationFormSynchronizer::syncBelongsToMany`).
 */
export function isEmbeddedTableBelongsToManyAttachToolbarAction(
    action: string | undefined,
): boolean {
    return action != null && BTM_ATTACH_TOOLBAR_ACTIONS.has(normalized(action));
}

/** Whether this toolbar `action` opens the blank draft row drawer (`create` or `add`). */
export function isEmbeddedTableCreateDraftToolbarAction(
    action: string | undefined,
): boolean {
    return (
        action != null &&
        EMBEDDED_TABLE_DRAFT_ROW_TOOLBAR_ACTIONS.has(normalized(action))
    );
}

/** Toolbar keyword `add` (also opens the draft drawer in the default stack). */
export function isEmbeddedTableAddToolbarAction(
    action: string | undefined,
): boolean {
    return normalized(action) === 'add';
}

export function isDestructiveActionKey(actionKey: string | undefined): boolean {
    return DESTRUCTIVE_ACTIONS.has(normalized(actionKey));
}

export function isDestructiveActionButton(
    actionSlug: string,
    cfg: FlatpackDataTableActionButton,
): boolean {
    return (
        isDestructiveActionKey(actionSlug) ||
        DESTRUCTIVE_ICONS.has(normalized(cfg.icon)) ||
        isDestructiveActionKey(cfg.action)
    );
}
