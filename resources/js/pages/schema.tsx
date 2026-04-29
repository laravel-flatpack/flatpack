import { Head, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';
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
import type {
    SchemaNodeDoc,
    SchemaPageProps,
    SchemaPropertyDoc,
} from '@/types/schema';

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

            <Tabs defaultValue="overview" className="w-full">
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
                            <pre className="max-h-[70vh] overflow-auto rounded-xl border bg-muted/30 p-4 text-xs">
                                {JSON.stringify(document.raw, null, 2)}
                            </pre>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}

SchemaPage.layout = (page: ReactNode) => <DocsLayout>{page}</DocsLayout>;

export default SchemaPage;
