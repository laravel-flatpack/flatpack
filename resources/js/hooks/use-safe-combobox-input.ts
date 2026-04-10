'use client';

/**
 * Fork of @platejs/combobox `useComboboxInput` with a guard before `removeNodes`.
 * `findPath(element)` can return a stale path after concurrent updates; Slate's
 * `removeNodes` then hits `matchPath` and throws ("undefined is not iterable").
 * @see https://github.com/udecode/plate/blob/main/packages/combobox/src/react/hooks/useComboboxInput.ts
 */
import type { CancelComboboxInputCause } from '@platejs/combobox';
import type {
    UseComboboxInputOptions,
    UseComboboxInputResult,
} from '@platejs/combobox/react';
import { Hotkeys, isHotkey } from 'platejs';
import { useEditorRef, useElement, useSelected } from 'platejs/react';
import * as React from 'react';
import type { Editor as SlateEditor } from 'slate';
import { Editor } from 'slate';

export function useSafeComboboxInput(
    options: UseComboboxInputOptions,
): UseComboboxInputResult {
    const {
        autoFocus = true,
        cancelInputOnArrowLeftRight = true,
        cancelInputOnBackspace = true,
        cancelInputOnBlur = true,
        cancelInputOnDeselect = true,
        cancelInputOnEscape = true,
        forwardUndoRedoToEditor = true,
        ref,
        onCancelInput,
        cursorState,
    } = options;

    const editor = useEditorRef();
    const element = useElement();
    const selected = useSelected();
    const cursorAtStart = cursorState?.atStart ?? false;
    const cursorAtEnd = cursorState?.atEnd ?? false;

    const removeInput = React.useCallback(
        (shouldFocusEditor = false) => {
            const path = editor.api.findPath(element);
            if (!path || path.length === 0) return;
            if (!Editor.hasPath(editor as unknown as SlateEditor, path)) return;
            editor.tf.removeNodes({ at: path });
            if (shouldFocusEditor) editor.tf.focus();
        },
        [editor, element],
    );

    const cancelInput = React.useCallback(
        (cause: CancelComboboxInputCause = 'manual', focusEditor = false) => {
            removeInput(focusEditor);
            onCancelInput?.(cause);
        },
        [removeInput, onCancelInput],
    );

    React.useEffect(() => {
        if (autoFocus) ref.current?.focus();
    }, [autoFocus, ref]);

    const previousSelected = React.useRef(selected);
    React.useEffect(() => {
        if (previousSelected.current && !selected && cancelInputOnDeselect) {
            cancelInput('deselect');
        }
        previousSelected.current = selected;
    }, [selected, cancelInputOnDeselect, cancelInput]);

    const onBlur = React.useCallback(() => {
        if (cancelInputOnBlur) cancelInput('blur');
    }, [cancelInputOnBlur, cancelInput]);

    const onKeyDown = React.useCallback(
        (event: React.KeyboardEvent<HTMLElement>) => {
            if (cancelInputOnEscape && isHotkey('escape', event)) {
                cancelInput('escape', true);
            }
            if (
                cancelInputOnBackspace &&
                cursorAtStart &&
                isHotkey('backspace', event)
            ) {
                cancelInput('backspace', true);
            }
            if (
                cancelInputOnArrowLeftRight &&
                cursorAtStart &&
                isHotkey('arrowleft', event)
            ) {
                cancelInput('arrowLeft', true);
            }
            if (
                cancelInputOnArrowLeftRight &&
                cursorAtEnd &&
                isHotkey('arrowright', event)
            ) {
                cancelInput('arrowRight', true);
            }
            const isUndo =
                Hotkeys.isUndo(event) && editor.history.undos.length > 0;
            const isRedo =
                Hotkeys.isRedo(event) && editor.history.redos.length > 0;
            if (forwardUndoRedoToEditor && (isUndo || isRedo)) {
                event.preventDefault();
                editor[isUndo ? 'undo' : 'redo']();
                editor.tf.focus();
            }
        },
        [
            cancelInput,
            cancelInputOnArrowLeftRight,
            cancelInputOnBackspace,
            cancelInputOnEscape,
            cursorAtEnd,
            cursorAtStart,
            editor,
            forwardUndoRedoToEditor,
        ],
    );

    return {
        cancelInput,
        removeInput,
        props: { onBlur, onKeyDown },
    };
}
