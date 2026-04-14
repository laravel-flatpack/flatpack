export type UploadedFile = {
    key: string;
    name: string;
    size: number;
    type: string;
    url: string;
    appUrl?: string;
};

export type UseUploadFileProps = {
    onUploadComplete?: (file: UploadedFile) => void;
    onUploadError?: (error: unknown) => void;
};
