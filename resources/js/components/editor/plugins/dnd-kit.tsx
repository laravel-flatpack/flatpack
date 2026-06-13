'use client';

import { DndPlugin } from '@platejs/dnd';
import { PlaceholderPlugin } from '@platejs/media/react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { BlockDraggable } from '@/components/ui/block-draggable';

export const DndKit = [
    DndPlugin.configure({
        options: {
            // Edge scroll zones are fixed full-width layers (z-index 10k, opacity 0).
            // They sit above the editor and receive native `drop` without being
            // react-dnd targets, so dropTargetIds stays empty and blocks never move.
            enableScroller: false,
            onDropFiles: ({ dragItem, editor, target }) => {
                editor
                    .getTransforms(PlaceholderPlugin)
                    .insert.media(dragItem.files, {
                        at: target,
                        nextBlock: false,
                    });
            },
        },
        render: {
            aboveNodes: BlockDraggable,
            aboveSlate: ({ children }) => (
                <DndProvider backend={HTML5Backend}>{children}</DndProvider>
            ),
        },
    }),
];
