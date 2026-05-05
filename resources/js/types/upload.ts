import type { FileUploadStoredFile } from '@/types/form-fields';

export type UploadedFile = FileUploadStoredFile;

export type EditorUploadRequestConfig = {
    fieldId: string;
    uploadEndpoint: string;
    maxSizeKb?: number;
};

export type UseUploadFileProps = {
    config?: EditorUploadRequestConfig;
    onUploadComplete?: (file: UploadedFile) => void;
    onUploadError?: (error: unknown) => void;
};
