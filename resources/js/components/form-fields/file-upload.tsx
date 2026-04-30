import { Loader2, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldTitle,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { FileUploadStoredFile } from '@/types/form-fields';

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
            ?.trim() ?? ''
    );
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

export const FileUploadField = ({
    id,
    label,
    helperText,
    multiple = false,
    accept,
    maxFiles,
    uploadEndpoint,
    fieldId,
    value,
    onValueChange,
}: {
    id: string;
    label: string;
    helperText?: string;
    multiple?: boolean;
    accept?: string | string[];
    maxFiles?: number;
    uploadEndpoint: string;
    fieldId: string;
    value?: unknown;
    onValueChange?: (value: unknown) => void;
}) => {
    const [isUploading, setIsUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const files = useMemo(() => normalizeFiles(value), [value]);
    const accepted = Array.isArray(accept) ? accept.join(',') : (accept ?? '');

    return (
        <Field>
            <FieldTitle>{label}</FieldTitle>
            <FieldContent>
                <div className="space-y-3">
                    <div className="flex items-center gap-2">
                        <Input
                            id={id}
                            name={id}
                            type="file"
                            multiple={multiple}
                            accept={accepted}
                            disabled={
                                isUploading || uploadEndpoint.trim() === ''
                            }
                            onChange={(event) => {
                                const selected = event.currentTarget.files;
                                if (!selected || selected.length === 0) {
                                    return;
                                }

                                const current = normalizeFiles(value);
                                const selectedFiles = Array.from(selected);
                                const allowed =
                                    typeof maxFiles === 'number' && maxFiles > 0
                                        ? Math.max(0, maxFiles - current.length)
                                        : selectedFiles.length;
                                const toUpload = selectedFiles.slice(
                                    0,
                                    allowed,
                                );
                                if (toUpload.length === 0) {
                                    return;
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
                                        const response = await fetch(
                                            uploadEndpoint,
                                            {
                                                method: 'POST',
                                                headers: {
                                                    Accept: 'application/json',
                                                    'X-Requested-With':
                                                        'XMLHttpRequest',
                                                    ...(csrfToken() !== ''
                                                        ? {
                                                              'X-CSRF-TOKEN':
                                                                  csrfToken(),
                                                          }
                                                        : {}),
                                                },
                                                body: formData,
                                            },
                                        );
                                        if (!response.ok) {
                                            throw new Error('Upload failed');
                                        }

                                        const payload =
                                            (await response.json()) as {
                                                files?: FileUploadStoredFile[];
                                            };
                                        const uploaded = Array.isArray(
                                            payload.files,
                                        )
                                            ? payload.files
                                            : [];
                                        const next = multiple
                                            ? [...current, ...uploaded]
                                            : (uploaded[0] ?? null);
                                        onValueChange?.(next);
                                    } catch {
                                        setUploadError(
                                            'Upload failed. Please try again.',
                                        );
                                    } finally {
                                        setIsUploading(false);
                                        event.currentTarget.value = '';
                                    }
                                })();
                            }}
                        />
                        {isUploading ? (
                            <Loader2 className="size-4 animate-spin text-muted-foreground" />
                        ) : null}
                    </div>

                    {files.length > 0 ? (
                        <div className="space-y-2">
                            {files.map((file, index) => {
                                const key =
                                    `${file.path ?? file.url ?? file.name ?? index}` +
                                    `-${index}`;
                                const name =
                                    file.name ??
                                    file.path ??
                                    file.url ??
                                    'File';
                                return (
                                    <div
                                        key={key}
                                        className="flex items-center justify-between rounded-md border p-2 text-sm"
                                    >
                                        <span className="truncate">{name}</span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => {
                                                if (multiple) {
                                                    const next = files.filter(
                                                        (_, i) => i !== index,
                                                    );
                                                    onValueChange?.(next);
                                                    return;
                                                }
                                                onValueChange?.(null);
                                            }}
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                );
                            })}
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
