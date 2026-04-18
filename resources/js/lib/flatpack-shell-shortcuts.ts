import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';

/**
 * Matches `SidebarProvider` in `components/ui/sidebar.tsx` (global Cmd/Ctrl+B).
 */
export const FLATPACK_SHELL_SIDEBAR_TOGGLE_SHORTCUT: ParsedFlatpackShortcut = {
    raw: 'mod+b',
    normalized: 'mod+b',
    actionId: 'shell-sidebar-toggle',
    key: 'b',
    modifiers: new Set(['mod']),
};

/**
 * Matches `FlatpackKeyboardShortcutsGlobalHotkey` (global Cmd/Ctrl+K).
 */
export const FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_SHORTCUT: ParsedFlatpackShortcut =
    {
        raw: 'mod+k',
        normalized: 'mod+k',
        actionId: 'shell-keyboard-shortcuts-dialog',
        key: 'k',
        modifiers: new Set(['mod']),
    };
