import * as React from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { messageFrom422Body } from '@/lib/file-storage';
import type { UploadedFile, UseUploadFileProps } from '@/types/upload';

export function useUploadFile({
    config,
    onUploadComplete,
    onUploadError,
}: UseUploadFileProps = {}) {
    const [uploadedFile, setUploadedFile] = React.useState<UploadedFile>();
    const [uploadingFile, setUploadingFile] = React.useState<File>();
    const [progress, setProgress] = React.useState(0);
    const [isUploading, setIsUploading] = React.useState(false);

    const maxSizeKb = config?.maxSizeKb;
    const fieldId = config?.fieldId?.trim() ?? '';
    const uploadEndpoint = config?.uploadEndpoint?.trim() ?? '';

    async function uploadFile(file: File) {
        if (uploadEndpoint === '' || fieldId === '') {
            const err = new Error('Editor upload is not configured for this field.');
            toast.error(err.message);
            onUploadError?.(err);
            throw err;
        }

        if (typeof maxSizeKb === 'number' && maxSizeKb > 0) {
            const maxBytes = maxSizeKb * 1024;
            if (file.size > maxBytes) {
                const err = new Error(`Each file must be at most ${maxSizeKb} KB.`);
                toast.error(err.message);
                onUploadError?.(err);
                throw err;
            }
        }

        setIsUploading(true);
        setUploadingFile(file);
        setProgress(15);

        try {
            const formData = new FormData();
            formData.append('field', fieldId);
            formData.append('files[]', file);

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
                        const errBody = (await response.json()) as unknown;
                        errMsg = messageFrom422Body(errBody) ?? errMsg;
                    } catch {
                        /* keep errMsg */
                    }
                }
                throw new Error(errMsg);
            }

            setProgress(100);
            const payload = (await response.json()) as {
                files?: UploadedFile[];
            };
            const uploaded = Array.isArray(payload.files) ? payload.files : [];
            const result = uploaded[0];
            if (result == null) {
                throw new Error('Upload failed. Please try again.');
            }

            setUploadedFile(result);
            onUploadComplete?.(result);
            return result;
        } catch (error) {
            const message = getErrorMessage(error);
            toast.error(
                message.length > 0
                    ? message
                    : 'Something went wrong, please try again later.',
            );
            onUploadError?.(error);
            throw error;
        } finally {
            setProgress(0);
            setIsUploading(false);
            setUploadingFile(undefined);
        }
    }

    return {
        isUploading,
        progress,
        uploadedFile,
        uploadFile,
        uploadingFile,
    };
}

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content')
            ?.trim() ?? ''
    );
}

export function getErrorMessage(err: unknown) {
    if (err instanceof z.ZodError) {
        return err.issues.map((issue) => issue.message).join('\n');
    }
    if (err instanceof Error) {
        return err.message;
    }
    return 'Something went wrong, please try again later.';
}
