import { useEffect, useMemo } from 'react';
import {
    type ParsedFlatpackShortcut,
    parseFlatpackActionShortcut,
    shortcutMatchesKeyboardEvent,
} from '@/lib/flatpack-action-shortcuts';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type UseFlatpackActionShortcutsParams = {
    actions: FlatpackListHeaderAction[];
    disabled?: boolean;
};

type UseFlatpackActionShortcutsResult = {
    isMacPlatform: boolean;
    shortcutByActionId: Map<string, ParsedFlatpackShortcut>;
};

export function useFlatpackActionShortcuts({
    actions,
    disabled = false,
}: UseFlatpackActionShortcutsParams): UseFlatpackActionShortcutsResult {
    const isMacPlatform = useMemo(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        return window.navigator.platform.toLowerCase().includes('mac');
    }, []);

    const shortcutByActionId = useMemo(() => {
        const byActionId = new Map<string, ParsedFlatpackShortcut>();
        const ownerByShortcut = new Map<string, string>();

        actions.forEach((action) => {
            const parsed = parseFlatpackActionShortcut(action);
            if (!parsed) {
                return;
            }

            if (!parsed.ok) {
                console.warn(
                    `[flatpack] Ignoring shortcut "${action.shortcut}" for action "${action.id}": ${parsed.reason}`,
                );
                return;
            }

            const previousOwner = ownerByShortcut.get(
                parsed.shortcut.normalized,
            );
            if (previousOwner) {
                console.warn(
                    `[flatpack] Duplicate shortcut "${parsed.shortcut.raw}" on actions "${previousOwner}" and "${action.id}". Ignoring the duplicate.`,
                );
                return;
            }

            ownerByShortcut.set(parsed.shortcut.normalized, action.id);
            byActionId.set(action.id, parsed.shortcut);
        });

        return byActionId;
    }, [actions]);

    useEffect(() => {
        if (disabled || shortcutByActionId.size === 0) {
            return;
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            const hasOpenDialog = document.querySelector(
                '[role="dialog"][data-state="open"]',
            );
            if (hasOpenDialog) {
                return;
            }

            for (const shortcut of shortcutByActionId.values()) {
                if (
                    !shortcutMatchesKeyboardEvent(
                        shortcut,
                        event,
                        isMacPlatform,
                    )
                ) {
                    continue;
                }

                const trigger = Array.from(
                    document.querySelectorAll<HTMLElement>(
                        '[data-flatpack-action-id]',
                    ),
                ).find(
                    (element) =>
                        element.getAttribute('data-flatpack-action-id') ===
                        shortcut.actionId,
                );

                if (!trigger || trigger.matches(':disabled')) {
                    return;
                }

                event.preventDefault();
                trigger.click();
                return;
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [disabled, isMacPlatform, shortcutByActionId]);

    return { isMacPlatform, shortcutByActionId };
}
