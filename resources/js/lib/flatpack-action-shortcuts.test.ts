import { describe, expect, it } from 'vitest';
import {
    formatShortcutForDisplay,
    formatShortcutHintCompact,
    parseFlatpackActionShortcut,
    shortcutMatchesKeyboardEvent,
} from '@/lib/flatpack-action-shortcuts';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

function headerAction(
    partial: Pick<FlatpackListHeaderAction, 'id'> &
        Partial<FlatpackListHeaderAction>,
): FlatpackListHeaderAction {
    return {
        id: partial.id,
        label: partial.label ?? 'Label',
        action: partial.action ?? 'save',
        shortcut: partial.shortcut,
    };
}

describe('parseFlatpackActionShortcut', () => {
    it('returns null when shortcut is missing, empty, or whitespace-only', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'a', shortcut: undefined }),
            ),
        ).toBeNull();
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'a', shortcut: '' }),
            ),
        ).toBeNull();
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'a', shortcut: '   \t' }),
            ),
        ).toBeNull();
    });

    it('parses mod+key (case-insensitive) and preserves action id', () => {
        const result = parseFlatpackActionShortcut(
            headerAction({
                id: 'publish',
                shortcut: '  MOD+S  ',
            }),
        );
        expect(result).toMatchObject({
            ok: true,
            shortcut: {
                raw: 'MOD+S',
                normalized: 'mod+s',
                actionId: 'publish',
                key: 's',
                modifiers: new Set(['mod'] as const),
            },
        });
    });

    it('orders modifiers as mod, shift, alt regardless of input order', () => {
        const result = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'shift+mod+s' }),
        );
        expect(result).toMatchObject({
            ok: true,
            shortcut: {
                normalized: 'mod+shift+s',
                key: 's',
                modifiers: new Set(['mod', 'shift'] as const),
            },
        });
    });

    it('parses mod+alt+key', () => {
        const result = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+alt+e' }),
        );
        expect(result).toMatchObject({
            ok: true,
            shortcut: {
                normalized: 'mod+alt+e',
                key: 'e',
            },
        });
    });

    it('maps esc token to escape in the normalized key', () => {
        const result = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+esc' }),
        );
        expect(result).toMatchObject({
            ok: true,
            shortcut: { normalized: 'mod+escape', key: 'escape' },
        });
    });

    it('accepts the space key as the literal token space', () => {
        const result = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+space' }),
        );
        expect(result).toMatchObject({
            ok: true,
            shortcut: { normalized: 'mod+space', key: 'space' },
        });
    });

    it('fails when token count is not two or three', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 's' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut must be "modifier+key" or "modifier+modifier+key".',
        });
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 'mod+shift+alt+s' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut must be "modifier+key" or "modifier+modifier+key".',
        });
    });

    it('fails on duplicate tokens', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 'mod+mod+s' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut contains duplicate keys.',
        });
    });

    it('fails when there is not exactly one non-modifier key', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 'mod+a+b' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut must include exactly one non-modifier key.',
        });
    });

    it('fails when a token is not a supported modifier or the key', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 'mod+ctrl+s' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut must include exactly one non-modifier key.',
        });
    });

    it('fails when mod is omitted', () => {
        expect(
            parseFlatpackActionShortcut(
                headerAction({ id: 'x', shortcut: 'shift+s' }),
            ),
        ).toEqual({
            ok: false,
            reason: 'Shortcut must include mod for safe form usage.',
        });
    });
});

