import type { PlateEditor } from 'platejs/react';

/**
 * Slate must keep at least one block. Select-all + delete can leave `[]`, which
 * breaks typing, focus, and the placeholder until the document is fixed.
 */
export function ensureDocumentHasBlock(editor: PlateEditor): boolean {
    if (editor.children.length > 0) return false;

    editor.tf.insertNodes(
        editor.api.create.block({
            type: 'p',
            children: [{ text: '' }],
        }),
        { at: [0], select: true },
    );

    return true;
}
