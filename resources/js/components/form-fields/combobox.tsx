import {
    type ReactNode,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { idsToRelationRows, relationRowsToIds } from '@/lib/relation-row-value';
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

function dedupeItems(items: ComboboxObjectItem[]): ComboboxObjectItem[] {
    const map = new Map<string, ComboboxObjectItem>();
    for (const item of items) {
        if (item.value.trim() === '' || item.label.trim() === '') {
            continue;
        }
        if (!map.has(item.value)) {
            map.set(item.value, item);
        }
    }

    return [...map.values()];
}

/**
 * Base UI multi-select passes selected entries as {@link ComboboxObjectItem} objects;
 * form state and chips expect string ids (values).
 */
function comboboxMultipleSelectionToIds(selected: unknown): string[] {
    if (!Array.isArray(selected)) {
        return [];
    }
    const out: string[] = [];
    for (const entry of selected) {
        if (entry !== null && typeof entry === 'object' && 'value' in entry) {
            const raw = (entry as ComboboxObjectItem).value;
            const s = String(raw).trim();
            if (s !== '') {
                out.push(s);
            }
        } else if (typeof entry === 'string' || typeof entry === 'number') {
            const s = String(entry).trim();
            if (s !== '') {
                out.push(s);
            }
        }
    }
    return out;
}

export const ComboboxField = ({
    id,
    label,
    multiple,
    items,
    singlePlaceholder,
    multiPlaceholder,
    singleDescription,
    multiDescription,
    value,
    remote = false,
    remoteEndpoint,
    remoteFieldId,
    remotePerPage = 20,
    useRelationRowPayload = false,
    relationValueKey = 'id',
    onValueChange,
    invalid = false,
}: {
    id: string;
    label: string;
    multiple: boolean;
    items: ComboboxObjectItem[];
    singlePlaceholder: string;
    multiPlaceholder: string;
    singleDescription?: ReactNode;
    multiDescription?: ReactNode;
    value?: unknown;
    remote?: boolean;
    remoteEndpoint?: string;
    remoteFieldId?: string;
    remotePerPage?: number;
    /** Multi + relation: submit {@link idsToRelationRows} instead of string[]. */
    useRelationRowPayload?: boolean;
    relationValueKey?: string;
    onValueChange?: (value: unknown) => void;
    invalid?: boolean;
}) => {
    const [multiValue, setMultiValue] = useState<string[]>([]);
    const [query, setQuery] = useState('');
    const [remoteItems, setRemoteItems] = useState<ComboboxObjectItem[]>([]);
    const [remoteLoading, setRemoteLoading] = useState(false);
    const [remotePage, setRemotePage] = useState(1);
    const [remoteHasMore, setRemoteHasMore] = useState(false);
    const requestIdRef = useRef(0);
    const isFetchingNextPageRef = useRef(false);
    const labelId = `${id}-label`;
    const normalizedItems = useMemo(
        () => (remote ? remoteItems : items),
        [items, remote, remoteItems],
    );
    /** Multi-select: hide options already chosen so duplicates cannot be added. */
    const selectableItems = useMemo(() => {
        if (!multiple) {
            return normalizedItems;
        }
        const selected = new Set(multiValue.map((id) => String(id)));
        return normalizedItems.filter((o) => !selected.has(String(o.value)));
    }, [multiple, multiValue, normalizedItems]);
    const selectedValue =
        typeof value === 'string' || typeof value === 'number'
            ? String(value)
            : '';

    const loadRemotePage = useCallback(
        async (page: number, append: boolean, currentQuery: string) => {
            if (!remote || !remoteEndpoint) {
                return;
            }
            if (append && isFetchingNextPageRef.current) {
                return;
            }
            if (append) {
                isFetchingNextPageRef.current = true;
            }

            const params = new URLSearchParams({
                field: remoteFieldId ?? id,
                page: String(page),
                per_page: String(remotePerPage),
                q: currentQuery,
            });
            if (selectedValue !== '') {
                params.set('selected', selectedValue);
            }

            const requestId = requestIdRef.current + 1;
            requestIdRef.current = requestId;
            setRemoteLoading(true);
            try {
                const response = await fetch(
                    `${remoteEndpoint}?${params.toString()}`,
                );
                if (!response.ok) {
                    return;
                }

                const payload = (await response.json()) as {
                    data?: Array<{ value: string; label: string }>;
                    meta?: { has_more?: boolean; page?: number };
                };
                if (requestId !== requestIdRef.current) {
                    return;
                }
                const nextItems = Array.isArray(payload.data)
                    ? payload.data
                    : [];
                setRemoteItems((current) =>
                    dedupeItems(
                        append ? [...current, ...nextItems] : nextItems,
                    ),
                );
                setRemoteHasMore(payload.meta?.has_more === true);
                setRemotePage(payload.meta?.page ?? page);
            } finally {
                if (append) {
                    isFetchingNextPageRef.current = false;
                }
                setRemoteLoading(false);
            }
        },
        [
            id,
            remote,
            remoteEndpoint,
            remoteFieldId,
            remotePerPage,
            selectedValue,
        ],
    );

    useEffect(() => {
        if (multiple) {
            const next = useRelationRowPayload
                ? relationRowsToIds(value, relationValueKey)
                : Array.isArray(value)
                  ? value
                        .filter(
                            (item): item is string | number =>
                                typeof item === 'string' ||
                                typeof item === 'number',
                        )
                        .map((item) => String(item))
                  : [];
            setMultiValue([...new Set(next)]);
        } else {
            setMultiValue([]);
        }
    }, [multiple, value, useRelationRowPayload, relationValueKey]);

    useEffect(() => {
        if (!remote || !remoteEndpoint) {
            return;
        }

        const timer = window.setTimeout(() => {
            void loadRemotePage(1, false, query);
        }, 250);

        return () => window.clearTimeout(timer);
    }, [loadRemotePage, query, remote, remoteEndpoint]);

    const singleValue = useMemo(() => {
        if (
            (typeof value !== 'string' && typeof value !== 'number') ||
            String(value).trim() === ''
        ) {
            return null;
        }

        const valueAsString = String(value);
        return (
            normalizedItems.find(
                (item) => String(item.value) === valueAsString,
            ) ?? null
        );
    }, [normalizedItems, value]);

    const handleListScroll = useCallback(
        (event: React.UIEvent<HTMLElement>) => {
            if (!remote || !remoteHasMore || remoteLoading) {
                return;
            }

            const element = event.currentTarget;
            const distanceToBottom =
                element.scrollHeight - element.scrollTop - element.clientHeight;
            if (distanceToBottom > 48) {
                return;
            }

            void loadRemotePage(remotePage + 1, true, query);
        },
        [
            loadRemotePage,
            query,
            remote,
            remoteHasMore,
            remoteLoading,
            remotePage,
        ],
    );

    if (multiple) {
        return (
            <Field>
                {label ? <FieldTitle id={labelId}>{label}</FieldTitle> : null}
                <FieldContent>
                    <Combobox
                        items={selectableItems}
                        multiple
                        value={multiValue}
                        onValueChange={(v) => {
                            const ids = [
                                ...new Set(comboboxMultipleSelectionToIds(v)),
                            ];
                            setMultiValue(ids);
                            if (useRelationRowPayload) {
                                onValueChange?.(
                                    idsToRelationRows(ids, relationValueKey),
                                );
                            } else {
                                onValueChange?.(ids);
                            }
                        }}
                    >
                        <ComboboxChips className="w-full">
                            <ComboboxValue>
                                {multiValue.map((item) => {
                                    const id = String(item);
                                    const chipLabel =
                                        normalizedItems.find(
                                            (o) => String(o.value) === id,
                                        )?.label ?? id;
                                    return (
                                        <ComboboxChip key={id}>
                                            {chipLabel}
                                        </ComboboxChip>
                                    );
                                })}
                            </ComboboxValue>
                            <ComboboxChipsInput
                                id={id}
                                placeholder={multiPlaceholder}
                                aria-labelledby={label ? labelId : undefined}
                                aria-invalid={invalid || undefined}
                                onChange={(event) => {
                                    setQuery(event.currentTarget.value);
                                }}
                            />
                        </ComboboxChips>
                        <ComboboxContent>
                            <ComboboxList
                                onScroll={remote ? handleListScroll : undefined}
                            >
                                {(item: ComboboxObjectItem) => (
                                    <ComboboxItem key={item.value} value={item}>
                                        {item.label}
                                    </ComboboxItem>
                                )}
                            </ComboboxList>
                            {remoteLoading ? (
                                <div className="px-3 py-2 text-sm text-muted-foreground">
                                    Loading...
                                </div>
                            ) : null}
                            <ComboboxEmpty>
                                {remoteLoading ? 'Loading...' : 'No matches'}
                            </ComboboxEmpty>
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
                    items={normalizedItems}
                    value={singleValue}
                    onValueChange={(v) => {
                        onValueChange?.(v?.value ?? null);
                    }}
                >
                    <ComboboxInput
                        id={id}
                        placeholder={singlePlaceholder}
                        showClear={singleValue != null}
                        className="w-full rounded-3xl"
                        loading={remoteLoading}
                        aria-labelledby={label ? labelId : undefined}
                        aria-invalid={invalid || undefined}
                        onChange={(event) => {
                            setQuery(event.currentTarget.value);
                        }}
                    />
                    <ComboboxContent>
                        <ComboboxList
                            onScroll={remote ? handleListScroll : undefined}
                        >
                            {(item: ComboboxObjectItem) => (
                                <ComboboxItem key={item.value} value={item}>
                                    {item.label}
                                </ComboboxItem>
                            )}
                        </ComboboxList>
                        {remoteLoading ? (
                            <div className="px-3 py-2 text-sm text-muted-foreground">
                                Loading...
                            </div>
                        ) : null}
                        <ComboboxEmpty>
                            {remoteLoading ? 'Loading...' : 'No matches'}
                        </ComboboxEmpty>
                    </ComboboxContent>
                </Combobox>
                {singleDescription ? (
                    <FieldDescription>{singleDescription}</FieldDescription>
                ) : null}
            </FieldContent>
        </Field>
    );
};
