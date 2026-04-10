import { PathApi } from 'platejs';
import type { PlateEditor } from 'platejs/react';
import { Element } from 'slate';

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

/**
 * If the last top-level block is void (media, HR, etc.), append an empty paragraph
 * so the caret always has a writable line (e.g. first action is upload-only).
 */
export function ensureTrailingParagraphAfterVoid(editor: PlateEditor): boolean {
    if (editor.children.length === 0) {
        return ensureDocumentHasBlock(editor);
    }

    const last = editor.children.at(-1);
    if (!last || !Element.isElement(last) || !editor.api.isVoid(last)) {
        return false;
    }

    const lastPath = [editor.children.length - 1] as [number];
    const at = PathApi.next(lastPath);

    editor.tf.insertNodes(
        editor.api.create.block({
            type: 'p',
            children: [{ text: '' }],
        }),
        { at, select: true },
    );

    return true;
}
