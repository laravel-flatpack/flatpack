'use client';

import * as React from 'react';
import type { EditorFieldUploadConfig } from '@/types/form-fields';
import type { EditorUploadRequestConfig } from '@/types/upload';

type EditorMediaUploadContextValue = {
    requestConfig?: EditorUploadRequestConfig;
    upload?: EditorFieldUploadConfig;
};

const EditorMediaUploadContext =
    React.createContext<EditorMediaUploadContextValue>({});

export function EditorMediaUploadProvider({
    children,
    value,
}: React.PropsWithChildren<{ value: EditorMediaUploadContextValue }>) {
    return (
        <EditorMediaUploadContext.Provider value={value}>
            {children}
        </EditorMediaUploadContext.Provider>
    );
}

export function useEditorMediaUpload() {
    return React.useContext(EditorMediaUploadContext);
}
