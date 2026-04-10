import { Head, usePage } from '@inertiajs/react';
import { type ReactNode, useMemo, useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

/** Slugs for `?type=` — `toggle` is accepted as an alias for `switch`. */
export const demoComponentTypes = [
    'text',
    'textarea',
    'select',
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
    checkbox: DemoCheckbox,
    switch: DemoSwitch,
};

const allTypesOrdered: NormalizedType[] = [
    'text',
    'textarea',
    'select',
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
