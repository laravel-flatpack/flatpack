import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import type { FlatpackSessionFlash } from '@/types/flatpack';

const FLASH_KEYS: (keyof FlatpackSessionFlash)[] = [
    'error',
    'warning',
    'success',
    'info',
    'message',
];

function fingerprint(flash: FlatpackSessionFlash | undefined): string {
    if (!flash || typeof flash !== 'object') {
        return '';
    }
    try {
        return JSON.stringify(flash);
    } catch {
        return '';
    }
}

/**
 * Shows Sonner toasts for Laravel session flash keys shared as {@code flatpack.flash}
 * (e.g. {@code redirect()->back()->with('success', '…')} from host action handlers).
 */
export function useFlatpackSessionFlashToast(
    flash: FlatpackSessionFlash | undefined,
): void {
    const lastFingerprint = useRef<string>('');

    useEffect(() => {
        const fp = fingerprint(flash);
        if (fp === lastFingerprint.current) {
            return;
        }

        if (flash && typeof flash === 'object') {
            for (const key of FLASH_KEYS) {
                const raw = flash[key];
                const text = typeof raw === 'string' ? raw.trim() : '';
                if (text === '') {
                    continue;
                }

                switch (key) {
                    case 'error':
                        toast.error(text);
                        break;
                    case 'warning':
                        toast.warning(text);
                        break;
                    case 'success':
                        toast.success(text);
                        break;
                    case 'info':
                        toast.info(text);
                        break;
                    case 'message':
                        toast(text);
                        break;
                    default:
                        break;
                }
            }
        }

        lastFingerprint.current = fp;
    }, [flash]);
}
