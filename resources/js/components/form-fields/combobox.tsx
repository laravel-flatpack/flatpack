import {
    type ReactNode,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
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
    remote = false,
    remoteEndpoint,
    remoteFieldId,
    remotePerPage = 20,
    onValueChange,
    invalid = false,
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
    remote?: boolean;
    remoteEndpoint?: string;
    remoteFieldId?: string;
    remotePerPage?: number;
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
            const next = Array.isArray(value)
                ? value
                      .filter(
                          (item): item is string | number =>
                              typeof item === 'string' ||
                              typeof item === 'number',
                      )
                      .map((item) => String(item))
                : [];
            setMultiValue(next);
        } else {
            setMultiValue([]);
        }
    }, [multiple, value]);

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
                                aria-invalid={invalid || undefined}
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
