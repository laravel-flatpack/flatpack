import { useMemo } from 'react';
import {
    type RegisteredFlatpackShortcut,
    useRegisterFlatpackShortcuts,
} from '@/contexts/flatpack-shortcuts-registry';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

type UseFlatpackRegisterActionShortcutsParams = {
    scope: string;
    actions: FlatpackListHeaderAction[];
    shortcutByActionId: Map<string, ParsedFlatpackShortcut>;
};

/**
 * Registers shortcuts for YAML-driven action rows so the keyboard shortcuts dialog
 * can show them consistently across form/list surfaces.
 */
export function useFlatpackRegisterActionShortcuts({
    scope,
    actions,
    shortcutByActionId,
}: UseFlatpackRegisterActionShortcutsParams): void {
    const registeredShortcuts = useMemo((): RegisteredFlatpackShortcut[] => {
        const rows: RegisteredFlatpackShortcut[] = [];
        for (const action of actions) {
            const shortcut = shortcutByActionId.get(action.id);
            if (!shortcut) {
                continue;
            }

            rows.push({
                id: `action:${action.id}`,
                description: action.label,
                shortcut,
            });
        }
        return rows;
    }, [actions, shortcutByActionId]);

    useRegisterFlatpackShortcuts(scope, registeredShortcuts);
}
