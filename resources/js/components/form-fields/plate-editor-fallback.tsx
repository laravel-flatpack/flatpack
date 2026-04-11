import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

export type PlateEditorFallbackProps = {
    /** Matches {@link RichTextEditor} vs {@link BlockEditor} content height. */
    variant: 'rich-text' | 'block';
    showFixedToolbar?: boolean;
    className?: string;
};

/** Shown while a Plate-based editor chunk is loading (rich text / block editor). */
export function PlateEditorFallback({
    variant,
    showFixedToolbar = false,
    className,
}: PlateEditorFallbackProps) {
    const contentHeight =
        variant === 'block'
            ? 'min-h-[280px] max-h-[560px]'
            : 'min-h-[220px] max-h-[480px]';

    return (
        <div
            className={cn(
                'flex w-full flex-col overflow-hidden rounded-md border border-input bg-background ring-offset-background',
                className,
            )}
            data-slot="plate-editor-fallback"
        >
            {showFixedToolbar ? (
                <div
                    className="h-10 shrink-0 border-b border-border bg-background/95 backdrop-blur-sm supports-backdrop-blur:bg-background/60"
                    aria-hidden
                />
            ) : null}
            <div
                className={cn(
                    'flex w-full flex-1 items-center justify-center',
                    contentHeight,
                    variant === 'block' && 'py-2',
                    variant === 'rich-text' && showFixedToolbar && 'py-2',
                )}
            >
                <Spinner className="size-6 text-muted-foreground" />
            </div>
        </div>
    );
}
