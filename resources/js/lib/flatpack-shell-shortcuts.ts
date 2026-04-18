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

/** Letter key for Mod+… “Show keyboard shortcuts”. Change this to remap the hotkey everywhere. */
export const FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY = '.';

const keyboardShortcutsDialogKeyNormalized =
    FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY.toLowerCase();

/**
 * Matches `FlatpackKeyboardShortcutsGlobalHotkey` (global Cmd/Ctrl + {@link FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY}).
 */
export const FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_SHORTCUT: ParsedFlatpackShortcut =
    {
        raw: `mod+${keyboardShortcutsDialogKeyNormalized}`,
        normalized: `mod+${keyboardShortcutsDialogKeyNormalized}`,
        actionId: 'shell-keyboard-shortcuts-dialog',
        key: keyboardShortcutsDialogKeyNormalized,
        modifiers: new Set(['mod']),
    };
