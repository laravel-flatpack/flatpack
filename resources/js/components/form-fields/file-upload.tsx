import { FileText, Loader2, Upload, X } from 'lucide-react';
import { type DragEvent, useCallback, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { resolveFormFieldLabelLayout } from '@/lib/form-field-label-layout';
import { cn } from '@/lib/utils';
import type {
    FileUploadStoredFile,
    FormFieldLabelShow,
} from '@/types/form-fields';

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
            ?.trim() ?? ''
    );
}

function messageFrom422Body(body: unknown): string | null {
    if (typeof body !== 'object' || body === null) {
        return null;
    }
    const record = body as Record<string, unknown>;
    const errors = record.errors;
    if (
        typeof errors === 'object' &&
        errors !== null &&
        !Array.isArray(errors)
    ) {
        const files = (errors as Record<string, unknown>).files;
        if (Array.isArray(files) && files.length > 0) {
            const lines = files.filter(
                (s): s is string => typeof s === 'string',
            );
            if (lines.length > 0) {
                return lines.join(' ');
            }
        }
    }
    const msg = record.message;
    if (typeof msg === 'string' && msg.trim() !== '') {
        return msg;
    }

    return null;
}

function normalizeFiles(value: unknown): FileUploadStoredFile[] {
    if (Array.isArray(value)) {
        return value.filter(
            (item): item is FileUploadStoredFile =>
                typeof item === 'object' && item !== null,
        );
    }
    if (typeof value === 'object' && value !== null) {
        return [value as FileUploadStoredFile];
    }
    return [];
}

function coercePositiveInt(value: unknown): number | undefined {
    if (typeof value === 'number' && Number.isFinite(value) && value > 0) {
        return Math.min(Math.floor(value), Number.MAX_SAFE_INTEGER);
    }
    if (typeof value === 'string' && value.trim() !== '') {
        const n = Number(value.trim());
        if (Number.isFinite(n) && n > 0) {
            return Math.min(Math.floor(n), Number.MAX_SAFE_INTEGER);
        }
    }
    return undefined;
}

/** e.g. "5MB", "1.5MB", "500KB" for the dropzone hint line. */
function formatSizeHintKb(maxSizeKb: number): string {
    const mb = maxSizeKb / 1024;
    if (mb >= 1) {
        return Number.isInteger(mb) ? `${mb}MB` : `${mb.toFixed(1)}MB`;
    }
    return `${maxSizeKb}KB`;
}

/** Public URL or path for `<img src>` from stored upload metadata. */
function storedFileImageSrc(file: FileUploadStoredFile): string {
    const url = file.url?.trim();
    if (url) {
        return url;
    }
    const path = file.path?.trim();
    if (!path) {
        return '';
    }
    if (
        path.startsWith('http://') ||
        path.startsWith('https://') ||
        path.startsWith('/')
    ) {
        return path;
    }

    return `/${path.replace(/^\/+/, '')}`;
}

/**
 * Flatpack private-file URLs use {@code ?path=relative/path/on/disk}; the pathname ends in
 * {@code /serve}, so we must not use the last URL segment as the display name.
 */
function basenameFromUrlQueryPath(rawUrl: string): string | null {
    const q = rawUrl.indexOf('?');
    if (q === -1) {
        return null;
    }
    try {
        const params = new URLSearchParams(rawUrl.slice(q + 1));
        const p = params.get('path');
        if (!p || p.trim() === '') {
            return null;
        }
        const segments = p.split('/').filter(Boolean);
        const base = segments.pop();
        return base && base !== '' ? base : null;
    } catch {
        return null;
    }
}

/**
 * Label shown in the UI: basename of stored {@code path} (matches DB / disk) first, then URL path,
 * then original upload {@code name}. Avoids showing the client filename when the persisted key differs.
 */
