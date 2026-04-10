import { type ReactNode, useState } from 'react';
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
import { Field, FieldContent, FieldDescription, FieldLabel } from '../ui/field';

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
}) => {
    const [singleValue, setSingleValue] = useState<ComboboxObjectItem | null>(
        null,
    );
    const [multiValue, setMultiValue] = useState<string[]>([]);

    if (multiple) {
        return (
            <Field>
                {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
                <FieldContent>
                    <Combobox
                        items={[...multiItems]}
                        multiple
                        value={multiValue}
                        onValueChange={setMultiValue}
                    >
                        <ComboboxChips className="max-w-sm">
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
            {label ? <FieldLabel htmlFor={id}>{label}</FieldLabel> : null}
            <FieldContent>
                <Combobox
                    items={items}
                    value={singleValue}
                    onValueChange={setSingleValue}
                >
                    <ComboboxInput
                        id={id}
                        placeholder={singlePlaceholder}
                        showClear={singleValue != null}
                        className="w-full max-w-sm rounded-3xl"
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
