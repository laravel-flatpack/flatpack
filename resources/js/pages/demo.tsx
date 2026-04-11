import { Head, usePage } from '@inertiajs/react';
import {
    type ReactNode,
    Suspense,
    useCallback,
    useEffect,
    useMemo,
    useState,
} from 'react';
import DemoLayout from '@/layouts/demo-layout';
import {
    buildDemoFieldRenderProps,
    type DemoLazyFieldMap,
    demoCatalogToByType,
    demoCatalogToTypes,
    flattenDemoQuery,
    lazyFieldMapFromCatalog,
    normalizeDemoComponentType,
    parseLocationSearch,
    parseSearchParamsFromUrl,
    pickDemoComponentSelector,
} from '@/lib/demo';
import type {
    DemoComponentCatalogEntry,
    DemoComponentsInertiaProps,
    DemoComponentType,
} from '@/types/demo';

export type { DemoComponentType };

/**
 * Opt-in only (no automatic dev banner):
 * - `?debugDemo=1` or `true` on the demo URL
 */
function useDemoQueryDebugEnabled(flatQuery: Record<string, string>): boolean {
    if (flatQuery.debugDemo === '1' || flatQuery.debugDemo === 'true') {
        return true;
    }
    return false;
}

function DemoQueryDebugDump({ data }: { data: unknown }) {
    return (
        <div
            className="rounded-md border border-amber-500/60 bg-amber-500/10 p-4 font-mono text-xs text-foreground mb-8"
            data-slot="demo-query-debug"
        >
            <p className="mb-2 font-sans font-semibold text-amber-950 dark:text-amber-100">
                Demo query debug — also logged as{' '}
                <code className="rounded bg-black/10 px-1 dark:bg-white/10">
                    [flatpack demo/components]
                </code>
                . Enable with{' '}
                <code className="rounded bg-black/10 px-1">?debugDemo=1</code> .
            </p>
            <pre className="max-h-[min(70vh,520px)] overflow-auto whitespace-pre-wrap break-words">
                {JSON.stringify(data, null, 2)}
            </pre>
        </div>
    );
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
    lazyByType,
}: {
    entry: DemoComponentCatalogEntry;
    queryOverrides: Record<string, string>;
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
    const inertiaPage = usePage<DemoComponentsInertiaProps>();
    const { query, catalog } = inertiaPage.props;

    const demoComponentByType = useMemo(
        () => demoCatalogToByType(catalog),
        [catalog],
    );
    const demoComponentTypes = useMemo(
        () => demoCatalogToTypes(catalog),
        [catalog],
    );
    const lazyFieldByType = useMemo(
        () => lazyFieldMapFromCatalog(catalog),
        [catalog],
    );

    const browserSearch =
        typeof window !== 'undefined' ? window.location.search : '';

    const { flatQuery, queryLayers } = useMemo(() => {
        const fromServer = flattenDemoQuery(query);
        const fromInertiaUrl = parseSearchParamsFromUrl(inertiaPage.url);
        const fromAddressBar = parseLocationSearch(browserSearch);
        // Address bar wins (matches what the user sees); then Inertia URL; then server props.
        const merged = { ...fromServer, ...fromInertiaUrl, ...fromAddressBar };
        return {
            flatQuery: merged,
            queryLayers: { fromServer, fromInertiaUrl, fromAddressBar },
        };
    }, [query, inertiaPage.url, browserSearch]);

    const showQueryDebug = useDemoQueryDebugEnabled(flatQuery);

    const queryDebugPayload = useMemo(() => {
        const selectorPick = pickDemoComponentSelector(flatQuery);
        const trimmed = selectorPick.trim().toLowerCase();
        const norm = normalizeDemoComponentType(
            trimmed !== '' ? trimmed : null,
            demoComponentByType,
        );
        return {
            step: 'demo/components query resolution',
            inertia: {
                component: inertiaPage.component,
                url: inertiaPage.url,
                version: inertiaPage.version,
                propsTopLevelKeys: Object.keys(inertiaPage.props).sort(),
            },
            rawProps: {
                query:
                    query === undefined
                        ? '(undefined — not sent by server)'
                        : query,
                catalogEntryCount: catalog.length,
            },
            browser:
                typeof window === 'undefined'
                    ? null
                    : {
                          href: window.location.href,
                          search: window.location.search,
                          pathname: window.location.pathname,
                      },
            queryLayers,
            flatQueryMerged: flatQuery,
            selector: {
                raw: selectorPick,
                trimmedLower: trimmed,
            },
            normalized: norm,
            flags: {
                requestedUnknown: trimmed !== '' && norm === null,
            },
        };
    }, [
        catalog,
        demoComponentByType,
        inertiaPage.component,
        inertiaPage.props,
        inertiaPage.url,
        inertiaPage.version,
        query,
        flatQuery,
        queryLayers,
    ]);

    useEffect(() => {
        if (!showQueryDebug) {
            return;
        }
        console.log('[flatpack demo/components]', queryDebugPayload);
    }, [showQueryDebug, queryDebugPayload]);

    const debugPanel = showQueryDebug ? (
        <DemoQueryDebugDump data={queryDebugPayload} />
    ) : null;

    const selectorRaw = pickDemoComponentSelector(flatQuery);
    const selectorTrimmed = selectorRaw.trim().toLowerCase();
    const normalized = useMemo(
        () =>
            normalizeDemoComponentType(
                selectorTrimmed !== '' ? selectorTrimmed : null,
                demoComponentByType,
            ),
        [selectorTrimmed, demoComponentByType],
    );

    const requestedUnknown = selectorTrimmed !== '' && normalized === null;

    const headTitle =
        normalized !== null
            ? `${demoComponentByType[normalized].title} — Components`
            : 'Components';

    if (requestedUnknown) {
        return (
            <div className="flex flex-col gap-4 p-6">
                <Head title="Components" />
                {debugPanel}
                <p className="text-sm text-muted-foreground">
                    Unknown component type{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-foreground">
                        {selectorRaw}
                    </code>
                    . Use a valid{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono">
                        ?demo=
                    </code>{' '}
                    (or{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono">
                        ?type=
                    </code>
                    ) or open this page without those parameters to see all
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
                {debugPanel}
                <DemoFieldPreview
                    entry={demoComponentByType[normalized]}
                    queryOverrides={flatQuery}
                    lazyByType={lazyFieldByType}
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
            {debugPanel}
            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Components
                </h1>
                <p className="text-sm text-muted-foreground">
                    Preview Flatpack UI primitives. Use{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        ?demo=text
                    </code>{' '}
                    (or{' '}
                    <code className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs">
                        ?type=text
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
                            <p className="text-sm text-muted-foreground">
                                {demoComponentByType[key].description}
                            </p>
                        </div>
                        <div className="rounded-xl border border-border bg-card/30 p-6">
                            <DemoFieldPreview
                                entry={demoComponentByType[key]}
                                queryOverrides={{}}
                                lazyByType={lazyFieldByType}
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
