import { Skeleton } from '@/components/ui/skeleton';
import type { FieldLoadingProps } from '@/types/loading';

export function FieldLoading({
    label = true,
    textField = true,
    textareaField = false,
    helperText = false,
}: FieldLoadingProps) {
    return (
        <div className="flex flex-col gap-4" data-slot="field-loading">
            <div className="flex flex-col gap-4">
                {label && (
                    <Skeleton className="h-4 w-32 rounded-full bg-muted-foreground/15 dark:bg-muted-foreground/25" />
                )}
                {textField && (
                    <Skeleton className="h-8 w-full rounded-xl bg-muted-foreground/15 dark:bg-muted-foreground/25" />
                )}
                {textareaField && (
                    <Skeleton className="h-40 w-full rounded-xl bg-muted-foreground/15 dark:bg-muted-foreground/25" />
                )}
            </div>
            {helperText && (
                <Skeleton className="h-4 w-40 rounded-full bg-muted-foreground/15 dark:bg-muted-foreground/25" />
            )}
        </div>
    );
}