function storedFileDisplayName(file: FileUploadStoredFile): string {
    const fromPath = file.path?.trim();
    if (fromPath && fromPath !== '') {
        const base = fromPath.split('/').pop();
        if (base && base !== '') {
            return base;
        }
    }

    const fromUrl = file.url?.trim();
    if (fromUrl && fromUrl !== '') {
        const fromQuery = basenameFromUrlQueryPath(fromUrl);
        if (fromQuery) {
            return fromQuery;
        }

        if (fromUrl.startsWith('http://') || fromUrl.startsWith('https://')) {
            try {
                const pathname = new URL(fromUrl).pathname;
                const seg = pathname.split('/').filter(Boolean).pop();
                if (seg && seg !== '' && seg !== 'serve') {
                    return seg;
                }
            } catch {
                /* ignore malformed URL */
            }
        } else {
            const seg = fromUrl.split(/[?#]/)[0]?.split('/').pop();
            if (seg && seg !== '') {
                return seg;
            }
        }
    }

    const original = file.name?.trim();
    if (original && original !== '') {
        return original;
    }

    return 'File';
}

/** Human-readable size for file-card preview; returns null when unknown. */
function formatStoredFileSize(bytes: unknown): string | null {
    if (typeof bytes !== 'number' || !Number.isFinite(bytes) || bytes < 0) {
        return null;
    }
    if (bytes < 1024) {
        return `${Math.round(bytes)} B`;
    }
    const kb = bytes / 1024;
    if (kb < 1024) {
        return `${kb < 10 ? kb.toFixed(1) : Math.round(kb)} KB`;
    }
    const mb = kb / 1024;
    return `${mb < 10 ? mb.toFixed(1) : Math.round(mb)} MB`;
}

function fileModeDetailLine(file: FileUploadStoredFile): string | null {
    const size = formatStoredFileSize(file.size);
    const mime = file.mime_type?.trim();
    const parts = [size, mime && mime !== '' ? mime : null].filter(
        (p): p is string => p !== null && p !== '',
    );
    if (parts.length === 0) {
        return null;
    }
    return parts.join(' · ');
}

function formatDropzoneLimits(
    maxFiles: number | undefined,
    maxSizeKb: number | undefined,
): string | null {
    const parts: string[] = [];
    if (maxFiles !== undefined && maxFiles > 0) {
        parts.push(`Max ${maxFiles} file${maxFiles === 1 ? '' : 's'}`);
    }
    if (maxSizeKb !== undefined && maxSizeKb > 0) {
        parts.push(`Up to ${formatSizeHintKb(maxSizeKb)}`);
    }
    if (parts.length === 0) {
        return null;
    }
    return parts.join(' ∙ ');
}

export const FileUploadField = ({
    id,
    label,
    helperText,
    mode = 'url',
    multiple = false,
    accept,
    maxFiles,
    maxSizeKb,
    uploadEndpoint,
    fieldId,
    value,
    onValueChange,
    showLabel,
}: {
    id: string;
    label: string;
    helperText?: string;
    /**
     * `image`: same persistence as `url`; image previews. `file`: same persistence as `url`; file-card
     * preview (icon + metadata). Otherwise compact filename row when not using image previews.
     */
    mode?: 'relation' | 'url' | 'image' | 'file';
    multiple?: boolean;
    accept?: string | string[];
    maxFiles?: number;
    /** Per-field YAML limit; server may still apply a lower global cap from `config/flatpack.php`. */
    maxSizeKb?: number;
    uploadEndpoint: string;
    fieldId: string;
    value?: unknown;
    onValueChange?: (value: unknown) => void;
    showLabel?: FormFieldLabelShow;
}) => {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const files = useMemo(() => normalizeFiles(value), [value]);
    const accepted = useMemo(() => {
        if (accept === undefined) {
            return mode === 'image' ? 'image/*' : '';
        }
        if (Array.isArray(accept)) {
            if (accept.length > 0) {
                return accept.join(',');
            }

            return mode === 'image' ? 'image/*' : '';
        }
        return accept;
    }, [accept, mode]);
    const labelId = `${id}-label`;
    const labelLayout = resolveFormFieldLabelLayout(showLabel, 'stacked');

    const disabled = isUploading || uploadEndpoint.trim() === '';
    const maxFilesLimit = coercePositiveInt(maxFiles);
    const maxSizeKbLimit = coercePositiveInt(maxSizeKb);
    const limitsLine = formatDropzoneLimits(maxFilesLimit, maxSizeKbLimit);
    const hasFiles = files.length > 0;
    const canAddMoreFiles =
        multiple &&
        (maxFilesLimit === undefined || files.length < maxFilesLimit);

    const uploadSelectedFiles = useCallback(
        (selected: FileList | File[]) => {
            const list =
                selected instanceof FileList ? Array.from(selected) : selected;
            const effective = multiple ? list : list.slice(0, 1);
            if (effective.length === 0) {
                return;
            }

            const current = normalizeFiles(value);
            const allowed =
                maxFilesLimit !== undefined
                    ? Math.max(0, maxFilesLimit - current.length)
                    : effective.length;
            const toUpload = effective.slice(0, allowed);
            if (toUpload.length === 0) {
                return;
            }

            const maxKb = maxSizeKbLimit ?? null;
            if (maxKb !== null) {
                const maxBytes = maxKb * 1024;
                const oversized = toUpload.find((f) => f.size > maxBytes);
                if (oversized !== undefined) {
                    setUploadError(`Each file must be at most ${maxKb} KB.`);
                    if (inputRef.current) {
                        inputRef.current.value = '';
                    }

                    return;
                }
            }

            const formData = new FormData();
            formData.append('field', fieldId);
            for (const file of toUpload) {
                formData.append('files[]', file);
            }

            setIsUploading(true);
            setUploadError(null);
            void (async () => {
                try {
                    const response = await fetch(uploadEndpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken() !== ''
                                ? { 'X-CSRF-TOKEN': csrfToken() }
                                : {}),
                        },
                        body: formData,
                    });
                    if (!response.ok) {
                        let errMsg = 'Upload failed. Please try again.';
                        if (response.status === 422) {
                            try {
                                const errBody =
                                    (await response.json()) as unknown;
                                errMsg = messageFrom422Body(errBody) ?? errMsg;
                            } catch {
                                /* keep errMsg */
                            }
                        }
                        setUploadError(errMsg);

                        return;
                    }

                    const payload = (await response.json()) as {
                        files?: FileUploadStoredFile[];
                    };
                    const uploaded = Array.isArray(payload.files)
                        ? payload.files
                        : [];
                    const next = multiple
                        ? [...current, ...uploaded]
                        : (uploaded[0] ?? null);
                    onValueChange?.(next);
                } catch {
                    setUploadError('Upload failed. Please try again.');
                } finally {
                    setIsUploading(false);
                    if (inputRef.current) {
                        inputRef.current.value = '';
                    }
                }
            })();
        },
        [
            fieldId,
            maxFilesLimit,
            maxSizeKbLimit,
            multiple,
            onValueChange,
            uploadEndpoint,
            value,
        ],
    );

    const onDragEnter = useCallback(
        (event: DragEvent<HTMLDivElement>) => {
            if (disabled) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            setIsDragging(true);
        },
        [disabled],
    );

    const onDragLeave = useCallback(
        (event: DragEvent<HTMLDivElement>) => {
            if (disabled) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const next = event.relatedTarget;
            if (next instanceof Node && event.currentTarget.contains(next)) {
                return;
            }
            setIsDragging(false);
        },
        [disabled],
    );

    const onDragOver = useCallback(
        (event: DragEvent<HTMLDivElement>) => {
            if (disabled) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
        },
        [disabled],
    );

    const onDrop = useCallback(
        (event: DragEvent<HTMLDivElement>) => {
            if (disabled) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            setIsDragging(false);
            const dt = event.dataTransfer.files;
            if (dt?.length) {
                uploadSelectedFiles(dt);
            }
        },
        [disabled, uploadSelectedFiles],
    );

    return (
        <Field orientation={labelLayout.orientation}>
            <FieldTitle id={labelId} className={labelLayout.labelClassName}>
                {label}
            </FieldTitle>
            <FieldContent>
                <div className="flex flex-col gap-4">
                    <input
                        ref={inputRef}
                        id={id}
                        name={id}
                        type="file"
                        multiple={multiple}
                        accept={accepted}
                        className="sr-only"
                        aria-label={`Upload file for ${label}`}
                        disabled={disabled}
                        onChange={(event) => {
                            const selected = event.currentTarget.files;
                            if (!selected || selected.length === 0) {
                                return;
                            }
                            uploadSelectedFiles(selected);
                        }}
                    />

                    {!hasFiles ? (
                        <div
                            role="button"
                            tabIndex={disabled ? -1 : 0}
                            data-dragging={isDragging ? true : undefined}
                            aria-disabled={disabled || undefined}
                            className={cn(
                                'border-input data-[dragging=true]:bg-accent/50',
                                'has-[input:focus]:border-ring has-[input:focus]:ring-ring/50',
                                'has-[input:focus]:ring-[3px]',
                                'flex min-h-48 flex-col items-center justify-center gap-4 overflow-hidden rounded-sm border border-dashed p-6 text-center',
                                'outline-none transition-colors',
                                disabled
                                    ? 'cursor-not-allowed opacity-50'
                                    : 'cursor-pointer',
                            )}
                            onClick={() => {
                                if (!disabled) {
                                    inputRef.current?.click();
                                }
                            }}
                            onKeyDown={(event) => {
                                if (disabled) {
                                    return;
                                }
                                if (
                                    event.key === 'Enter' ||
                                    event.key === ' '
                                ) {
                                    event.preventDefault();
                                    inputRef.current?.click();
                                }
                            }}
                            onDragEnter={onDragEnter}
                            onDragLeave={onDragLeave}
                            onDragOver={onDragOver}
                            onDrop={onDrop}
                        >
                            {isUploading ? (
                                <Loader2 className="size-10 animate-spin text-muted-foreground" />
                            ) : (
                                <Upload
                                    className="size-10 stroke-1 text-muted-foreground"
                                    aria-hidden
                                />
                            )}
                            <p className="font-medium text-sm">
                                Drag & Drop or Choose file to upload
                            </p>
                            {limitsLine ? (
                                <p className="text-muted-foreground text-sm">
                                    {limitsLine}
                                </p>
                            ) : null}
                        </div>
                    ) : null}

                    {hasFiles && isUploading ? (
                        <div className="flex items-center gap-2 text-muted-foreground text-sm">
                            <Loader2
                                className="size-5 shrink-0 animate-spin"
                                aria-hidden
                            />
                            <span>Uploading…</span>
                        </div>
                    ) : null}

                    {/* Stored upload preview: `image` → <img>; `file` → file card; else compact row. */}
                    {files.length > 0 ? (
                        <div
                            className={
                                mode === 'image' && multiple
                                    ? 'grid grid-cols-2 gap-3 sm:grid-cols-3'
                                    : 'space-y-2'
                            }
                        >
                            {files.map((file, index) => {
                                const key =
                                    `${file.path ?? file.url ?? file.name ?? index}` +
                                    `-${index}`;
                                const displayName = storedFileDisplayName(file);
                                const imgSrc =
                                    mode === 'image'
                                        ? storedFileImageSrc(file)
                                        : '';
                                const remove = () => {
                                    if (multiple) {
                                        const next = files.filter(
                                            (_, i) => i !== index,
                                        );
                                        onValueChange?.(next);
                                        return;
                                    }
                                    onValueChange?.(null);
                                };

                                if (mode === 'image' && imgSrc !== '') {
                                    return (
                                        <div
                                            key={key}
                                            className={cn(
                                                'relative overflow-hidden rounded-md border bg-muted/20',
                                                multiple
                                                    ? 'aspect-square'
                                                    : 'flex justify-center p-2',
                                            )}
                                        >
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="absolute right-1 top-1 z-10 size-8 bg-background/80 shadow-sm"
                                                onClick={(event) => {
                                                    event.stopPropagation();
                                                    remove();
                                                }}
                                                aria-label={`Remove ${displayName}`}
                                            >
                                                <X className="size-4" />
                                            </Button>
                                            <img
                                                src={imgSrc}
                                                alt={displayName}
                                                className={
                                                    multiple
                                                        ? 'size-full object-cover'
                                                        : 'max-h-64 w-full max-w-lg object-contain'
                                                }
                                                loading="lazy"
                                                decoding="async"
                                            />
                                        </div>
                                    );
                                }

                                if (mode === 'file') {
                                    const detail = fileModeDetailLine(file);
                                    return (
                                        <div
                                            key={key}
                                            className="flex items-center gap-3 rounded-md border border-border bg-muted/35 px-3 py-2.5 text-sm shadow-sm"
                                        >
                                            <div
                                                className="flex size-10 shrink-0 items-center justify-center rounded-md border border-border bg-background"
                                                aria-hidden
                                            >
                                                <FileText className="size-5 text-muted-foreground" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium">
                                                    {displayName}
                                                </p>
                                                {detail ? (
                                                    <p className="truncate text-muted-foreground text-xs">
                                                        {detail}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="shrink-0"
                                                onClick={remove}
                                                aria-label={`Remove ${displayName}`}
                                            >
                                                <X className="size-4" />
                                            </Button>
                                        </div>
                                    );
                                }

                                return (
                                    <div
                                        key={key}
                                        className="flex items-center justify-between rounded-md border p-2 text-sm"
                                    >
                                        <span className="truncate">
                                            {displayName}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={remove}
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                    ) : null}

                    {hasFiles && !isUploading && multiple && canAddMoreFiles ? (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={disabled}
                                onClick={(event) => {
                                    event.stopPropagation();
                                    inputRef.current?.click();
                                }}
                            >
                                Add files
                            </Button>
                        </div>
                    ) : null}

                    {uploadError ? (
                        <p className="text-sm text-destructive">
                            {uploadError}
                        </p>
                    ) : null}
                    {helperText ? (
                        <FieldDescription>{helperText}</FieldDescription>
                    ) : null}
                </div>
            </FieldContent>
        </Field>
    );
};
