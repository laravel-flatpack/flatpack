'use client';

import { normalizeStaticValue, type Value } from 'platejs';
import { Plate, usePlateEditor } from 'platejs/react';
import * as React from 'react';
import { ensureDocumentHasBlock } from '@/components/editor/ensure-document-block';
import { RichTextEditorKit } from '@/components/editor/plugins/rich-text-editor-kit';
import { RichTextToolbar } from '@/components/editor/rich-text-toolbar';
import { Editor, EditorContainer } from '@/components/ui/editor';
import { cn } from '@/lib/utils';

const emptyDoc: Value = [{ type: 'p', children: [{ text: '' }] }];

export type RichTextEditorProps = {
    id?: string;
    className?: string;
    placeholder?: string;
    readOnly?: boolean;
    /** Show formatting toolbar above the editable area (inside the field border). */
    showFixedToolbar?: boolean;
    /** Initial document; normalized once on mount. */
    initialValue?: Value;
    onValueChange?: (value: Value) => void;
};

export function RichTextEditor({
    id,
    className,
    placeholder = 'Type / for commands…',
    readOnly,
    showFixedToolbar = false,
    initialValue,
    onValueChange,
}: RichTextEditorProps) {
    const initialDocRef = React.useRef<Value | null>(null);
    if (initialDocRef.current === null) {
        initialDocRef.current = normalizeStaticValue(initialValue ?? emptyDoc);
    }

    const editor = usePlateEditor({
        plugins: RichTextEditorKit,
        value: initialDocRef.current,
    });

    return (
        <Plate
            editor={editor}
            readOnly={readOnly}
            onValueChange={({ editor: ed, value: next }) => {
                if (
                    !readOnly &&
                    Array.isArray(next) &&
                    next.length === 0 &&
                    ensureDocumentHasBlock(ed)
                ) {
                    return;
                }
                onValueChange?.(next);
            }}
        >
            {showFixedToolbar ? (
                <div
                    className={cn(
                        'flex flex-col overflow-hidden rounded-md border border-input bg-background ring-offset-background',
                        'focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                        className,
                    )}
                    id={id}
                >
                    <RichTextToolbar />
                    <EditorContainer
                        variant="default"
                        className="min-h-[220px] max-h-[480px] flex-1 overflow-y-auto border-0 bg-transparent shadow-none ring-0 [&:focus-within]:ring-0"
                    >
                        <Editor variant="select" placeholder={placeholder} />
                    </EditorContainer>
                </div>
            ) : (
                <EditorContainer
                    variant="select"
                    id={id}
                    className={cn('min-h-[220px] max-h-[480px]', className)}
                >
                    <Editor variant="select" placeholder={placeholder} />
                </EditorContainer>
            )}
        </Plate>
    );
}
