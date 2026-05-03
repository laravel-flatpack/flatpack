import { Head, usePage } from '@inertiajs/react';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import { ChevronDown, ListChecksIcon, TextCursorInputIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';
import { NavHeader } from '@/components/navigation/nav-header';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import DocsLayout from '@/layouts/docs-layout';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';
import type {
    SchemaNodeDoc,
    SchemaPageProps,
    SchemaPropertyDoc,
} from '@/types/schema';

hljs.registerLanguage('json', json);

const hljsPanelLayoutClass =
    'schema-json-hljs max-h-[70vh] overflow-auto rounded-xl border border-border bg-muted/30 p-4 font-mono text-xs leading-relaxed [tab-size:2] dark:bg-muted/50';

const hljsLightTokenColorsClass =
    'not-dark:text-foreground not-dark:**:[.hljs-punctuation]:text-muted-foreground not-dark:**:[.hljs-attr,.hljs-attribute,.hljs-literal,.hljs-meta,.hljs-number,.hljs-operator,.hljs-selector-attr,.hljs-selector-class,.hljs-selector-id,.hljs-variable]:text-[#005cc5] not-dark:**:[.hljs-keyword,.hljs-doctag,.hljs-template-tag,.hljs-template-variable,.hljs-type,.hljs-variable.language\\\\_]:text-[#d73a49] not-dark:**:[.hljs-name,.hljs-quote]:text-[#22863a] not-dark:**:[.hljs-regexp,.hljs-string,.hljs-meta_.hljs-string]:text-[#032f62] not-dark:**:[.hljs-comment,.hljs-code,.hljs-formula]:text-[#6a737d]';

const hljsDarkTokenColorsClass =
    'dark:text-[#9aa5ce] dark:**:[.hljs-meta,.hljs-comment]:text-[#565f89] dark:**:[.hljs-variable,.hljs-template-variable,.hljs-number,.hljs-literal,.hljs-type,.hljs-params,.hljs-link]:text-[#ff9e64] dark:**:[.hljs-tag,.hljs-doctag,.hljs-selector-id,.hljs-selector-class,.hljs-regexp,.hljs-template-tag,.hljs-selector-pseudo,.hljs-selector-attr,.hljs-variable.language\\\\_,.hljs-deletion]:text-[#f7768e] dark:**:[.hljs-built_in,.hljs-attribute]:text-[#e0af68] dark:**:[.hljs-selector-tag]:text-[#73daca] dark:**:[.hljs-title.function\\\\_,.hljs-title,.hljs-title.class\\\\_,.hljs-title.class\\\\_.inherited\\\\_\\\\_,.hljs-subst,.hljs-property]:text-[#7dcfff] dark:**:[.hljs-quote,.hljs-string,.hljs-symbol,.hljs-bullet,.hljs-addition]:text-[#9ece6a] dark:**:[.hljs-code,.hljs-formula,.hljs-section]:text-[#7aa2f7] dark:**:[.hljs-name,.hljs-keyword,.hljs-operator,.hljs-char.escape\\\\_,.hljs-attr]:text-[#bb9af7] dark:**:[.hljs-punctuation]:text-[#c0caf5]';

function SchemaRawJsonPanel({ raw }: { raw: unknown }) {
    const code = JSON.stringify(raw, null, 2);
    const html = hljs.highlight(code, {
        language: 'json',
        ignoreIllegals: true,
    }).value;

    return (
        <pre
            className={cn(
                hljsPanelLayoutClass,
                hljsLightTokenColorsClass,
                hljsDarkTokenColorsClass,
            )}
        >
            {/* hljs escapes markup; source is JSON.stringify of server payload */}
            {/* biome-ignore lint/security/noDangerouslySetInnerHtml: trusted hljs HTML from normalized schema */}
            <code className="hljs" dangerouslySetInnerHTML={{ __html: html }} />
        </pre>
    );
}

function PropertyBadges({ property }: { property: SchemaPropertyDoc }) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <Badge variant={property.required ? 'default' : 'outline'}>
                {property.required ? 'required' : 'optional'}
            </Badge>
            <Badge variant="secondary">{property.type}</Badge>
            {property.ref ? (
                <Badge variant="outline">{property.ref}</Badge>
            ) : null}
            {property.options.length > 0 ? (
                <Badge variant="outline">
                    {property.options.length} options
                </Badge>
            ) : null}
        </div>
    );
}

