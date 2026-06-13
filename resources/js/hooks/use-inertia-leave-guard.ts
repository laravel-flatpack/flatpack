import type { PendingVisit } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

/** Call immediately before an intentional Inertia visit (e.g. form submit) so the leave guard does not block it while the form is still dirty. */
export function bypassNextInertiaLeaveGuard(): void {
    bypassNextLeaveGuardFlag = true;
}

let bypassNextLeaveGuardFlag = false;

function stripPendingMeta(visit: PendingVisit) {
    const url = visit.url;
    const options = { ...visit } as Record<string, unknown>;
    delete options.url;
    delete options.completed;
    delete options.cancelled;
    delete options.interrupted;
    return { url, options: options as Parameters<typeof router.visit>[1] };
}

/**
 * Blocks Inertia navigations while `isDirty` until the user confirms leaving.
 * Browser tab close / refresh uses `beforeunload` (native prompt; custom copy is ignored by most browsers).
 */
export function useInertiaLeaveGuard(isDirty: boolean) {
    const [pendingVisit, setPendingVisit] = useState<PendingVisit | null>(null);
    const isDirtyRef = useRef(isDirty);
    const bypassNextRef = useRef(false);

    useEffect(() => {
        isDirtyRef.current = isDirty;
    }, [isDirty]);

    useEffect(() => {
        return router.on('before', (event) => {
            const visit = event.detail.visit;

            if (visit.prefetch) {
                return;
            }

            if (bypassNextLeaveGuardFlag) {
                bypassNextLeaveGuardFlag = false;
                return;
            }

            if (bypassNextRef.current) {
                bypassNextRef.current = false;
                return;
            }

            if (!isDirtyRef.current) {
                return;
            }

            event.preventDefault();
            setPendingVisit(visit);
            return false;
        });
    }, []);

    useEffect(() => {
        if (!isDirty) {
            return;
        }
        const onBeforeUnload = (e: BeforeUnloadEvent) => {
            e.preventDefault();
            e.returnValue = '';
        };
        window.addEventListener('beforeunload', onBeforeUnload);
        return () => window.removeEventListener('beforeunload', onBeforeUnload);
    }, [isDirty]);

    const confirmLeave = useCallback(() => {
        setPendingVisit((visit) => {
            if (visit) {
                bypassNextRef.current = true;
                const { url, options } = stripPendingMeta(visit);
                router.visit(url, options);
            }
            return null;
        });
    }, []);

    const cancelLeave = useCallback(() => {
        setPendingVisit(null);
    }, []);

    return {
        leaveGuardOpen: pendingVisit !== null,
        confirmLeave,
        cancelLeave,
    };
}
