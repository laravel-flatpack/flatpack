'use client';

import { PlaceholderPlugin } from '@platejs/media/react';
import { FilmIcon, ImageIcon } from 'lucide-react';
import { KEYS } from 'platejs';
import { useEditorRef } from 'platejs/react';
import type * as React from 'react';
import { useFilePicker } from 'use-file-picker';
import { ToolbarButton } from './toolbar';

const MEDIA_CONFIG: Record<
    typeof KEYS.img | typeof KEYS.video,
    {
        accept: string[];
        icon: React.ReactNode;
        tooltip: string;
    }
> = {
    [KEYS.img]: {
        accept: ['image/*'],
        icon: <ImageIcon className="size-4" />,
        tooltip: 'Image',
    },
    [KEYS.video]: {
        accept: ['video/*'],
        icon: <FilmIcon className="size-4" />,
        tooltip: 'Video',
    },
};

export function MediaToolbarButton({
    nodeType,
    ...props
}: React.ComponentProps<typeof ToolbarButton> & {
    nodeType: typeof KEYS.img | typeof KEYS.video;
}) {
    const currentConfig = MEDIA_CONFIG[nodeType];

    const editor = useEditorRef();

    const { openFilePicker } = useFilePicker({
        accept: currentConfig.accept,
        multiple: true,
        onFilesSelected: ({ plainFiles: updatedFiles }) => {
            editor.getTransforms(PlaceholderPlugin).insert.media(updatedFiles);
        },
    });

    return (
        <ToolbarButton
            tooltip={currentConfig.tooltip}
            onClick={() => {
                openFilePicker();
            }}
            {...props}
        >
            {currentConfig.icon}
        </ToolbarButton>
    );
}
