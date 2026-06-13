import type { HttpResponse } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

/**
 * Human-readable message for failed Inertia XHR responses (non-validation).
 */
export function inertiaHttpErrorMessage(response: HttpResponse): string {
    const raw = response.data;
    if (typeof raw === 'string' && raw.trim() !== '') {
        try {
            const parsed = JSON.parse(raw) as { message?: unknown };
            if (
                typeof parsed.message === 'string' &&
                parsed.message.trim() !== ''
            ) {
                return parsed.message;
            }
        } catch {
            // Non-JSON body (e.g. HTML error page)
        }
    }

    switch (response.status) {
        case 401:
            return 'Please sign in to continue.';
        case 403:
            return "You don't have permission to perform this action.";
        case 404:
            return 'The requested resource was not found.';
        case 419:
            return 'Your session has expired. Please refresh the page.';
        case 429:
            return 'Too many requests. Please wait and try again.';
        case 500:
        case 502:
        case 503:
            return 'The server had a problem. Please try again later.';
        default:
            return 'Something went wrong. Please try again.';
    }
}

/**
 * Show Sonner toasts for HTTP errors instead of the default full-page error UI.
 * Does not run for 422 — validation errors must still reach {@code onError} on the visit.
 */
export function registerInertiaHttpExceptionToast(): VoidFunction {
    return router.on('httpException', (event) => {
        const { response } = event.detail;
        if (response.status === 422) {
            return;
        }
        toast.error(inertiaHttpErrorMessage(response));
        return false;
    });
}
