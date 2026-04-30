import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { cn } from '@/lib/utils';
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
}: {
    id: string;
    label: string;
    emptyLabel: string;
    value?: DateRange;
    onValueChange?: (value: DateRange | undefined) => void;
}) => {
    const [open, setOpen] = useState(false);
    const labelId = `${id}-label`;

    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <button
                            id={id}
                            type="button"
                            className={cn(
                                'flex h-9 w-full min-w-0 items-center justify-start truncate rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                open && 'border-ring ring-3 ring-ring/30',
                            )}
                            aria-expanded={open}
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
