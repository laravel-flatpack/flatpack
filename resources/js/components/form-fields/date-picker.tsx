import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Calendar } from '../ui/calendar';
import { Field, FieldContent, FieldTitle } from '../ui/field';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';

export const DatePickerField = ({
    id,
    label,
    emptyLabel,
    onValueChange,
}: {
    id: string;
    label: string;
    emptyLabel: string;
    onValueChange?: (value: Date | undefined) => void;
}) => {
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>();
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
                                'flex h-9 w-full min-w-0 max-w-sm items-center justify-start rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                open && 'border-ring ring-3 ring-ring/30',
                            )}
                            aria-expanded={open}
                            aria-labelledby={label ? labelId : undefined}
                        >
                            <CalendarIcon className="mr-2 size-4 shrink-0 text-muted-foreground" />
                            {date ? (
                                format(date, 'PPP')
                            ) : (
                                <span className="text-muted-foreground">
                                    {emptyLabel}
                                </span>
                            )}
                        </button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                            mode="single"
                            selected={date}
                            onSelect={(d) => {
                                setDate(d);
                                setOpen(false);
                                onValueChange?.(d);
                            }}
                        />
                    </PopoverContent>
                </Popover>
            </FieldContent>
        </Field>
    );
};
