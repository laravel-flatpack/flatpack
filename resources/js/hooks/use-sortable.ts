import * as React from 'react';

type SortableItem = {
    id: number | string;
    [key: string]: unknown;
};

type UseSortableOptions<T extends SortableItem> = {
    endpoint: (item: T) => string;
    sortColumn: string;
    onError?: () => void;
    onItemsChange?: (items: T[]) => void;
    reindexOnReorder?: boolean;
};

function asSortableNumber(value: unknown): number {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }
    if (typeof value === 'string') {
        const parsed = Number(value);
        if (Number.isFinite(parsed)) {
            return parsed;
        }
    }

    return Number.MAX_SAFE_INTEGER;
}

function reindexItems<T extends SortableItem>(
    items: T[],
    sortColumn: string,
): T[] {
    return items.map((item, index) => ({
        ...item,
        [sortColumn]: index + 1,
    }));
}

function hasSameOrder<T extends SortableItem>(
    left: T[],
    right: T[],
    sortColumn: string,
): boolean {
    if (left.length !== right.length) {
        return false;
    }
    return left.every((item, index) => {
        const other = right[index];
        if (other == null) {
            return false;
        }
        return item.id === other.id && item[sortColumn] === other[sortColumn];
    });
}

export function useSortable<T extends SortableItem>(
    initialItems: T[],
    options: UseSortableOptions<T>,
) {
    const {
        endpoint,
        sortColumn,
        onError,
        onItemsChange,
        reindexOnReorder = true,
    } = options;
    const previousItemsRef = React.useRef<T[]>([]);
    const sourceSignatureRef = React.useRef<string>('');

    const csrfToken = React.useMemo(() => {
        if (typeof document === 'undefined') {
            return '';
        }
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
        return token?.trim() ?? '';
    }, []);
    const [isReordering, setIsReordering] = React.useState(false);
    const [items, setItems] = React.useState<T[]>(() => {
        const sorted = [...initialItems].sort(
            (a, b) =>
                asSortableNumber(a[sortColumn]) -
                asSortableNumber(b[sortColumn]),
        );
        return reindexOnReorder ? reindexItems(sorted, sortColumn) : sorted;
    });

    React.useLayoutEffect(() => {
        const sourceSignature = JSON.stringify(
            initialItems.map((item) => [item.id, item[sortColumn]]),
        );
        if (sourceSignatureRef.current === sourceSignature) {
            return;
        }
        sourceSignatureRef.current = sourceSignature;
        const sorted = [...initialItems].sort(
            (a, b) =>
                asSortableNumber(a[sortColumn]) -
                asSortableNumber(b[sortColumn]),
        );
        const next = reindexOnReorder
            ? reindexItems(sorted, sortColumn)
            : sorted;
        setItems((previous) =>
            hasSameOrder(previous, next, sortColumn) ? previous : next,
        );
    }, [initialItems, reindexOnReorder, sortColumn]);

    const handleReorder = React.useCallback(
        (
            draggedId: number | string,
            newPosition: number,
            persistedPosition?: number,
        ) => {
            const draggedIndex = items.findIndex(
                (item) => item.id === draggedId,
            );
            if (draggedIndex < 0) {
                return;
            }

            const boundedLocalPosition = Math.max(
                1,
                Math.min(items.length, newPosition),
            );
            const targetIndex = boundedLocalPosition - 1;
            if (draggedIndex === targetIndex) {
                return;
            }

            const draggedItem = items[draggedIndex];
            if (draggedItem == null) {
                return;
            }

            previousItemsRef.current = items;
            const reordered = [...items];
            reordered.splice(draggedIndex, 1);
            reordered.splice(targetIndex, 0, draggedItem);
            const optimisticItems = reindexOnReorder
                ? reindexItems(reordered, sortColumn)
                : reordered;
            setItems(optimisticItems);
            onItemsChange?.(optimisticItems);

            const url = endpoint(draggedItem).trim();
            if (url === '') {
                return;
            }

            setIsReordering(true);
            void (async () => {
                try {
                    const requestedPersistedPosition = Math.max(
                        1,
                        Math.round(persistedPosition ?? boundedLocalPosition),
                    );
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken !== ''
                                ? { 'X-CSRF-TOKEN': csrfToken }
                                : {}),
                        },
                        body: JSON.stringify({
                            position: requestedPersistedPosition,
                        }),
                    });
                    if (!response.ok) {
                        throw new Error('Reorder request failed');
                    }
                } catch {
                    setItems(previousItemsRef.current);
                    onItemsChange?.(previousItemsRef.current);
                    onError?.();
                } finally {
                    setIsReordering(false);
                }
            })();
        },
        [
            csrfToken,
            endpoint,
            items,
            onError,
            onItemsChange,
            reindexOnReorder,
            sortColumn,
        ],
    );

    return {
        items,
        handleReorder,
        isReordering,
    };
}
