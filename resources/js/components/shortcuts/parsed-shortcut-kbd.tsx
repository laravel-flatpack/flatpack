import { Kbd, KbdGroup } from '@/components/ui/kbd';
import {
    formatShortcutForDisplay,
    type ParsedFlatpackShortcut,
} from '@/lib/flatpack-action-shortcuts';

export function ParsedShortcutKbd({
    shortcut,
    isMac,
}: {
    shortcut: ParsedFlatpackShortcut;
    isMac: boolean;
}) {
    const parts = formatShortcutForDisplay(shortcut, isMac);

    return (
        <KbdGroup className="pointer-events-none">
            {parts.map((part) => (
                <Kbd key={`${shortcut.normalized}:${part}`}>{part}</Kbd>
            ))}
        </KbdGroup>
    );
}
