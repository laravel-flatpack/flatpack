import { useEffect } from 'react';
import { useFlatpackKeyboardShortcutsDialog } from '@/contexts/flatpack-keyboard-shortcuts-dialog';
import { useIsMacPlatform } from '@/hooks/use-is-mac-platform';

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
 * Shell shortcut: Cmd/Ctrl+K toggles the keyboard shortcuts cheat sheet (see `flatpack-shell-shortcuts.ts`).
 */
export function FlatpackKeyboardShortcutsGlobalHotkey() {
    const { toggleShortcutsDialog } = useFlatpackKeyboardShortcutsDialog();
    const isMacPlatform = useIsMacPlatform();

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'k' && event.key !== 'K') {
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
