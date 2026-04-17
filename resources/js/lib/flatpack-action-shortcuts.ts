import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

const MODIFIER_TOKENS = new Set(['mod', 'shift', 'alt']);

type ShortcutModifier = 'mod' | 'shift' | 'alt';

export type ParsedFlatpackShortcut = {
    raw: string;
    normalized: string;
    actionId: string;
    key: string;
    modifiers: ReadonlySet<ShortcutModifier>;
};

type ParseSuccess = { ok: true; shortcut: ParsedFlatpackShortcut };
type ParseFailure = { ok: false; reason: string };

export function parseFlatpackActionShortcut(
    action: FlatpackListHeaderAction,
): ParseSuccess | ParseFailure | null {
    const raw = action.shortcut?.trim();
    if (!raw) {
        return null;
    }

    const tokens = raw
        .toLowerCase()
        .split('+')
        .map((token) => token.trim())
        .filter(Boolean);

    if (tokens.length < 2 || tokens.length > 3) {
        return {
            ok: false,
            reason: 'Shortcut must be "modifier+key" or "modifier+modifier+key".',
        };
    }

    const uniqueTokens = new Set(tokens);
    if (uniqueTokens.size !== tokens.length) {
        return { ok: false, reason: 'Shortcut contains duplicate keys.' };
    }

    const modifiers = tokens.filter((token) => MODIFIER_TOKENS.has(token));
    const keys = tokens.filter((token) => !MODIFIER_TOKENS.has(token));

    if (keys.length !== 1) {
        return {
            ok: false,
            reason: 'Shortcut must include exactly one non-modifier key.',
        };
    }

    if (modifiers.length !== tokens.length - 1) {
        return {
            ok: false,
            reason: 'Only mod, shift, and alt modifiers are supported.',
        };
    }

    if (modifiers.length < 1 || modifiers.length > 2) {
        return {
            ok: false,
            reason: 'Shortcut supports one or two modifiers.',
        };
    }

    if (!modifiers.includes('mod')) {
        return {
            ok: false,
            reason: 'Shortcut must include mod for safe form usage.',
        };
    }

    const modifierSet = new Set(modifiers as ShortcutModifier[]);
    const orderedModifiers: ShortcutModifier[] = (
        ['mod', 'shift', 'alt'] as const
    ).filter((modifier): modifier is ShortcutModifier =>
        modifierSet.has(modifier),
    );
    const key = normalizeKey(keys[0]);
    const normalized = [...orderedModifiers, key].join('+');

    return {
        ok: true,
        shortcut: {
            raw,
            normalized,
            actionId: action.id,
            key,
            modifiers: modifierSet,
        },
    };
}

export function shortcutMatchesKeyboardEvent(
    shortcut: ParsedFlatpackShortcut,
    event: KeyboardEvent,
    isMac: boolean,
): boolean {
    const expectedShift = shortcut.modifiers.has('shift');
    const expectedAlt = shortcut.modifiers.has('alt');
    const expectedMod = shortcut.modifiers.has('mod');

    if (event.shiftKey !== expectedShift) {
        return false;
    }

    if (event.altKey !== expectedAlt) {
        return false;
    }

    const actualModPressed = isMac ? event.metaKey : event.ctrlKey;
    if (actualModPressed !== expectedMod) {
        return false;
    }

    // Keep matching exact: extra Ctrl (mac) or Meta (non-mac) should not trigger.
    if (isMac && event.ctrlKey) {
        return false;
    }
    if (!isMac && event.metaKey) {
        return false;
    }

    return normalizeKey(event.key) === shortcut.key;
}

/** Single-line hint for inline UI (e.g. next to a button label). Uses ` + ` between parts on all platforms (e.g. ⌘+S, Ctrl+Shift+S). */
export function formatShortcutHintCompact(
    shortcut: ParsedFlatpackShortcut,
    isMac: boolean,
): string {
    const parts = formatShortcutForDisplay(shortcut, isMac);
    return parts.join(' + ');
}

export function formatShortcutForDisplay(
    shortcut: ParsedFlatpackShortcut,
    isMac: boolean,
): string[] {
    const parts: string[] = [];

    if (shortcut.modifiers.has('mod')) {
        parts.push(isMac ? '⌘' : 'Ctrl');
    }
    if (shortcut.modifiers.has('shift')) {
        parts.push(isMac ? '⇧' : 'Shift');
    }
    if (shortcut.modifiers.has('alt')) {
        parts.push(isMac ? '⌥' : 'Alt');
    }

    parts.push(formatKeyForDisplay(shortcut.key));

    return parts;
}

function normalizeKey(key: string): string {
    const normalized = key.trim().toLowerCase();
    if (normalized === ' ') {
        return 'space';
    }
    if (normalized === 'esc') {
        return 'escape';
    }
    return normalized;
}

function formatKeyForDisplay(key: string): string {
    if (key.length === 1) {
        return key.toUpperCase();
    }

    if (key === 'space') {
        return 'Space';
    }

    return key[0].toUpperCase() + key.slice(1);
}
