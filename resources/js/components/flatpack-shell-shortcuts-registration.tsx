import {
    type RegisteredFlatpackShortcut,
    useRegisterFlatpackShortcuts,
} from '@/contexts/flatpack-shortcuts-registry';
import {
    FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_SHORTCUT,
    FLATPACK_SHELL_SIDEBAR_TOGGLE_SHORTCUT,
} from '@/lib/flatpack-shell-shortcuts';

const SHELL_SHORTCUT_ROWS: RegisteredFlatpackShortcut[] = [
    {
        id: 'shell:toggle-sidebar',
        description: 'Toggle sidebar',
        shortcut: FLATPACK_SHELL_SIDEBAR_TOGGLE_SHORTCUT,
    },
    {
        id: 'shell:keyboard-shortcuts',
        description: 'Show keyboard shortcuts',
        shortcut: FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_SHORTCUT,
    },
];

/**
 * Registers global shell shortcuts for the keyboard shortcuts dialog (they are not driven by YAML).
 */
export function FlatpackShellShortcutsRegistration() {
    useRegisterFlatpackShortcuts('flatpack-shell', SHELL_SHORTCUT_ROWS);
    return null;
}
