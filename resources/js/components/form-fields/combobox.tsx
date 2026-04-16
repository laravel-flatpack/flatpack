import { type ReactNode, useLayoutEffect, useState } from 'react';
import {
    Combobox,
    ComboboxChip,
    ComboboxChips,
    ComboboxChipsInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
    ComboboxValue,
} from '../ui/combobox';
import { Field, FieldContent, FieldDescription, FieldTitle } from '../ui/field';

export type ComboboxObjectItem = { value: string; label: string };

export const ComboboxField = ({
    id,
    label,
    multiple,
    items,
    multiItems,
    singlePlaceholder,
    multiPlaceholder,
    singleDescription,
    multiDescription,
    value,
    onValueChange,
}: {
    id: string;
    label: string;
    multiple: boolean;
    items: ComboboxObjectItem[];
    multiItems: readonly string[];
    singlePlaceholder: string;
    multiPlaceholder: string;
    singleDescription?: ReactNode;
    multiDescription?: ReactNode;
    value?: unknown;
    onValueChange?: (value: unknown) => void;
}) => {
    const [singleValue, setSingleValue] = useState<ComboboxObjectItem | null>(
        null,
    );
    const [multiValue, setMultiValue] = useState<string[]>([]);
    const labelId = `${id}-label`;

    useLayoutEffect(() => {
        if (multiple) {
            const next = Array.isArray(value)
                ? value.filter(
                      (item): item is string => typeof item === 'string',
                  )
                : [];
            setMultiValue(next);
            return;
        }

        if (typeof value !== 'string' || value.trim() === '') {
            setSingleValue(null);
            return;
        }

        setSingleValue(items.find((item) => item.value === value) ?? null);
    }, [items, multiple, value]);

    if (multiple) {
        return (
            <Field>
                {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
                <FieldContent>
                    <Combobox
                        items={[...multiItems]}
                        multiple
                        value={multiValue}
                        onValueChange={(v) => {
                            setMultiValue(v);
                            onValueChange?.(v);
                        }}
                    >
                        <ComboboxChips className="w-full">
                            <ComboboxValue>
                                {multiValue.map((item) => (
                                    <ComboboxChip key={item}>
                                        {item}
                                    </ComboboxChip>
                                ))}
                            </ComboboxValue>
                            <ComboboxChipsInput
                                id={id}
                                placeholder={multiPlaceholder}
                                aria-labelledby={label ? labelId : undefined}
                            />
                        </ComboboxChips>
                        <ComboboxContent>
                            <ComboboxEmpty>No matches</ComboboxEmpty>
                            <ComboboxList>
                                {(item: string) => (
                                    <ComboboxItem key={item} value={item}>
                                        {item}
                                    </ComboboxItem>
                                )}
                            </ComboboxList>
                        </ComboboxContent>
                    </Combobox>
                    {multiDescription ? (
                        <FieldDescription>{multiDescription}</FieldDescription>
                    ) : null}
                </FieldContent>
            </Field>
        );
    }

    return (
        <Field>
            {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
            <FieldContent>
                <Combobox
                    items={items}
                    value={singleValue}
                    onValueChange={(v) => {
                        setSingleValue(v);
                        onValueChange?.(v);
                    }}
                >
                    <ComboboxInput
                        id={id}
                        placeholder={singlePlaceholder}
                        showClear={singleValue != null}
                        className="w-full max-w-sm rounded-3xl"
                        aria-labelledby={label ? labelId : undefined}
                    />
                    <ComboboxContent>
                        <ComboboxList>
                            {(item: ComboboxObjectItem) => (
                                <ComboboxItem key={item.value} value={item}>
                                    {item.label}
                                </ComboboxItem>
                            )}
                        </ComboboxList>
                        <ComboboxEmpty>No matches</ComboboxEmpty>
                    </ComboboxContent>
                </Combobox>
                {singleDescription ? (
                    <FieldDescription>{singleDescription}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
