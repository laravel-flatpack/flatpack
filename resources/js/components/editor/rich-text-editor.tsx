'use client';

import { normalizeStaticValue, type Value } from 'platejs';
import { Plate, usePlateEditor } from 'platejs/react';
import * as React from 'react';
import {
    ensureDocumentHasBlock,
    ensureTrailingParagraphAfterVoid,
} from '@/components/editor/ensure-document-block';
import { RichTextEditorKit } from '@/components/editor/plugins/rich-text-editor-kit';
import { RichTextToolbar } from '@/components/editor/rich-text-toolbar';
import { Editor, EditorContainer } from '@/components/ui/editor';
import { sanitizePlateValue } from '@/lib/plate-value-sanitize';
import { cn } from '@/lib/utils';

const emptyDoc: Value = [{ type: 'p', children: [{ text: '' }] }];

export type RichTextEditorProps = {
    labelId?: string;
    className?: string;
    placeholder?: string;
    readOnly?: boolean;
    toolbar?: boolean;
    initialValue?: Value;
    onValueChange?: (value: Value) => void;
};

export function RichTextEditor({
    labelId,
    className,
    placeholder = 'Type / for commands…',
    readOnly,
    toolbar = false,
    initialValue,
    onValueChange,
}: RichTextEditorProps) {
    const initialDocRef = React.useRef<Value | null>(null);
    if (initialDocRef.current === null) {
        initialDocRef.current = normalizeStaticValue(
            sanitizePlateValue(initialValue ?? emptyDoc),
        );
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
                if (readOnly) {
                    onValueChange?.(next);
                    return;
                }
                if (
                    Array.isArray(next) &&
                    next.length === 0 &&
                    ensureDocumentHasBlock(ed)
                ) {
                    return;
                }
                if (ensureTrailingParagraphAfterVoid(ed)) {
                    return;
                }
                onValueChange?.(next);
            }}
        >
            {toolbar ? (
                <div
                    className={cn(
                        'flex flex-col overflow-hidden rounded-md border border-input bg-background ring-offset-background',
                        'focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                        className,
                    )}
                >
                    <RichTextToolbar />
                    <EditorContainer
                        variant="default"
                        className="min-h-[220px] max-h-[480px] flex-1 overflow-y-auto border-0 bg-transparent shadow-none ring-0 [&:focus-within]:ring-0"
                    >
                        <Editor
                            variant="select"
                            placeholder={placeholder}
                            aria-labelledby={labelId}
                        />
                    </EditorContainer>
                </div>
            ) : (
                <EditorContainer
                    variant="select"
                    className={cn('min-h-[220px] max-h-[480px]', className)}
                >
                    <Editor
                        variant="select"
                        placeholder={placeholder}
                        aria-labelledby={labelId}
                    />
                </EditorContainer>
            )}
        </Plate>
    );
}
