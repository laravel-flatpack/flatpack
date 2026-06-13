import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import { cn } from '@/lib/utils';
import type { FormFieldLabelShow } from '@/types/form-fields';
import { Calendar } from '../ui/calendar';
import { Field, FieldContent, FieldTitle } from '../ui/field';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';

function formatRangeLabel(range: DateRange | undefined): ReactNode {
    if (!range?.from) {
        return null;
    }
    if (!range.to) {
        return (
            <>
                {format(range.from, 'PPP')}
                <span className="text-muted-foreground"> — …</span>
            </>
        );
    }
    return (
        <>
            {format(range.from, 'PPP')}
            <span className="text-muted-foreground"> — </span>
            {format(range.to, 'PPP')}
        </>
    );
}

export const DateRangePickerField = ({
    id,
    label,
    emptyLabel,
    value,
    onValueChange,
    showLabel,
    disabled = false,
}: {
    id: string;
    label: string;
    emptyLabel: string;
    value?: DateRange;
    onValueChange?: (value: DateRange | undefined) => void;
    showLabel?: FormFieldLabelShow;
    disabled?: boolean;
}) => {
    const [open, setOpen] = useState(false);
    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');

    return (
        <Field orientation={labelLayout.orientation}>
            {label ? (
                <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                    {label}
                </FieldTitle>
            ) : null}
            <FieldContent>
                <Popover
                    open={disabled ? false : open}
                    onOpenChange={disabled ? () => {} : setOpen}
                >
                    <PopoverTrigger asChild>
                        <button
                            id={id}
                            type="button"
                            disabled={disabled}
                            className={cn(
                                'flex h-9 w-full min-w-0 items-center justify-start truncate rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                open &&
                                    !disabled &&
                                    'border-ring ring-3 ring-ring/30',
                            )}
                            aria-expanded={disabled ? undefined : open}
                            aria-labelledby={label ? labelId : undefined}
                        >
                            <CalendarIcon className="mr-2 size-4 shrink-0 text-muted-foreground" />
                            {formatRangeLabel(value) ?? (
                                <span className="text-muted-foreground">
                                    {emptyLabel}
                                </span>
                            )}
                        </button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                            mode="range"
                            numberOfMonths={2}
                            selected={value}
                            onSelect={(next) => {
                                onValueChange?.(next);
                                if (next?.from && next.to) {
                                    setOpen(false);
                                }
                            }}
                        />
                    </PopoverContent>
                </Popover>
            </FieldContent>
        </Field>
    );
};
