'use client';

import { normalizeStaticValue, type Value } from 'platejs';
import { Plate, usePlateEditor } from 'platejs/react';
import * as React from 'react';
import { ensureDocumentHasBlock } from '@/components/editor/ensure-document-block';
import { BlockEditorKit } from '@/components/editor/plugins/block-editor-kit';
import { Editor, EditorContainer } from '@/components/ui/editor';
import { cn } from '@/lib/utils';

const emptyDoc: Value = [{ type: 'p', children: [{ text: '' }] }];

export type BlockEditorProps = {
    id?: string;
    className?: string;
    placeholder?: string;
    readOnly?: boolean;
    initialValue?: Value;
    onValueChange?: (value: Value) => void;
};

/**
 * Notion-like block editor: drag handle and “+” in the gutter, no top toolbar.
 * Uses the same document model as {@link RichTextEditor}.
 */
export function BlockEditor({
    id,
    className,
    placeholder = 'Type / for commands, or use + and drag ⋮⋮…',
    readOnly,
    initialValue,
    onValueChange,
}: BlockEditorProps) {
    const initialDocRef = React.useRef<Value | null>(null);
    if (initialDocRef.current === null) {
        initialDocRef.current = normalizeStaticValue(initialValue ?? emptyDoc);
    }

    const editor = usePlateEditor({
        plugins: BlockEditorKit,
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
            <div
                className={cn(
                    'overflow-visible rounded-md border border-input bg-background ring-offset-background',
                    'focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                )}
            >
                <EditorContainer
                    variant="default"
                    id={id}
                    className={cn(
                        'min-h-[280px] max-h-[560px] overflow-y-auto border-0 bg-transparent px-0 py-2 shadow-none ring-0 [&:focus-within]:ring-0',
                        className,
                    )}
                >
                    <Editor
                        variant="select"
                        placeholder={placeholder}
                        className="overflow-x-visible py-2 pl-14 pr-3"
                    />
                </EditorContainer>
            </div>
        </Plate>
    );
}
