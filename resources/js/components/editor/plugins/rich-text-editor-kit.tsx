'use client';

import { TogglePlugin } from '@platejs/toggle/react';
import { BasicNodesKit } from '@/components/editor/plugins/basic-nodes-kit';
import { CalloutKit } from '@/components/editor/plugins/callout-kit';
import { CodeBlockKit } from '@/components/editor/plugins/code-block-kit';
import { ListKit } from '@/components/editor/plugins/list-kit';
import { MediaKit } from '@/components/editor/plugins/media-kit';
import { SlashKit } from '@/components/editor/plugins/slash-kit';
import { TableKit } from '@/components/editor/plugins/table-kit';
import { ToggleElement } from '@/components/ui/toggle-node';

/** Notion-style blocks: headings, lists, todos, tables, toggles, code, callout, media, slash menu. */
export const RichTextEditorKit = [
    ...BasicNodesKit,
    ...CodeBlockKit,
    ...CalloutKit,
    ...TableKit,
    ...ListKit,
    TogglePlugin.withComponent(ToggleElement),
    ...MediaKit,
    ...SlashKit,
];
