import type { CSSProperties } from 'react';
import { parseImageColumnOptions } from '@/lib/data-table-utils';
import { listCellImageSrc } from '@/lib/file-storage';
import { cn } from '@/lib/utils';
import type { FlatpackDataTableColumn } from '@/types/data-table';

export function ImageCell({
    column,
    value,
}: {
    column: FlatpackDataTableColumn;
    value: unknown;
}) {
    const src = listCellImageSrc(value);
    if (src === '') {
        return <span className="text-muted-foreground">—</span>;
    }

    const opts =
        column.type === 'image' ? parseImageColumnOptions(column.options) : {};

    const style: CSSProperties = {};
    if (opts.width != null) {
        style.width = opts.width;
    }
    if (opts.height != null) {
        style.height = opts.height;
    }
    if (opts.aspect_ratio != null) {
        style.aspectRatio =
            typeof opts.aspect_ratio === 'number'
                ? opts.aspect_ratio
                : opts.aspect_ratio;
    }

    const hasCustomLayout =
        opts.width != null || opts.height != null || opts.aspect_ratio != null;

    const onlyAspectRatio =
        opts.aspect_ratio != null && opts.width == null && opts.height == null;

    return (
        <img
            src={src}
            alt=""
            className={cn(
                'max-w-none rounded object-cover',
                !hasCustomLayout && 'h-8 w-8',
                onlyAspectRatio && 'w-8',
            )}
            style={Object.keys(style).length > 0 ? style : undefined}
        />
    );
}
