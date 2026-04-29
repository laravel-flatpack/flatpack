import { Head, usePage } from '@inertiajs/react';
import {
    BlocksIcon,
    ChevronDown,
    LayersIcon,
    RadioIcon,
    SlidersHorizontalIcon,
} from 'lucide-react';
import {
    type ReactNode,
    Suspense,
    useCallback,
    useLayoutEffect,
    useMemo,
    useState,
} from 'react';
import { FieldLoading } from '@/components/field-loading';
import { NavHeader } from '@/components/nav-header';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { CardWidget } from '@/components/widgets/card';
import { MetricWidget } from '@/components/widgets/metric';
import DemoLayout from '@/layouts/demo-layout';
import {
    buildDemoFieldRenderProps,
    type DemoLazyFieldMap,
    lazyFieldMapFromCatalog,
} from '@/lib/demo';
import { mergeDemoFlatQuery, resolveDemoShowValue } from '@/lib/demo-query';
import { route } from '@/lib/route';
import type {
    DemoComponentCatalogEntry,
    DemoComponentsInertiaProps,
    DemoComponentWidgetEntry,
} from '@/types/demo';
import { WidgetLoading } from '@/components/widget-loading';

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
    demoQueryFlat,
    lazyByType,
}: {
    entry: DemoComponentCatalogEntry;
    queryOverrides: Record<string, string>;
    /** Merged URL query for demo-only flags (e.g. `showValue`), not field prop overrides. */
    demoQueryFlat: Record<string, string>;
    lazyByType: DemoLazyFieldMap;
}) {
    const [liveValue, setLiveValue] = useState<unknown>(
        () => entry.value ?? null,
    );

    useLayoutEffect(() => {
        setLiveValue(entry.value ?? null);
    }, [entry]);

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

    const LazyField = lazyByType[entry.props.type];

    const showLiveValue = resolveDemoShowValue(entry, demoQueryFlat);

    return (
        <div className="flex flex-col gap-4">
            <div className="space-y-2">
                <Suspense fallback={<FieldLoading {...entry.props} />}>
                    <LazyField {...fieldProps} />
                </Suspense>
            </div>
            {showLiveValue ? (
                <Collapsible defaultOpen={true}>
                    <CollapsibleTrigger
                        className="flex w-full items-center justify-between gap-2 rounded-md px-3 py-3 text-left text-muted-foreground outline-none transition-colors hover:bg-muted/40 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 [&[data-state=open]>svg]:rotate-180"
                        type="button"
                    >
                        <div className="flex items-center gap-2">
                            <RadioIcon className="size-4 shrink-0" />
                            <span className="text-xs font-medium">
                                Live Value
                            </span>
                        </div>
                        <ChevronDown
                            aria-hidden
                            className="size-4 shrink-0 transition-transform duration-200"
                        />
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <pre className="mt-2 max-h-48 overflow-auto rounded-md border bg-muted/30 p-3 text-xs">
                            {liveValue === null || liveValue === undefined
                                ? 'null'
                                : formatDemoLiveValue(liveValue)}
                        </pre>
                    </CollapsibleContent>
                </Collapsible>
            ) : null}
        </div>
    );
}

function DemoWidgetPreview({
    entry,
}: {
    entry: DemoComponentWidgetEntry;
}) {
    return (
        <div className="flex flex-col gap-4">
            <Suspense fallback={<WidgetLoading {...entry.props} />}>
                {entry.props.type === 'metric' ? (
                    <MetricWidget widget={entry.props} />
                ) : entry.props.type === 'card' ? (
                    <CardWidget widget={entry.props} />
                ) : null}
            </Suspense>
        </div>
    );
}   