function PropertyCard({ property }: { property: SchemaPropertyDoc }) {
    return (
        <Card
            size="sm"
            className="rounded-2xl border border-border bg-card/40 shadow-none"
        >
            <CardHeader className="px-4">
                <div className="flex items-center justify-between gap-3">
                    <CardTitle className="font-mono text-sm">
                        {property.name}
                    </CardTitle>
                    <PropertyBadges property={property} />
                </div>
                {property.description ? (
                    <CardDescription>{property.description}</CardDescription>
                ) : null}
            </CardHeader>
            <CardContent className="flex flex-col gap-3 px-4 pb-4">
                {property.options.length > 0 ? (
                    <div className="flex flex-wrap gap-1.5">
                        {property.options.map((option) => (
                            <Badge
                                key={`${property.name}-${option}`}
                                variant="outline"
                                className="font-mono"
                            >
                                {option}
                            </Badge>
                        ))}
                    </div>
                ) : null}
                {Object.keys(property.validation).length > 0 ? (
                    <div className="flex flex-wrap gap-1.5">
                        {Object.entries(property.validation).map(
                            ([key, value]) => (
                                <Badge
                                    key={`${property.name}-${key}`}
                                    variant="secondary"
                                    className="font-mono"
                                >
                                    {key}: {value}
                                </Badge>
                            ),
                        )}
                    </div>
                ) : null}
                {Object.keys(property.compositionRules).length > 0 ? (
                    <div className="flex flex-wrap gap-1.5">
                        {Object.entries(property.compositionRules).map(
                            ([ruleName, count]) => (
                                <Badge
                                    key={`${property.name}-${ruleName}`}
                                    variant="outline"
                                    className="font-mono"
                                >
                                    {ruleName}: {count}
                                </Badge>
                            ),
                        )}
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}

function SchemaNodeSection({ node }: { node: SchemaNodeDoc }) {
    return (
        <section className="flex flex-col gap-4">
            <div className="flex flex-col gap-2">
                <h3 className="text-base font-semibold tracking-tight">
                    {node.title ?? node.key}
                </h3>
                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">{node.type}</Badge>
                    {node.required.length > 0 ? (
                        <Badge variant="outline">
                            {node.required.length} required keys
                        </Badge>
                    ) : null}
                    {node.options.length > 0 ? (
                        <Badge variant="outline">
                            {node.options.length} available options
                        </Badge>
                    ) : null}
                </div>
                {node.description ? (
                    <p className="text-sm text-muted-foreground">
                        {node.description}
                    </p>
                ) : null}
            </div>

            {Object.keys(node.validation).length > 0 ? (
                <div className="flex flex-wrap gap-1.5">
                    {Object.entries(node.validation).map(([key, value]) => (
                        <Badge
                            key={`${node.key}-${key}`}
                            variant="secondary"
                            className="font-mono"
                        >
                            {key}: {value}
                        </Badge>
                    ))}
                </div>
            ) : null}

            {node.options.length > 0 ? (
                <div className="flex flex-wrap gap-1.5">
                    {node.options.map((option) => (
                        <Badge
                            key={`${node.key}-${option}`}
                            variant="outline"
                            className="font-mono"
                        >
                            {option}
                        </Badge>
                    ))}
                </div>
            ) : null}

            {node.properties.length > 0 ? (
                <div className="grid gap-3">
                    {node.properties.map((property) => (
                        <PropertyCard
                            key={`${node.key}-${property.name}`}
                            property={property}
                        />
                    ))}
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">
                    No nested properties.
                </p>
            )}
        </section>
    );
}

function SchemaPage() {
    const { document } = usePage<SchemaPageProps>().props;
    const [activeTab, setActiveTab] = useState('overview');

    return (
        <div className="mx-2 flex w-full max-w-7xl flex-col gap-6 py-6">
            <Head title={`Schema - ${document.id}`} />
            <div className="flex items-center justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-black tracking-tight mb-4">
                        {document.title}
                    </h1>
                    {document.description ? (
                        <p className="text-sm text-muted-foreground">
                            {document.description}
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                <Badge variant="secondary">
                    {document.meta.propertyCount} root properties
                </Badge>
                <Badge variant="secondary">
                    {document.meta.definitionCount} definitions
                </Badge>
            </div>

            <Tabs
                value={activeTab}
                onValueChange={setActiveTab}
                className="w-full"
            >
                <TabsList variant="line" className="w-full justify-start">
                    <TabsTrigger value="overview" className="flex-none">
                        Overview
                    </TabsTrigger>
                    <TabsTrigger value="definitions" className="flex-none">
                        Definitions
                    </TabsTrigger>
                    <TabsTrigger value="raw" className="flex-none">
                        Raw JSON
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="pt-2">
                    <Card className="rounded-3xl border border-border bg-card/40 shadow-none">
                        <CardHeader>
                            <CardTitle>Root object</CardTitle>
                            <CardDescription>
                                Top-level keys, validation constraints, and
                                available options.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <SchemaNodeSection node={document.root} />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="definitions" className="pt-2">
                    <div className="space-y-3">
                        {document.definitions.map((definition) => (
                            <Collapsible
                                key={definition.key}
                                defaultOpen={
                                    definition.key === 'fieldDefinition'
                                }
                            >
                                <Card className="rounded-3xl border border-border bg-card/40 shadow-none">
                                    <CardHeader>
                                        <CollapsibleTrigger
                                            type="button"
                                            className="group flex w-full items-center justify-between gap-3 text-left"
                                        >
                                            <div className="flex flex-col gap-1">
                                                <CardTitle className="font-mono text-sm">
                                                    #/$defs/{definition.key}
                                                </CardTitle>
                                                <CardDescription>
                                                    {definition.description ??
                                                        'No description'}
                                                </CardDescription>
                                            </div>
                                            <ChevronDown className="size-4 shrink-0 transition-transform group-data-[state=open]:rotate-180" />
                                        </CollapsibleTrigger>
                                    </CardHeader>
                                    <CollapsibleContent>
                                        <CardContent className="pt-0">
                                            <SchemaNodeSection
                                                node={definition}
                                            />
                                        </CardContent>
                                    </CollapsibleContent>
                                </Card>
                            </Collapsible>
                        ))}
                    </div>
                </TabsContent>

                <TabsContent value="raw" className="pt-2">
                    <Card className="rounded-3xl border border-border bg-card/40 shadow-none">
                        <CardHeader>
                            <CardTitle>Raw schema payload</CardTitle>
                            <CardDescription>
                                Normalized backend output from the JSON schema
                                source.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {activeTab === 'raw' ? (
                                <SchemaRawJsonPanel raw={document.raw} />
                            ) : null}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}

function SchemaDocsShell({ children }: { children: ReactNode }) {
    const { document } = usePage<SchemaPageProps>().props;
    const navigation = (
        <NavHeader
            activeId={document.id}
            items={[
                {
                    id: 'form',
                    label: 'Form schema',
                    href: route('flatpack.schema.form'),
                    icon: TextCursorInputIcon,
                },
                {
                    id: 'list',
                    label: 'List schema',
                    href: route('flatpack.schema.list'),
                    icon: ListChecksIcon,
                },
            ]}
        />
    );

    return <DocsLayout navigation={navigation}>{children}</DocsLayout>;
}

SchemaPage.layout = (page: ReactNode) => (
    <SchemaDocsShell>{page}</SchemaDocsShell>
);

export default SchemaPage;
