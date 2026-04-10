import * as React from 'react';
import { toast } from 'sonner';
import { z } from 'zod';

/** Shape expected by media placeholders after a successful upload. */
export type UploadedFile = {
    key: string;
    name: string;
    size: number;
    type: string;
    url: string;
    appUrl?: string;
};

interface UseUploadFileProps {
    onUploadComplete?: (file: UploadedFile) => void;
    onUploadError?: (error: unknown) => void;
}

/**
 * Local dev upload: returns a blob URL for previews. Replace with your API
 * (e.g. Laravel signed upload) when you deploy.
 */
export function useUploadFile({
    onUploadComplete,
    onUploadError,
}: UseUploadFileProps = {}) {
    const [uploadedFile, setUploadedFile] = React.useState<UploadedFile>();
    const [uploadingFile, setUploadingFile] = React.useState<File>();
    const [progress, setProgress] = React.useState(0);
    const [isUploading, setIsUploading] = React.useState(false);

    async function uploadFile(file: File) {
        setIsUploading(true);
        setUploadingFile(file);

        try {
            const objectUrl = URL.createObjectURL(file);
            const result: UploadedFile = {
                key: `local-${file.name}-${String(file.lastModified)}`,
                name: file.name,
                size: file.size,
                type: file.type,
                url: objectUrl,
                appUrl: objectUrl,
            };

            let p = 0;
            while (p < 100) {
                await new Promise((r) => setTimeout(r, 40));
                p += 8;
                setProgress(Math.min(p, 100));
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

export function getErrorMessage(err: unknown) {
    if (err instanceof z.ZodError) {
        return err.issues.map((issue) => issue.message).join('\n');
    }
    if (err instanceof Error) {
        return err.message;
    }
    return 'Something went wrong, please try again later.';
}