function DemoComponents() {
    const inertiaPage = usePage<DemoComponentsInertiaProps>();
    const query = inertiaPage.props.query ?? {};
    const fields = inertiaPage.props.fieldsCatalog;
    const widgets = inertiaPage.props.widgetsCatalog;

    const catalogDerived = useMemo(
        () => ({
            byType: fields.reduce<Record<string, DemoComponentCatalogEntry>>(
                (acc, entry) => {
                    if (!Object.hasOwn(acc, entry.props.type)) {
                        acc[entry.props.type] = entry;
                    }
                    return acc;
                },
                {},
            ),
            lazyByType: lazyFieldMapFromCatalog(fields),
            orderedTypes: [...new Set(fields.map((e) => e.props.type))],
        }),
        [fields],
    );
    const widgetsByType = useMemo(
        () =>
            widgets.reduce<Record<string, DemoComponentWidgetEntry>>(
                (acc, entry) => {
                    if (!Object.hasOwn(acc, entry.props.type)) {
                        acc[entry.props.type] = entry;
                    }
                    return acc;
                },
                {},
            ),
        [widgets],
    );

    const browserSearch =
        typeof window !== 'undefined' ? window.location.search : '';

    const flatQuery = useMemo(
        () =>
            mergeDemoFlatQuery({
                query,
                inertiaUrl: inertiaPage.url,
                locationSearch: browserSearch,
            }),
        [query, inertiaPage.url, browserSearch],
    );

    const catalogParam = (flatQuery.catalog ?? 'all').toLowerCase();
    const selectedCatalog: 'all' | 'fields' | 'widgets' =
        catalogParam === 'fields' || catalogParam === 'widgets'
            ? catalogParam
            : 'all';
    const lookupCatalog: 'fields' | 'widgets' =
        catalogParam === 'widgets' ? 'widgets' : 'fields';

    const selectorRaw = (
        flatQuery.type ??
        flatQuery.demo ??
        flatQuery.field ??
        flatQuery.widget ??
        flatQuery.column ??
        ''
    ).trim();
    const selectorNormalized = selectorRaw.toLowerCase();
    const selectedWidgetEntry =
        selectorNormalized !== '' ? widgetsByType[selectorNormalized] : undefined;
    const selectedFieldEntry =
        selectorNormalized !== '' ? catalogDerived.byType[selectorNormalized] : undefined;
    const requestedUnknown =
        selectorNormalized !== '' && (
            lookupCatalog === 'widgets'
                ? selectedWidgetEntry === undefined
                : selectedFieldEntry === undefined
        );

    const headTitle =
        lookupCatalog === 'widgets' && selectedWidgetEntry !== undefined
            ? `${selectedWidgetEntry.title} — Widgets`
            : selectedFieldEntry !== undefined
                ? `${selectedFieldEntry.title} — Components`
            : 'Components';

    if (requestedUnknown) {
        return (
            <div className="flex flex-col gap-4 p-6">
                <Head title={headTitle} />
                <p className="text-sm text-muted-foreground">
                    Unknown component type{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-foreground">
                        {selectorRaw}
                    </code>
                    . Use a valid{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono">
                        ?type=
                    </code>{' '}
                    {lookupCatalog === 'widgets' ? (
                        <>
                            with{' '}
                            <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono">
                                catalog=widgets
                            </code>{' '}
                        </>
                    ) : null}
                    or open this page without those parameters to see all
                    components.
                </p>
            </div>
        );
    }

    if (lookupCatalog === 'widgets' && selectedWidgetEntry !== undefined) {
        return (
            <div
                className="p-6"
                data-demo-component={selectedWidgetEntry.id}
                data-slot="demo-components-single"
            >
                <Head title={headTitle} />
                <DemoWidgetPreview entry={selectedWidgetEntry} />
            </div>
        );
    }

    if (selectedFieldEntry !== undefined) {
        return (
            <div
                className="p-6"
                data-demo-component={selectedFieldEntry.id}
                data-slot="demo-components-single"
            >
                <Head title={headTitle} />
                <DemoFieldPreview
                    entry={selectedFieldEntry}
                    queryOverrides={flatQuery}
                    demoQueryFlat={flatQuery}
                    lazyByType={catalogDerived.lazyByType}
                />
            </div>
        );  
    }

    return (
        <>
            <NavHeader
                activeId={selectedCatalog}
                items={[
                    {
                        id: 'all',
                        label: 'All Components',
                        href: route('flatpack.demo.components'),
                        icon: LayersIcon,
                    },
                    {
                        id: 'fields',
                        label: 'Form Fields',
                        href: route('flatpack.demo.components', {
                            catalog: 'fields',
                        }),
                        icon: SlidersHorizontalIcon,
                    },
                    {
                        id: 'widgets',
                        label: 'Widgets',
                        href: route('flatpack.demo.components', {
                            catalog: 'widgets',
                        }),
                        icon: BlocksIcon,
                    },
                ]}
            />
            <div className="mx-2 flex w-full max-w-7xl flex-col gap-6 py-6">
                <Head title={headTitle} />
                <div className="flex items-center justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-black tracking-tight mb-4">
                            Components
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Preview Flatpack UI primitives. Use{' '}
                            <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                                ?type=
                            </code>
                            (or
                            <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                                ?demo=text
                            </code>
                            ) to show one field. Optional query keys override catalog
                            props (for example{' '}
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
                            ). Use{' '}
                            <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                                showValue=true
                            </code>{' '}
                            or{' '}
                            <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                                showValue=false
                            </code>{' '}
                            to toggle the live value panel.
                        </p>
                    </div>
                </div>
                {(selectedCatalog === 'all' || selectedCatalog === 'fields') && (
                    <div className="flex flex-col gap-12">
                        {catalogDerived.orderedTypes.map((key) => (
                            <section
                                key={key}
                                id={`demo-${key}`}
                                className="flex flex-col gap-4 scroll-mt-6"
                                data-demo-component={key}
                            >
                                <div className="flex flex-col gap-1">
                                    <h2 className="text-lg font-medium tracking-tight">
                                        {catalogDerived.byType[key].title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {catalogDerived.byType[key].description}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-border bg-card/30 p-6">
                                    <DemoFieldPreview
                                        entry={catalogDerived.byType[key]}
                                        queryOverrides={{}}
                                        demoQueryFlat={flatQuery}
                                        lazyByType={catalogDerived.lazyByType}
                                    />
                                </div>
                            </section>
                        ))}
                    </div>
                )}
                {(selectedCatalog === 'all' || selectedCatalog === 'widgets') && (
                    <div className="flex flex-col gap-12">
                        {widgets.map((entry) => (
                            <section 
                                key={entry.id}
                                id={`demo-${entry.id}`}
                                className="flex flex-col gap-4 scroll-mt-6"
                                data-demo-component={entry.id}
                            >
                                <div className="flex flex-col gap-1">
                                    <h2 className="text-lg font-medium tracking-tight">
                                        {entry.title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {entry.description}
                                    </p>
                                </div>
                                <div className="rounded-xl border border-border bg-card/30 p-6">
                                    <DemoWidgetPreview entry={entry} />
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

DemoComponents.layout = (page: ReactNode) => <DemoLayout>{page}</DemoLayout>;

export default DemoComponents;
