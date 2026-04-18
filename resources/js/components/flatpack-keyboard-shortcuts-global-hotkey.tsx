import { useEffect } from 'react';
import { useFlatpackKeyboardShortcutsDialog } from '@/contexts/flatpack-keyboard-shortcuts-dialog';
import { useIsMacPlatform } from '@/hooks/use-is-mac-platform';
import { FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY } from '@/lib/flatpack-shell-shortcuts';

function isKeyboardShortcutFormFieldTarget(
    target: EventTarget | null,
): boolean {
    if (!target || !(target instanceof HTMLElement)) {
        return false;
    }

    if (target.isContentEditable) {
        return true;
    }

    const field = target.closest('input, textarea, select');
    if (!field) {
        return false;
    }

    if (
        field instanceof HTMLTextAreaElement ||
        field instanceof HTMLSelectElement
    ) {
        return true;
    }

    if (field instanceof HTMLInputElement) {
        const t = field.type.toLowerCase();
        return !(
            t === 'button' ||
            t === 'submit' ||
            t === 'reset' ||
            t === 'checkbox' ||
            t === 'radio' ||
            t === 'file'
        );
    }

    return true;
}

/**
 * Shell shortcut: Cmd/Ctrl + {@link FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY} toggles the cheat sheet (see `flatpack-shell-shortcuts.ts`).
 */
export function FlatpackKeyboardShortcutsGlobalHotkey() {
    const { toggleShortcutsDialog } = useFlatpackKeyboardShortcutsDialog();
    const isMacPlatform = useIsMacPlatform();

    useEffect(() => {
        const lower =
            FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY.toLowerCase();
        const upper =
            FLATPACK_SHELL_KEYBOARD_SHORTCUTS_DIALOG_KEY.toUpperCase();

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== lower && event.key !== upper) {
                return;
            }

            const modPressed = isMacPlatform ? event.metaKey : event.ctrlKey;
            if (!modPressed) {
                return;
            }

            if (isMacPlatform && event.ctrlKey) {
                return;
            }
            if (!isMacPlatform && event.metaKey) {
                return;
            }

            if (event.shiftKey || event.altKey) {
                return;
            }

            if (isKeyboardShortcutFormFieldTarget(event.target)) {
                return;
            }

            event.preventDefault();
            toggleShortcutsDialog();
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [isMacPlatform, toggleShortcutsDialog]);

    return null;
}
