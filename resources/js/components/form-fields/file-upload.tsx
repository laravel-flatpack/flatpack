import { FileText, Loader2, Upload, X } from 'lucide-react';
import { type DragEvent, useCallback, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import {
    coercePositiveInt,
    fileModeDetailLine,
    formatDropzoneLimits,
    messageFrom422Body,
    normalizeFiles,
    storedFileDisplayName,
    storedFileImageSrc,
} from '@/lib/file-storage';
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
