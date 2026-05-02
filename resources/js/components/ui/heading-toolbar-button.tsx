'use client';

import { Heading1, Heading2, Heading3 } from 'lucide-react';
import { KEYS } from 'platejs';
import { useEditorRef, useEditorSelector } from 'platejs/react';
import type * as React from 'react';
import { getBlockType, setBlockType } from '@/components/editor/transforms';
import { ToolbarButton } from './toolbar';

type HeadingLevel = typeof KEYS.h1 | typeof KEYS.h2 | typeof KEYS.h3;

function HeadingToolbarButton({
    nodeType,
    tooltip,
    children,
}: {
    nodeType: HeadingLevel;
    tooltip: string;
    children: React.ReactNode;
}) {
    const editor = useEditorRef();

    const pressed = useEditorSelector(
        (ed) => {
            const block = ed.api.block();
            if (!block) {
                return false;
            }
            return getBlockType(block[0]) === nodeType;
        },
        [nodeType],
    );

    return (
        <ToolbarButton
            pressed={pressed}
            tooltip={tooltip}
            onClick={() => {
                const block = editor.api.block();
                const current = block ? getBlockType(block[0]) : null;
                if (current === nodeType) {
                    setBlockType(editor, KEYS.p);
                } else {
                    setBlockType(editor, nodeType);
                }
                editor.tf.focus();
            }}
        >
            {children}
        </ToolbarButton>
    );
}

export function Heading1ToolbarButton() {
    return (
        <HeadingToolbarButton nodeType={KEYS.h1} tooltip="Heading 1">
            <Heading1 className="size-4" />
        </HeadingToolbarButton>
    );
}

export function Heading2ToolbarButton() {
    return (
        <HeadingToolbarButton nodeType={KEYS.h2} tooltip="Heading 2">
            <Heading2 className="size-4" />
        </HeadingToolbarButton>
    );
}

export function Heading3ToolbarButton() {
    return (
        <HeadingToolbarButton nodeType={KEYS.h3} tooltip="Heading 3">
            <Heading3 className="size-4" />
        </HeadingToolbarButton>
    );
}