describe('shortcutMatchesKeyboardEvent', () => {
    const modS = parseFlatpackActionShortcut(
        headerAction({ id: 'save', shortcut: 'mod+s' }),
    );
    if (!modS?.ok) {
        throw new Error('fixture expected to parse');
    }

    it('matches Meta+key on mac and Ctrl+key on non-mac', () => {
        expect(
            shortcutMatchesKeyboardEvent(
                modS.shortcut,
                new KeyboardEvent('keydown', { key: 's', metaKey: true }),
                true,
            ),
        ).toBe(true);
        expect(
            shortcutMatchesKeyboardEvent(
                modS.shortcut,
                new KeyboardEvent('keydown', { key: 's', ctrlKey: true }),
                false,
            ),
        ).toBe(true);
    });

    it('requires shift when present in the shortcut', () => {
        const parsed = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+shift+e' }),
        );
        if (!parsed?.ok) {
            throw new Error('fixture expected to parse');
        }

        expect(
            shortcutMatchesKeyboardEvent(
                parsed.shortcut,
                new KeyboardEvent('keydown', {
                    key: 'e',
                    metaKey: true,
                    shiftKey: true,
                }),
                true,
            ),
        ).toBe(true);

        expect(
            shortcutMatchesKeyboardEvent(
                parsed.shortcut,
                new KeyboardEvent('keydown', {
                    key: 'e',
                    metaKey: true,
                    shiftKey: false,
                }),
                true,
            ),
        ).toBe(false);
    });

    it('requires alt when present in the shortcut', () => {
        const parsed = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+alt+e' }),
        );
        if (!parsed?.ok) {
            throw new Error('fixture expected to parse');
        }

        expect(
            shortcutMatchesKeyboardEvent(
                parsed.shortcut,
                new KeyboardEvent('keydown', {
                    key: 'e',
                    metaKey: true,
                    altKey: true,
                }),
                true,
            ),
        ).toBe(true);

        expect(
            shortcutMatchesKeyboardEvent(
                parsed.shortcut,
                new KeyboardEvent('keydown', {
                    key: 'e',
                    metaKey: true,
                    altKey: false,
                }),
                true,
            ),
        ).toBe(false);
    });

    it('does not match when the key differs', () => {
        expect(
            shortcutMatchesKeyboardEvent(
                modS.shortcut,
                new KeyboardEvent('keydown', { key: 'a', metaKey: true }),
                true,
            ),
        ).toBe(false);
    });

    it('rejects extra Ctrl on mac and extra Meta on non-mac', () => {
        expect(
            shortcutMatchesKeyboardEvent(
                modS.shortcut,
                new KeyboardEvent('keydown', {
                    key: 's',
                    metaKey: true,
                    ctrlKey: true,
                }),
                true,
            ),
        ).toBe(false);

        expect(
            shortcutMatchesKeyboardEvent(
                modS.shortcut,
                new KeyboardEvent('keydown', {
                    key: 's',
                    ctrlKey: true,
                    metaKey: true,
                }),
                false,
            ),
        ).toBe(false);
    });

    it('normalizes the event key before comparing', () => {
        const esc = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+esc' }),
        );
        if (!esc?.ok) {
            throw new Error('fixture expected to parse');
        }
        expect(
            shortcutMatchesKeyboardEvent(
                esc.shortcut,
                new KeyboardEvent('keydown', { key: 'Escape', metaKey: true }),
                true,
            ),
        ).toBe(true);
    });
});

describe('formatShortcutForDisplay and formatShortcutHintCompact', () => {
    const modShiftS = parseFlatpackActionShortcut(
        headerAction({ id: 'x', shortcut: 'mod+shift+s' }),
    );
    if (!modShiftS?.ok) {
        throw new Error('fixture expected to parse');
    }

    it('uses platform glyphs on Mac and plain labels elsewhere', () => {
        expect(formatShortcutForDisplay(modShiftS.shortcut, true)).toEqual([
            '⌘',
            '⇧',
            'S',
        ]);
        expect(formatShortcutForDisplay(modShiftS.shortcut, false)).toEqual([
            'Ctrl',
            'Shift',
            'S',
        ]);
        expect(formatShortcutHintCompact(modShiftS.shortcut, true)).toBe(
            '⌘ + ⇧ + S',
        );
        expect(formatShortcutHintCompact(modShiftS.shortcut, false)).toBe(
            'Ctrl + Shift + S',
        );
    });

    it('formats multi-character keys for display', () => {
        const esc = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+esc' }),
        );
        if (!esc?.ok) {
            throw new Error('fixture expected to parse');
        }
        expect(formatShortcutForDisplay(esc.shortcut, false)).toEqual([
            'Ctrl',
            'Escape',
        ]);
    });

    it('formats space clearly', () => {
        const sp = parseFlatpackActionShortcut(
            headerAction({ id: 'x', shortcut: 'mod+space' }),
        );
        if (!sp?.ok) {
            throw new Error('fixture expected to parse');
        }
        expect(formatShortcutForDisplay(sp.shortcut, false)).toEqual([
            'Ctrl',
            'Space',
        ]);
    });
});
