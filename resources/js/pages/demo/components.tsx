import { Head, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, ChevronDownIcon, ClockIcon } from 'lucide-react';
import { type ReactNode, useMemo, useState } from 'react';
import type { DateRange } from 'react-day-picker';
import { Calendar } from '@/components/ui/calendar';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

/** Slugs for `?type=` — `toggle` is accepted as an alias for `switch`. */
export const demoComponentTypes = [
    'text',
    'textarea',
    'select',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'checkbox',
    'switch',
] as const;

export type DemoComponentType = (typeof demoComponentTypes)[number];

type PageProps = {
    type: string | null;
};

type NormalizedType = DemoComponentType;

const titles: Record<NormalizedType, string> = {
    text: 'Text input',
    textarea: 'Textarea',
    select: 'Select',
    'date-picker': 'Date picker',
    'date-range-picker': 'Date range picker',
    'time-picker': 'Time picker',
    checkbox: 'Checkbox',
    switch: 'Switch',
};

function normalizeType(raw: string | null): NormalizedType | null {
    if (raw === null || raw === '') {
        return null;
    }
    const lower = raw.trim().toLowerCase();
    if (lower === 'toggle') {
        return 'switch';
    }
    if ((demoComponentTypes as readonly string[]).includes(lower)) {
        return lower as NormalizedType;
    }
    return null;
}

function DemoText() {
    return (
        <Field>
            <FieldLabel htmlFor="demo-text">Label</FieldLabel>
            <FieldContent>
                <Input
                    id="demo-text"
                    type="text"
                    placeholder="Placeholder"
                    defaultValue=""
                />
                <FieldDescription>Optional helper text.</FieldDescription>
            </FieldContent>
        </Field>
    );
}

function DemoTextarea() {
    return (
        <Field>
            <FieldLabel htmlFor="demo-textarea">Message</FieldLabel>
            <FieldContent>
                <Textarea
                    id="demo-textarea"
                    placeholder="Type something…"
                    rows={5}
                    className="field-sizing-fixed resize-y min-h-0"
                />
            </FieldContent>
        </Field>
    );
}

function DemoSelect() {
    const [value, setValue] = useState('a');

    return (
        <Field>
            <FieldLabel htmlFor="demo-select">Choose</FieldLabel>
            <FieldContent>
                <Select value={value} onValueChange={setValue}>
                    <SelectTrigger id="demo-select" className="w-full max-w-sm">
                        <SelectValue placeholder="Pick an option" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="a">Option A</SelectItem>
                        <SelectItem value="b">Option B</SelectItem>
                        <SelectItem value="c">Option C</SelectItem>
                    </SelectContent>
                </Select>
            </FieldContent>
        </Field>
    );
}

function DemoDatePicker() {
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>();

    return (
        <Field>
            <FieldLabel htmlFor="demo-date-picker">Date</FieldLabel>
            <FieldContent>
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <button
                            id="demo-date-picker"
                            type="button"
                            className={cn(
                                'flex h-9 w-full min-w-0 max-w-sm items-center justify-start rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                open && 'border-ring ring-3 ring-ring/30',
                            )}
                            aria-expanded={open}
                        >
                            <CalendarIcon className="mr-2 size-4 shrink-0 text-muted-foreground" />
                            {date ? (
                                format(date, 'PPP')
                            ) : (
                                <span className="text-muted-foreground">
                                    Pick a date
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
                            }}
                        />
                    </PopoverContent>
                </Popover>
            </FieldContent>
        </Field>
    );
}

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

