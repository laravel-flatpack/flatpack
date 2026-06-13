'use client';

import {
    CodeBlockPlugin,
    CodeLinePlugin,
    CodeSyntaxPlugin,
} from '@platejs/code-block/react';
import {
    CodeBlockElement,
    CodeLineElement,
    CodeSyntaxLeaf,
} from '@/components/ui/code-block-node';
import { codeBlockLowlight } from '@/lib/code-block-lowlight';

const lowlight = codeBlockLowlight;

export const CodeBlockKit = [
    CodeBlockPlugin.configure({
        node: { component: CodeBlockElement },
        options: { lowlight },
        shortcuts: { toggle: { keys: 'mod+alt+8' } },
    }),
    CodeLinePlugin.withComponent(CodeLineElement),
    CodeSyntaxPlugin.withComponent(CodeSyntaxLeaf),
];
