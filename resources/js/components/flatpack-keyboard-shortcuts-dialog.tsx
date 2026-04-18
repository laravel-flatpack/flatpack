import { FlatpackParsedShortcutKbd } from '@/components/flatpack-parsed-shortcut-kbd';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useFlatpackRegisteredShortcuts } from '@/contexts/flatpack-shortcuts-registry';
import { useIsMacPlatform } from '@/hooks/use-is-mac-platform';

type FlatpackKeyboardShortcutsDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function FlatpackKeyboardShortcutsDialog({
    open,
    onOpenChange,
}: FlatpackKeyboardShortcutsDialogProps) {
    const isMacPlatform = useIsMacPlatform();
    const shortcuts = useFlatpackRegisteredShortcuts();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md" showCloseButton>
                <DialogHeader>
                    <DialogTitle>Keyboard shortcuts</DialogTitle>
                    <DialogDescription className="text-sm text-muted-foreground">
                        The following keyboard shortcuts are available on
                        <br />
                        this page.
                    </DialogDescription>
                </DialogHeader>
                <ul className="flex max-h-[min(60vh,28rem)] flex-col gap-2 overflow-y-auto pt-1">
                    {shortcuts.map((row) => (
                        <li
                            key={row.id}
                            className="flex items-center justify-between gap-4 text-sm border-b border-border pb-3 last:border-b-0"
                        >
                            <span className="min-w-0 shrink">
                                {row.description}
                            </span>
                            <FlatpackParsedShortcutKbd
                                shortcut={row.shortcut}
                                isMac={isMacPlatform}
                            />
                        </li>
                    ))}
                </ul>
            </DialogContent>
        </Dialog>
    );
}
