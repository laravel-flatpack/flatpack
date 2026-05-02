'use client';

import { Bold, Code2, Italic, Strikethrough, Underline } from 'lucide-react';
import { KEYS } from 'platejs';
import { FixedToolbar } from '@/components/ui/fixed-toolbar';
import {
    Heading1ToolbarButton,
    Heading2ToolbarButton,
    Heading3ToolbarButton,
} from '@/components/ui/heading-toolbar-button';
import {
    IndentToolbarButton,
    OutdentToolbarButton,
} from '@/components/ui/indent-toolbar-button';
import {
    BulletedListToolbarButton,
    NumberedListToolbarButton,
    TodoListToolbarButton,
} from '@/components/ui/list-toolbar-button';
import { MarkToolbarButton } from '@/components/ui/mark-toolbar-button';
import { MediaToolbarButton } from '@/components/ui/media-toolbar-button';
import { TableToolbarButton } from '@/components/ui/table-toolbar-button';
import { ToggleToolbarButton } from '@/components/ui/toggle-toolbar-button';
import { ToolbarGroup } from '@/components/ui/toolbar';

/**
 * Must render inside `<Plate>` (same scope as the editor). Used by
 * {@link RichTextEditor} and {@link BlockEditor} when `toolbar` is true.
 */
export function RichTextToolbar() {
    return (
        <FixedToolbar className="justify-start gap-0 rounded-none border-x-0 border-t-0">
            <ToolbarGroup>
                <MarkToolbarButton nodeType={KEYS.bold} tooltip="Bold">
                    <Bold className="size-4" />
                </MarkToolbarButton>
                <MarkToolbarButton nodeType={KEYS.italic} tooltip="Italic">
                    <Italic className="size-4" />
                </MarkToolbarButton>
                <MarkToolbarButton
                    nodeType={KEYS.underline}
                    tooltip="Underline"
                >
                    <Underline className="size-4" />
                </MarkToolbarButton>
                <MarkToolbarButton
                    nodeType={KEYS.strikethrough}
                    tooltip="Strikethrough"
                >
                    <Strikethrough className="size-4" />
                </MarkToolbarButton>
                <MarkToolbarButton nodeType={KEYS.code} tooltip="Code">
                    <Code2 className="size-4" />
                </MarkToolbarButton>
            </ToolbarGroup>

            <ToolbarGroup>
                <Heading1ToolbarButton />
                <Heading2ToolbarButton />
                <Heading3ToolbarButton />
            </ToolbarGroup>

            <ToolbarGroup>
                <BulletedListToolbarButton />
                <NumberedListToolbarButton />
                <TodoListToolbarButton />
            </ToolbarGroup>

            <ToolbarGroup>
                <IndentToolbarButton />
                <OutdentToolbarButton />
            </ToolbarGroup>

            <ToolbarGroup>
                <TableToolbarButton />
                <ToggleToolbarButton />
            </ToolbarGroup>

            <ToolbarGroup>
                <MediaToolbarButton nodeType={KEYS.img} />
                <MediaToolbarButton nodeType={KEYS.video} />
            </ToolbarGroup>
        </FixedToolbar>
    );
}
