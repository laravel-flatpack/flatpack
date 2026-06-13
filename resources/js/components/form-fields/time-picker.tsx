import { format } from 'date-fns';
import { CalendarIcon, ChevronDownIcon, ClockIcon } from 'lucide-react';
import { useLayoutEffect, useState } from 'react';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import { cn } from '@/lib/utils';
import type { FormFieldLabelShow } from '@/types/form-fields';
import { Calendar } from '../ui/calendar';
import { Field, FieldContent, FieldGroup, FieldTitle } from '../ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '../ui/input-group';
import { Popover, PopoverContent, PopoverTrigger } from '../ui/popover';

export const TimePickerField = ({
    id,
    dateLabel,
    timeLabel,
    dateEmptyLabel,
    timeDefaultValue,
    value,
    onValueChange,
    showLabel,
    disabled = false,
}: {
    id: string;
    dateLabel: string;
    timeLabel: string;
    dateEmptyLabel: string;
    timeDefaultValue: string;
    value?: { date?: Date; time?: string };
    onValueChange?: (value: { date: Date | undefined; time: string }) => void;
    showLabel?: FormFieldLabelShow;
    disabled?: boolean;
}) => {
    const dateId = `${id}-date`;
    const timeId = `${id}-time`;
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(value?.date);
    const [time, setTime] = useState(value?.time ?? timeDefaultValue);
    const dateLabelId = `${dateId}-label`;
    const timeLabelId = `${timeId}-label`;
    const ll = resolveFormFieldLabelLayout(showLabel, 'stacked');

    useLayoutEffect(() => {
        setDate(value?.date);
        setTime(value?.time ?? timeDefaultValue);
    }, [timeDefaultValue, value]);

    return (
        <FieldGroup className="w-full flex-row flex-wrap items-end gap-4">
            <Field orientation={ll.orientation} className="min-w-0 flex-1">
                {dateLabel ? (
                    <FieldTitle id={dateLabelId} className={ll.labelClassName}>
                        {dateLabel}
                    </FieldTitle>
                ) : null}
                <FieldContent>
                    <Popover
                        open={disabled ? false : open}
                        onOpenChange={disabled ? () => {} : setOpen}
                    >
                        <PopoverTrigger asChild>
                            <button
                                id={dateId}
                                type="button"
                                disabled={disabled}
                                className={cn(
                                    'flex h-9 w-full min-w-[8.5rem] items-center justify-between gap-2 rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                    open &&
                                        !disabled &&
                                        'border-ring ring-3 ring-ring/30',
                                )}
                                aria-expanded={disabled ? undefined : open}
                            >
                                <span className="flex min-w-0 flex-1 items-center gap-2">
                                    <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
                                    <span
                                        className={cn(
                                            'min-w-0 truncate',
                                            !date && 'text-muted-foreground',
                                        )}
                                    >
                                        {date
                                            ? format(date, 'PPP')
                                            : dateEmptyLabel}
                                    </span>
                                </span>
                                <ChevronDownIcon className="size-4 shrink-0 text-muted-foreground" />
                            </button>
                        </PopoverTrigger>
                        <PopoverContent
                            className="w-auto overflow-hidden p-0"
                            align="start"
                        >
                            <Calendar
                                mode="single"
                                captionLayout="dropdown"
                                defaultMonth={date}
                                selected={date}
                                onSelect={(d) => {
                                    setDate(d);
                                    setOpen(false);
                                    onValueChange?.({ date: d, time });
                                }}
                            />
                        </PopoverContent>
                    </Popover>
                </FieldContent>
            </Field>
            <Field
                orientation={ll.orientation}
                className="w-full min-w-[10rem] sm:w-36"
            >
                {timeLabel ? (
                    <FieldTitle id={timeLabelId} className={ll.labelClassName}>
                        {timeLabel}
                    </FieldTitle>
                ) : null}
                <FieldContent>
                    <InputGroup className="rounded-3xl">
                        <InputGroupAddon>
                            <ClockIcon />
                        </InputGroupAddon>
                        <InputGroupInput
                            type="time"
                            id={timeId}
                            name={timeId}
                            autoComplete="off"
                            step={1}
                            value={time}
                            disabled={disabled}
                            className="appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                            aria-labelledby={
                                timeLabel ? timeLabelId : undefined
                            }
                            onChange={(e) => {
                                const next = e.target.value;
                                setTime(next);
                                onValueChange?.({ date, time: next });
                            }}
                        />
                    </InputGroup>
                </FieldContent>
            </Field>
        </FieldGroup>
    );
};
