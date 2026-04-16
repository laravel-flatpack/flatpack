import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

/** Single confirmation UI for form, list header, and bulk actions (and any future call sites). */
const DEFAULT_DESCRIPTION = 'Are you sure you want to continue?';

export function FlatpackConfirmDialog({
    open,
    onOpenChange,
    title,
    description = DEFAULT_DESCRIPTION,
    continueLabel = 'Continue',
    continueVariant = 'default',
    onContinue,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    continueLabel?: string;
    continueVariant?: 'default' | 'destructive';
    onContinue: () => void;
}) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        variant={continueVariant}
                        onClick={onContinue}
                    >
                        {continueLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
