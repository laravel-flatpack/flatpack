import { Head, usePage } from '@inertiajs/react';
import { type ReactNode, useMemo } from 'react';
import {
    CheckboxField,
    ComboboxField,
    type ComboboxObjectItem,
    DatePickerField,
    DateRangePickerField,
    SelectField,
    SwitchField,
    TextareaField,
    TextField,
    TimePickerField,
} from '@/components/form-fields';

/** Slugs for `?type=` (`toggle` → `switch`). Combobox multi: `?type=combobox&multiple=true`. */
export const demoComponentTypes = [
    'text',
    'textarea',
    'select',
    'combobox',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'checkbox',
    'switch',
] as const;

export type DemoComponentType = (typeof demoComponentTypes)[number];

type PageProps = {
    type: string | null;
    /** When `type` is `combobox`, mirrors Flatpack YAML `multiple: true` for that field. */
    multiple: boolean;
};

type NormalizedType = DemoComponentType;

const titles: Record<NormalizedType, string> = {
    text: 'Text input',
    textarea: 'Textarea',
    select: 'Select',
    combobox: 'Combobox',
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

const DEMO_COMBOBOX_ITEMS: ComboboxObjectItem[] = Array.from(
    { length: 200 },
    (_, i) => ({
        value: `opt-${i + 1}`,
        label: `Option ${String(i + 1).padStart(3, '0')}`,
    }),
);

const DEMO_COMBOBOX_MULTI_ITEMS = [
    'Next.js',
    'SvelteKit',
    'Nuxt.js',
    'Remix',
    'Astro',
] as const;

function DemoText() {
    return (
        <TextField
            id="demo-text"
            label="Label"
            placeholder="Placeholder"
            defaultValue=""
            helperText="Optional helper text."
        />
    );
}

function DemoTextarea() {
    return (
        <TextareaField
            id="demo-textarea"
            label="Message"
            placeholder="Type something…"
            rows={5}
            className="field-sizing-fixed resize-y min-h-0"
        />
    );
}

function DemoSelect() {
    return (
        <SelectField
            id="demo-select"
            label="Choose"
            placeholder="Pick an option"
            options={[
                { value: 'a', label: 'Option A' },
                { value: 'b', label: 'Option B' },
                { value: 'c', label: 'Option C' },
            ]}
        />
    );
}

function DemoCombobox() {
    const { type, multiple } = usePage<PageProps>().props;
    const isMultiple =
        Boolean(multiple) && type?.trim().toLowerCase() === 'combobox';

    return (
        <ComboboxField
            id="demo-combobox"
            label={isMultiple ? 'Frameworks' : 'Choose'}
            multiple={isMultiple}
            items={DEMO_COMBOBOX_ITEMS}
            multiItems={DEMO_COMBOBOX_MULTI_ITEMS}
            singlePlaceholder="Search or pick…"
            multiPlaceholder="Add framework…"
            singleDescription={
                <>
                    Like select, but typeahead and filtering for long option
                    lists. Set{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        multiple: true
                    </code>{' '}
                    on the field in YAML for multi-select (chips); preview with{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        {'?type=combobox&multiple=true'}
                    </code>
                    .
                </>
            }
            multiDescription={
                <>
                    Enabled when the field sets{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        multiple: true
                    </code>{' '}
                    in Flatpack YAML (this preview uses{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        {'?type=combobox&multiple=true'}
                    </code>
                    ).
                </>
            }
        />
    );
}

function DemoDatePicker() {
    return (
        <DatePickerField
            id="demo-date-picker"
            label="Date"
            emptyLabel="Pick a date"
        />
    );
}

function DemoDateRangePicker() {
    return (
        <DateRangePickerField
            id="demo-date-range-picker"
            label="Dates"
            emptyLabel="Pick a date range"
        />
    );
}

function DemoTimePicker() {
    return (
        <TimePickerField
            dateId="demo-time-picker-date"
            timeId="demo-time-picker-time"
            dateLabel="Date"
            timeLabel="Time"
            dateEmptyLabel="Select date"
            timeDefaultValue="10:30:00"
        />
    );
}

function DemoCheckbox() {
    return (
        <CheckboxField id="demo-checkbox" label="Accept terms" defaultChecked />
    );
}

function DemoSwitch() {
    return (
        <SwitchField id="demo-switch" label="Airplane mode" defaultChecked />
    );
}

const demos: Record<NormalizedType, () => ReactNode> = {
    text: DemoText,
    textarea: DemoTextarea,
    select: DemoSelect,
    combobox: DemoCombobox,
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
    'combobox',
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
