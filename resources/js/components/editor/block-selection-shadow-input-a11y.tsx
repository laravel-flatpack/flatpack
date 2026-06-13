'use client';

import { BlockSelectionPlugin } from '@platejs/selection/react';
import { useEditorRef } from 'platejs/react';
import * as React from 'react';

const MAX_FRAMES = 90;

/**
 * @platejs/selection portals a hidden input for clipboard / keyboard during block
 * selection. It ships without id/name, which triggers accessibility audits.
 */
export function BlockSelectionShadowInputA11y() {
    const editor = useEditorRef();
    const uid = React.useId();
    const stableName = `plate-block-selection-shadow-${uid.replaceAll(':', '')}`;

    React.useEffect(() => {
        let frame = 0;
        let raf = 0;

        const run = () => {
            frame += 1;
            const shadowRef = editor.getOption(
                BlockSelectionPlugin,
                'shadowInputRef',
            ) as React.RefObject<HTMLInputElement | null> | undefined;
            const el = shadowRef?.current;
            if (el && !el.dataset.flatpackShadowA11y) {
                el.id = stableName;
                el.name = stableName;
                el.setAttribute('aria-hidden', 'true');
                el.tabIndex = -1;
                el.dataset.flatpackShadowA11y = '1';
                return;
            }
            if (frame < MAX_FRAMES) {
                raf = requestAnimationFrame(run);
            }
        };

        raf = requestAnimationFrame(run);
        return () => cancelAnimationFrame(raf);
    }, [editor, stableName]);

    return null;
}
