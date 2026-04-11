import { Head, usePage } from '@inertiajs/react';
import {
    type ReactNode,
    Suspense,
    useCallback,
    useMemo,
    useState,
} from 'react';
import DemoLayout from '@/layouts/demo-layout';
import {
    buildDemoFieldRenderProps,
    demoComponentByType,
    demoComponentTypes,
    flattenDemoQuery,
    lazyFieldByType,
    normalizeDemoComponentType,
} from '@/lib/demo';
import type {
    DemoComponentCatalogEntry,
    DemoComponentsInertiaProps,
} from '@/types/demo';

export {
    type DemoComponentType,
    demoComponentTypes,
} from '@/lib/demo';

function formatDemoLiveValue(value: unknown): string {
    return JSON.stringify(
        value,
        (_, v) => (v instanceof Date ? v.toISOString() : v),
        2,
    );
}

function DemoFieldPreview({
    entry,
    queryOverrides,
}: {
    entry: DemoComponentCatalogEntry;
    queryOverrides: Record<string, string>;
}) {
    const [liveValue, setLiveValue] = useState<unknown>(null);

    const onValueChange = useCallback((value: unknown) => {
        setLiveValue(value);
    }, []);

    const fieldProps = useMemo(
        () =>
            buildDemoFieldRenderProps(entry, {
                queryOverrides,
                onValueChange,
            }),
        [entry, queryOverrides, onValueChange],
    );

    const LazyField = lazyFieldByType[entry.props.type];

    return (
        <div className="flex flex-col gap-4">
            <Suspense
                fallback={
                    <div className="text-sm text-muted-foreground">
                        Loading…
                    </div>
                }
            >
                <LazyField {...fieldProps} />
            </Suspense>
            {entry.output?.show ? (
                <div className="flex flex-col gap-1">
                    <span className="text-xs font-medium text-muted-foreground">
                        {entry.output.label}
                    </span>
                    <pre className="max-h-48 overflow-auto rounded-md border bg-muted/30 p-3 text-xs">
                        {liveValue === null || liveValue === undefined
                            ? 'null'
                            : formatDemoLiveValue(liveValue)}
                    </pre>
                </div>
            ) : null}
        </div>
    );
}

function DemoComponents() {
    const { query } = usePage<DemoComponentsInertiaProps>().props;

    const flatQuery = useMemo(() => flattenDemoQuery(query), [query]);

    const rawType = flatQuery.type;
    const normalized = useMemo(
        () =>
            normalizeDemoComponentType(
                rawType !== undefined && rawType !== '' ? rawType : null,
            ),
        [rawType],
    );

    const requestedUnknown =
        rawType !== undefined && rawType !== '' && normalized === null;

    const headTitle =
        normalized !== null
            ? `${demoComponentByType[normalized].title} — Components`
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
        return (
            <div
                className="p-6"
                data-demo-component={normalized}
                data-slot="demo-components-single"
            >
                <Head title={headTitle} />
                <DemoFieldPreview
                    entry={demoComponentByType[normalized]}
                    queryOverrides={flatQuery}
                />
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
                    iframes. Optional query keys override catalog props for a
                    single component (for example{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        placeholder
                    </code>
                    ,{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        label
                    </code>
                    ,{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        showFixedToolbar
                    </code>
                    ).
                </p>
            </div>
            <div className="flex flex-col gap-12">
                {demoComponentTypes.map((key) => (
                    <section
                        key={key}
                        id={`demo-${key}`}
                        className="flex flex-col gap-4 scroll-mt-6"
                        data-demo-component={key}
                    >
                        <div className="flex flex-col gap-1">
                            <h2 className="text-lg font-medium tracking-tight">
                                {demoComponentByType[key].title}
                            </h2>
                            <p className="text-sm text-muted-foreground">{demoComponentByType[key].description}</p>
                        </div>
                        <div className="rounded-xl border border-border bg-card/30 p-6">
                            <DemoFieldPreview
                                entry={demoComponentByType[key]}
                                queryOverrides={{}}
                            />
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}

DemoComponents.layout = (page: ReactNode) => <DemoLayout>{page}</DemoLayout>;

export default DemoComponents;
