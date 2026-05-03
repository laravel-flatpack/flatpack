import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import { cn } from '@/lib/utils';
import type { FormFieldLabelShow } from '@/types/form-fields';
import { Calendar } from '../ui/calendar';
import { Field, FieldContent, FieldTitle } from '../ui/field';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';

export const DatePickerField = ({
    id,
    label,
    emptyLabel,
    value,
    onValueChange,
    inline = false,
    showLabel,
    triggerClassName,
    disabled = false,
}: {
    id: string;
    label: string;
    emptyLabel: string;
    value?: Date;
    onValueChange?: (value: Date | undefined) => void;
    inline?: boolean;
    showLabel?: FormFieldLabelShow;
    triggerClassName?: string;
    disabled?: boolean;
}) => {
    const [open, setOpen] = useState(false);
    const labelId = `${id}-label`;
    const labelledBy = !inline && label ? labelId : undefined;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');

    const trigger = (
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
                        'flex h-9 w-full min-w-0 items-center justify-start rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                        open && !disabled && 'border-ring ring-3 ring-ring/30',
                        triggerClassName,
                    )}
                    aria-expanded={disabled ? undefined : open}
                    aria-labelledby={labelledBy}
                >
                    <CalendarIcon className="mr-2 size-4 shrink-0 text-muted-foreground" />
                    {value ? (
                        format(value, 'PPP')
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
                    selected={value}
                    onSelect={(d) => {
                        setOpen(false);
                        onValueChange?.(d);
                    }}
                />
            </PopoverContent>
        </Popover>
    );

    if (inline) {
        return trigger;
    }

    return (
        <Field orientation={labelLayout.orientation}>
            {label ? (
                <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                    {label}
                </FieldTitle>
            ) : null}
            <FieldContent>{trigger}</FieldContent>
        </Field>
    );
};
