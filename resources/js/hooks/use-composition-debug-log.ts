import { useEffect } from 'react';

/**
 * Logs optional composition-sanitization messages when present (typically only when Laravel APP_DEBUG is true).
 */
export function useCompositionDebugLog(messages: string[] | undefined): void {
    useEffect(() => {
        if (messages === undefined || messages.length === 0) {
            return;
        }
        for (const msg of messages) {
            console.warn(`[FLATPACK]${msg}`);
        }
    }, [messages]);
}
