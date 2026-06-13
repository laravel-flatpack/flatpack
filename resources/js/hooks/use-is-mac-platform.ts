import { useMemo } from 'react';

export function useIsMacPlatform(): boolean {
    return useMemo(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        return window.navigator.platform.toLowerCase().includes('mac');
    }, []);
}
