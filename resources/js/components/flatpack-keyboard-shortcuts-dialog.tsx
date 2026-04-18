import { KeyboardIcon } from 'lucide-react';
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
                    <DialogTitle className="flex items-center gap-2 text-lg font-medium">
                        <KeyboardIcon className="w-4 h-4" />
                        Keyboard shortcuts
                    </DialogTitle>
                    <DialogDescription className="text-sm text-muted-foreground">
                        The following keyboard shortcuts are available on
                        <br />
                        this page.
                    </DialogDescription>
                </DialogHeader>
                <table className="w-full">
                    <thead>
                        <tr>
                            <th className="text-left py-3 text-xs">Shortcut</th>
                            <th className="text-left py-3 text-xs">
                                Description
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {shortcuts.map((row) => (
                            <tr
                                key={row.id}
                                className="border-b border-border last:border-b-0"
                            >
                                <td className="py-3">
                                    <FlatpackParsedShortcutKbd
                                        shortcut={row.shortcut}
                                        isMac={isMacPlatform}
                                    />
                                </td>
                                <td className="py-3 text-sm">
                                    {row.description}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </DialogContent>
        </Dialog>
    );
}
