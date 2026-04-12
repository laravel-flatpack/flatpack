import { Head, usePage } from '@inertiajs/react';
import { ChevronDown, RadioIcon } from 'lucide-react';
import {
    type ReactNode,
    Suspense,
    useCallback,
    useMemo,
    useState,
} from 'react';
import {
    FieldLoading,
    type FieldLoadingProps,
} from '@/components/field-loading';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import DemoLayout from '@/layouts/demo-layout';
import {
    buildDemoFieldRenderProps,
    type DemoLazyFieldMap,
    demoCatalogToByType,
    lazyFieldMapFromCatalog,
    resolveDemoComponentSelection,
} from '@/lib/demo';
import { mergeDemoFlatQuery, resolveDemoShowValue } from '@/lib/demo-query';
import type {
    DemoComponentCatalogEntry,
    DemoComponentsInertiaProps,
} from '@/types/demo';

const FIELD_LOADING_TEXT_TYPES = new Set([
    'text',
    'date-picker',
    'date-range-picker',
    'time-picker',
    'select',
    'combobox',
]);

const FIELD_LOADING_TEXTAREA_TYPES = new Set([
    'textarea',
    'rich-text',
    'block-editor',
    'table',
]);

function fieldLoadingPropsForEntry(
    entry: DemoComponentCatalogEntry,
): FieldLoadingProps {
    const { type, label, helperText } = entry.props;
    return {
        label: Boolean(label),
        helperText: Boolean(helperText),
        textField: FIELD_LOADING_TEXT_TYPES.has(type),
        textareaField: FIELD_LOADING_TEXTAREA_TYPES.has(type),
    };
}

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

    const LazyField = lazyByType[entry.props.type];

    const fieldLoadingProps = useMemo(
        () => fieldLoadingPropsForEntry(entry),
        [entry],
    );

    const showLiveValue = resolveDemoShowValue(entry, demoQueryFlat);

    return (
        <div className="flex flex-col gap-4">
            <Suspense fallback={<FieldLoading {...fieldLoadingProps} />}>
                <LazyField {...fieldProps} />
            </Suspense>
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

function DemoComponents() {
    const inertiaPage = usePage<DemoComponentsInertiaProps>();
    const { query, catalog } = inertiaPage.props;

    const catalogDerived = useMemo(
        () => ({
            byType: demoCatalogToByType(catalog),
            lazyByType: lazyFieldMapFromCatalog(catalog),
            orderedTypes: catalog.map((e) => e.props.type),
        }),
        [catalog],
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

    const { selectorRaw, normalized, requestedUnknown } = useMemo(
        () => resolveDemoComponentSelection(flatQuery, catalogDerived.byType),
        [flatQuery, catalogDerived.byType],
    );

    const headTitle =
        normalized !== null
            ? `${catalogDerived.byType[normalized].title} — Components`
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
                    or open this page without those parameters to see all
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
                    entry={catalogDerived.byType[normalized]}
                    queryOverrides={flatQuery}
                    demoQueryFlat={flatQuery}
                    lazyByType={catalogDerived.lazyByType}
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
        </div>
    );
}

DemoComponents.layout = (page: ReactNode) => <DemoLayout>{page}</DemoLayout>;

export default DemoComponents;
