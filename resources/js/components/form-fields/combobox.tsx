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
/** Seed chip labels from RelationRow[] (hydrated edit form) using {@code relation_name} when present. */
function mergeRelationRowsIntoLabelRecord(
    value: unknown,
    relationValueKey: string,
    relationLabelKey: string | undefined,
    into: Record<string, string>,
): void {
    if (!Array.isArray(value)) {
        return;
    }
    const labelAttr =
        relationLabelKey != null && relationLabelKey.trim() !== ''
            ? relationLabelKey.trim()
            : 'name';

    for (const row of value) {
        if (row === null || typeof row !== 'object') {
            continue;
        }
        const r = row as Record<string, unknown>;
        const rawId = r[relationValueKey] ?? r.id;
        if (rawId === null || rawId === undefined) {
            continue;
        }
        const rowId = String(rawId).trim();
        if (rowId === '') {
            continue;
        }

        const preferred = r[labelAttr];
        if (typeof preferred === 'string' && preferred.trim() !== '') {
            into[rowId] = preferred.trim();
            continue;
        }

        const fallback = (['name', 'title', 'label'] as const)
            .map((k) => r[k])
            .find((x): x is string => typeof x === 'string' && x.trim() !== '');
        if (fallback !== undefined) {
            into[rowId] = fallback.trim();
        }
    }
}

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

function mergeMultiSelectionIntoLabelMap(
    selected: unknown,
    ids: readonly string[],
    normalizedItems: readonly ComboboxObjectItem[],
    prev: Record<string, string>,
): Record<string, string> {
    const next = { ...prev };

    if (Array.isArray(selected)) {
        for (const entry of selected) {
            if (
                entry !== null &&
                typeof entry === 'object' &&
                'value' in entry
            ) {
                const o = entry as ComboboxObjectItem;
                const idStr = String(o.value).trim();
                if (idStr === '') {
                    continue;
                }
                const direct =
                    typeof o.label === 'string' ? o.label.trim() : '';
                const fromItems = normalizedItems.find(
                    (it) => String(it.value) === idStr,
                )?.label;
                const resolved =
                    direct !== ''
                        ? direct
                        : typeof fromItems === 'string'
                          ? fromItems.trim()
                          : '';
                if (resolved !== '') {
                    next[idStr] = resolved;
                }
                continue;
            }
            if (typeof entry === 'string' || typeof entry === 'number') {
                const idStr = String(entry).trim();
                if (idStr === '') {
                    continue;
                }
                const fromItems = normalizedItems.find(
                    (it) => String(it.value) === idStr,
                )?.label;
                if (typeof fromItems === 'string' && fromItems.trim() !== '') {
                    next[idStr] = fromItems.trim();
                }
            }
        }
    }

    for (const sid of ids) {
        if (next[sid] !== undefined && next[sid].trim() !== '') {
            continue;
        }
        const hit = normalizedItems.find(
            (it) => String(it.value) === sid,
        )?.label;
        if (typeof hit === 'string' && hit.trim() !== '') {
            next[sid] = hit.trim();
        }
    }

    return next;
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
    remoteFieldParamKey = 'field',
    remoteSearchParamKey = 'q',
    remoteBaseParams,
    remotePerPage = 20,
    useRelationRowPayload = false,
    relationValueKey = 'id',
    /** Related model attribute for labels (YAML {@code relation_name}); hydrates chip text from RelationRow[]. */
    relationLabelKey,
    emitObject = false,
    portalContainer,
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
    remoteFieldParamKey?: string | null;
    remoteSearchParamKey?: string;
    remoteBaseParams?: Record<string, string>;
    remotePerPage?: number;
    /** Multi + relation: submit {@link idsToRelationRows} instead of string[]. */
    useRelationRowPayload?: boolean;
    relationValueKey?: string;
    relationLabelKey?: string;
    emitObject?: boolean;
    portalContainer?: HTMLElement | null;
    onValueChange?: (value: unknown) => void;
    invalid?: boolean;
}) => {
    const [multiValue, setMultiValue] = useState<string[]>([]);
    /** Stable value→label map; remote search replaces {@link normalizedItems} often, so chips cannot rely on it alone. */
    const [labelByValue, setLabelByValue] = useState<Record<string, string>>(
        {},
    );
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
                ...(remoteBaseParams ?? {}),
                page: String(page),
                per_page: String(remotePerPage),
            });
            const trimmedQuery = currentQuery.trim();
            if (trimmedQuery !== '') {
                params.set(remoteSearchParamKey, trimmedQuery);
            }
            if (remoteFieldParamKey != null && remoteFieldParamKey !== '') {
                params.set(remoteFieldParamKey, remoteFieldId ?? id);
            }
            const trimmedSelectedValue = selectedValue.trim();
            if (trimmedSelectedValue !== '') {
                params.set('selected', trimmedSelectedValue);
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
            remoteFieldParamKey,
            remoteSearchParamKey,
            remoteBaseParams,
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

    useEffect(() => {
        setLabelByValue((prev) => {
            const next = { ...prev };
            for (const o of normalizedItems) {
                const k = String(o.value).trim();
                if (k !== '') {
                    next[k] = o.label;
                }
            }
            return next;
        });
    }, [normalizedItems]);

    useEffect(() => {
        if (!multiple || !useRelationRowPayload) {
            return;
        }
        setLabelByValue((prev) => {
            const next = { ...prev };
            mergeRelationRowsIntoLabelRecord(
                value,
                relationValueKey,
                relationLabelKey,
                next,
            );
            return next;
        });
    }, [
        multiple,
        useRelationRowPayload,
        value,
        relationValueKey,
        relationLabelKey,
    ]);

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
                            setLabelByValue((prev) =>
                                mergeMultiSelectionIntoLabelMap(
                                    v,
                                    ids,
                                    normalizedItems,
                                    prev,
                                ),
                            );
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
                                        labelByValue[id] ??
                                        normalizedItems.find(
                                            (o) => String(o.value) === id,
                                        )?.label ??
                                        id;
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
                        <ComboboxContent portalContainer={portalContainer}>
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
                        if (emitObject && v != null) {
                            onValueChange?.({
                                value: String(v.value),
                                label: String(v.label),
                            });
                            return;
                        }
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
                    <ComboboxContent portalContainer={portalContainer}>
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
