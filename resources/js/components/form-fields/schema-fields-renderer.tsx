import { Suspense } from 'react';
import { FieldLoading } from '@/components/loading/field-loading';
import { FieldError } from '@/components/ui/field';
import { loadField } from '@/lib/form';
import { mapFormFieldPropsToComponentProps } from '@/lib/form-field-props';
import {
    componentValueProps,
    relationRemoteProps,
    serializeFieldValue,
} from '@/lib/form-page-field-values';
import type {
    SchemaFieldRenderEntry,
    SchemaFieldsRendererProps,
} from '@/types/schema-fields-renderer';

function buildFieldComponentProps(
    entry: SchemaFieldRenderEntry,
    args: {
        entity?: string;
        parentRecordKey?: string | null;
        onEmbeddedTableToolbarAction?: (ctx: {
            fieldId: string;
            actionId: string;
        }) => void;
    },
) {
    const { field, id, value } = entry;
    const serialize =
        entry.serializeValue ?? ((f, next) => serializeFieldValue(f, next));
    const onValueChange = (nextValue: unknown) => {
        entry.onValueChange(serialize(field, nextValue, value));
    };

    return {
        ...(entry.extraComponentProps ?? {}),
        ...mapFormFieldPropsToComponentProps(field, {
            fieldId: id,
            entity: args.entity,
            onValueChange,
            parentRecordKey: args.parentRecordKey,
            onEmbeddedTableToolbarAction: args.onEmbeddedTableToolbarAction,
        }),
        ...componentValueProps(field, value),
        ...(args.entity != null
            ? relationRemoteProps(field, id, args.entity)
            : {}),
        ...(entry.required === true ? { required: true } : {}),
        ...(entry.invalid === true ? { invalid: true } : {}),
    };
}

export function SchemaFieldsRenderer({
    entries,
    entity,
    parentRecordKey,
    modeKey,
    onEmbeddedTableToolbarAction,
    fieldComponents,
    showErrors = false,
}: SchemaFieldsRendererProps) {
    return (
        <>
            {entries.map((entry) => {
                const FieldComponent =
                    fieldComponents?.[entry.id] ?? loadField(entry.field.type);
                if (entry.hidden === true) {
                    return null;
                }
                const componentProps = buildFieldComponentProps(entry, {
                    entity,
                    parentRecordKey,
                    onEmbeddedTableToolbarAction,
                });
                return (
                    <div
                        key={`${entry.id}:${modeKey ?? 'default'}`}
                        className={
                            entry.disabled === true
                                ? 'space-y-2 pointer-events-none opacity-60'
                                : 'space-y-2'
                        }
                        aria-disabled={entry.disabled === true || undefined}
                    >
                        <Suspense fallback={<FieldLoading {...entry.field} />}>
                            <FieldComponent {...componentProps} />
                        </Suspense>
                        {showErrors ? (
                            <FieldError errors={entry.errors ?? []} />
                        ) : null}
                    </div>
                );
            })}
        </>
    );
}
