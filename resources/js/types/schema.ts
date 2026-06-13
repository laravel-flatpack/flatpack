export type SchemaPropertyDoc = {
    name: string;
    required: boolean;
    description: string | null;
    type: string;
    ref: string | null;
    options: string[];
    const: string;
    default: string;
    validation: Record<string, string>;
    compositionRules: Record<string, number>;
};

export type SchemaNodeDoc = {
    key: string;
    title: string | null;
    description: string | null;
    type: string;
    required: string[];
    properties: SchemaPropertyDoc[];
    options: string[];
    const: string;
    validation: Record<string, string>;
    compositionRules: Record<string, number>;
    ref: string | null;
};

export type SchemaDocument = {
    id: 'form' | 'list';
    title: string;
    description: string | null;
    meta: {
        propertyCount: number;
        definitionCount: number;
    };
    root: SchemaNodeDoc;
    definitions: SchemaNodeDoc[];
    raw: Record<string, unknown>;
};

export type SchemaPageProps = {
    schemaType: 'form' | 'list';
    query: Record<string, unknown>;
    document: SchemaDocument;
};
