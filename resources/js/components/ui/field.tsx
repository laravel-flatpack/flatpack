import { cva, type VariantProps } from 'class-variance-authority';
import { useMemo } from 'react';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useInlineFieldsetLabelGrid } from '@/contexts/inline-fieldset-label-grid';
import { cn } from '@/lib/utils';

function FieldSet({ className, ...props }: React.ComponentProps<'fieldset'>) {
    return (
        <fieldset
            data-slot="field-set"
            className={cn(
                'flex flex-col gap-6 has-[>[data-slot=checkbox-group]]:gap-3 has-[>[data-slot=radio-group]]:gap-3',
                className,
            )}
            {...props}
        />
    );
}

function FieldLegend({
    className,
    variant = 'legend',
    ...props
}: React.ComponentProps<'legend'> & { variant?: 'legend' | 'label' }) {
    return (
        <legend
            data-slot="field-legend"
            data-variant={variant}
            className={cn(
                'mb-3 font-medium data-[variant=label]:text-sm data-[variant=legend]:text-base',
                className,
            )}
            {...props}
        />
    );
}

function FieldGroup({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="field-group"
            className={cn(
                'group/field-group @container/field-group flex w-full flex-col gap-7 data-[slot=checkbox-group]:gap-3 *:data-[slot=field-group]:gap-4',
                className,
            )}
            {...props}
        />
    );
}

const fieldVariants = cva(
    'group/field flex w-full gap-3 data-[invalid=true]:text-destructive',
    {
        variants: {
            orientation: {
                vertical: 'flex-col *:w-full [&>.sr-only]:w-auto',
                horizontal:
                    'flex-row items-center has-[>[data-slot=field-content]]:items-start has-[>[data-slot=field-content]]:*:data-[slot=field-label]:mt-3 *:data-[slot=field-label]:shrink-0 *:data-[slot=field-label]:grow-0 *:data-[slot=field-label]:basis-auto *:data-[slot=field-label]:max-w-[min(42%,18rem)] has-[>[data-slot=field-content]]:[&>[role=checkbox],[role=radio]]:mt-px',
                responsive:
                    'flex-col *:w-full @md/field-group:flex-row @md/field-group:items-center @md/field-group:*:w-auto @md/field-group:has-[>[data-slot=field-content]]:items-start @md/field-group:has-[>[data-slot=field-content]]:*:data-[slot=field-label]:mt-2 @md/field-group:*:data-[slot=field-label]:shrink-0 @md/field-group:*:data-[slot=field-label]:grow-0 @md/field-group:*:data-[slot=field-label]:basis-auto @md/field-group:*:data-[slot=field-label]:max-w-[min(42%,18rem)] [&>.sr-only]:w-auto @md/field-group:has-[>[data-slot=field-content]]:[&>[role=checkbox],[role=radio]]:mt-px',
            },
        },
        defaultVariants: {
            orientation: 'vertical',
        },
    },
);

function Field({
    className,
    orientation = 'vertical',
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof fieldVariants>) {
    const inlineFieldsetGrid = useInlineFieldsetLabelGrid();
    const contentsLayout =
        inlineFieldsetGrid === true && orientation === 'horizontal';

    return (
        <div
            role="group"
            data-slot="field"
            data-orientation={orientation}
            className={cn(
                contentsLayout
                    ? 'contents group/field data-[invalid=true]:text-destructive'
                    : fieldVariants({ orientation }),
                className,
            )}
            {...props}
        />
    );
}

function FieldContent({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="field-content"
            className={cn(
                'group/field-content flex min-h-0 min-w-0 w-full max-w-full flex-1 flex-col gap-1 leading-snug',
                className,
            )}
            {...props}
        />
    );
}

function FieldLabel({
    className,
    ...props
}: React.ComponentProps<typeof Label>) {
    return (
        <Label
            data-slot="field-label"
            className={cn(
                'group/field-label peer/field-label flex w-fit gap-2 leading-snug group-data-[disabled=true]/field:opacity-50 has-data-checked:bg-input/30 has-[>[data-slot=field]]:rounded-2xl has-[>[data-slot=field]]:border *:data-[slot=field]:p-4',
                'has-[>[data-slot=field]]:w-full has-[>[data-slot=field]]:flex-col',
                className,
            )}
            {...props}
        />
    );
}

function FieldTitle({ className, ...props }: React.ComponentProps<'div'>) {
    const inlineFieldsetGrid = useInlineFieldsetLabelGrid();
    return (
        <div
            data-slot="field-label"
            className={cn(
                'flex w-fit items-center gap-2 leading-snug font-medium group-data-[disabled=true]/field:opacity-50',
                // Inline (`showLabel: inline`) uses horizontal orientation; stacked labels stay slightly larger.
                'group-data-[orientation=horizontal]/field:text-xs group-data-[orientation=vertical]/field:text-sm',
                // Inline label grid uses `Field` with display:contents, so flex-row label offsets from fieldVariants do not apply; nudge label down to align with control cap-height.
                inlineFieldsetGrid && 'mt-2.5',
                className,
            )}
            {...props}
        />
    );
}

function FieldDescription({ className, ...props }: React.ComponentProps<'p'>) {
    return (
        <p
            data-slot="field-description"
            className={cn(
                'text-left text-sm leading-normal font-normal text-muted-foreground group-has-data-horizontal/field:text-balance [[data-variant=legend]+&]:-mt-1.5',
                'last:mt-0 nth-last-2:-mt-1',
                '[&>a]:underline [&>a]:underline-offset-4 [&>a:hover]:text-primary',
                className,
            )}
            {...props}
        />
    );
}

function FieldSeparator({
    children,
    className,
    ...props
}: React.ComponentProps<'div'> & {
    children?: React.ReactNode;
}) {
    return (
        <div
            data-slot="field-separator"
            data-content={!!children}
            className={cn(
                'relative -my-2 h-5 text-sm group-data-[variant=outline]/field-group:-mb-2',
                className,
            )}
            {...props}
        >
            <Separator className="absolute inset-0 top-1/2" />
            {children && (
                <span
                    className="relative mx-auto block w-fit bg-background px-2 text-muted-foreground"
                    data-slot="field-separator-content"
                >
                    {children}
                </span>
            )}
        </div>
    );
}

function FieldError({
    className,
    children,
    errors,
    ...props
}: React.ComponentProps<'div'> & {
    errors?: Array<{ message?: string } | undefined>;
}) {
    const content = useMemo(() => {
        if (children) {
            return children;
        }

        if (!errors?.length) {
            return null;
        }

        const uniqueErrors = [
            ...new Map(errors.map((error) => [error?.message, error])).values(),
        ];

        if (uniqueErrors?.length === 1) {
            return uniqueErrors[0]?.message;
        }

        return (
            <ul className="ml-4 flex list-disc flex-col gap-1">
                {uniqueErrors.map(
                    (error) =>
                        error?.message && (
                            <li key={error.message}>{error.message}</li>
                        ),
                )}
            </ul>
        );
    }, [children, errors]);

    if (!content) {
        return null;
    }

    return (
        <div
            role="alert"
            data-slot="field-error"
            className={cn('text-sm font-normal text-destructive', className)}
            {...props}
        >
            {content}
        </div>
    );
}

export {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    FieldTitle,
};