function DemoDateRangePicker() {
    const [open, setOpen] = useState(false);
    const [range, setRange] = useState<DateRange | undefined>();

    return (
        <Field>
            <FieldLabel htmlFor="demo-date-range-picker">Dates</FieldLabel>
            <FieldContent>
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <button
                            id="demo-date-range-picker"
                            type="button"
                            className={cn(
                                'flex h-9 w-full min-w-0 max-w-md items-center justify-start truncate rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                open && 'border-ring ring-3 ring-ring/30',
                            )}
                            aria-expanded={open}
                        >
                            <CalendarIcon className="mr-2 size-4 shrink-0 text-muted-foreground" />
                            {formatRangeLabel(range) ?? (
                                <span className="text-muted-foreground">
                                    Pick a date range
                                </span>
                            )}
                        </button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                            mode="range"
                            numberOfMonths={2}
                            selected={range}
                            onSelect={(next) => {
                                setRange(next);
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
}

function DemoTimePicker() {
    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>();

    return (
        <FieldGroup className="max-w-md flex-row flex-wrap items-end gap-4">
            <Field className="min-w-0 flex-1">
                <FieldLabel htmlFor="demo-time-picker-date">Date</FieldLabel>
                <FieldContent>
                    <Popover open={open} onOpenChange={setOpen}>
                        <PopoverTrigger asChild>
                            <button
                                id="demo-time-picker-date"
                                type="button"
                                className={cn(
                                    'flex h-9 w-full min-w-[8.5rem] items-center justify-between gap-2 rounded-3xl border border-transparent bg-input/50 px-3 py-1 text-left text-base font-normal text-foreground transition-[color,box-shadow,background-color] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 md:text-sm',
                                    open && 'border-ring ring-3 ring-ring/30',
                                )}
                                aria-expanded={open}
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
                                            : 'Select date'}
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
                                }}
                            />
                        </PopoverContent>
                    </Popover>
                </FieldContent>
            </Field>
            <Field className="w-full min-w-[10rem] sm:w-36">
                <FieldLabel htmlFor="demo-time-picker-time">Time</FieldLabel>
                <FieldContent>
                    <InputGroup className="rounded-3xl">
                        <InputGroupAddon>
                            <ClockIcon />
                        </InputGroupAddon>
                        <InputGroupInput
                            type="time"
                            id="demo-time-picker-time"
                            step={1}
                            defaultValue="10:30:00"
                            className="appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                        />
                    </InputGroup>
                </FieldContent>
            </Field>
        </FieldGroup>
    );
}

function DemoCheckbox() {
    return (
        <Field orientation="horizontal">
            <Checkbox id="demo-checkbox" defaultChecked />
            <FieldLabel htmlFor="demo-checkbox">Accept terms</FieldLabel>
        </Field>
    );
}

function DemoSwitch() {
    return (
        <Field orientation="horizontal">
            <Switch id="demo-switch" defaultChecked />
            <FieldLabel htmlFor="demo-switch">Airplane mode</FieldLabel>
        </Field>
    );
}

const demos: Record<NormalizedType, () => ReactNode> = {
    text: DemoText,
    textarea: DemoTextarea,
    select: DemoSelect,
    'date-picker': DemoDatePicker,
    'date-range-picker': DemoDateRangePicker,
    'time-picker': DemoTimePicker,
    checkbox: DemoCheckbox,
    switch: DemoSwitch,
};

const allTypesOrdered: NormalizedType[] = [
    'text',
    'textarea',
    'select',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'checkbox',
    'switch',
];

export default function DemoComponents() {
    const { type: rawType } = usePage<PageProps>().props;

    const normalized = useMemo(() => normalizeType(rawType ?? null), [rawType]);

    const requestedUnknown =
        rawType !== null && rawType !== '' && normalized === null;

    const headTitle =
        normalized !== null
            ? `${titles[normalized]} — Components`
            : 'Components';

    if (requestedUnknown) {
        return (
            <div className="flex flex-col gap-4 p-6">
                <Head title="Components" />
                <p className="text-sm text-muted-foreground">
                    Unknown component type{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-foreground">
                        {rawType}
                    </code>
                    . Use a valid{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono">
                        ?type=
                    </code>{' '}
                    value or open this page without query parameters to see all
                    components.
                </p>
            </div>
        );
    }

    if (normalized !== null) {
        const Demo = demos[normalized];
        return (
            <div
                className="p-6"
                data-demo-component={normalized}
                data-slot="demo-components-single"
            >
                <Head title={headTitle} />
                <Demo />
            </div>
        );
    }

    return (
        <div
            className="flex flex-col gap-10 p-4 md:p-6"
            data-slot="demo-components-all"
        >
            <Head title={headTitle} />
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Components
                </h1>
                <p className="text-sm text-muted-foreground">
                    Preview Flatpack UI primitives. Use{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        ?type=text
                    </code>{' '}
                    (and similar) to show a single component for documentation
                    iframes.
                </p>
            </div>
            <div className="flex flex-col gap-12">
                {allTypesOrdered.map((key) => {
                    const Demo = demos[key];
                    return (
                        <section
                            key={key}
                            id={`demo-${key}`}
                            className="flex flex-col gap-4 scroll-mt-6"
                            data-demo-component={key}
                        >
                            <h2 className="text-lg font-medium tracking-tight">
                                {titles[key]}
                            </h2>
                            <div className="rounded-xl border border-border bg-card/30 p-6">
                                <Demo />
                            </div>
                        </section>
                    );
                })}
            </div>
        </div>
    );
}
