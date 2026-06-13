'use client';

import { BlockMenuKit } from '@/components/editor/plugins/block-menu-kit';
import { DndKit } from '@/components/editor/plugins/dnd-kit';
import { RichTextEditorKit } from '@/components/editor/plugins/rich-text-editor-kit';

/** Same blocks as the rich editor, plus drag-and-drop and block menu (no fixed toolbar). */
export const BlockEditorKit = [
    ...RichTextEditorKit,
    ...DndKit,
    ...BlockMenuKit,
];
