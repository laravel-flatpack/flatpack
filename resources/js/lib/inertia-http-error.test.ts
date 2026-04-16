import type { HttpResponse } from '@inertiajs/core';
import { describe, expect, it } from 'vitest';
import { inertiaHttpErrorMessage } from './inertia-http-error';

function response(status: number, data: string): HttpResponse {
    return { status, data, headers: {} };
}

describe('inertiaHttpErrorMessage', () => {
    it('uses JSON message when present', () => {
        expect(
            inertiaHttpErrorMessage(
                response(403, JSON.stringify({ message: 'Custom denied.' })),
            ),
        ).toBe('Custom denied.');
    });

    it('falls back by status when JSON has no message', () => {
        expect(inertiaHttpErrorMessage(response(403, '{}'))).toBe(
            "You don't have permission to perform this action.",
        );
    });

    it('falls back when body is not JSON', () => {
        expect(inertiaHttpErrorMessage(response(500, '<html></html>'))).toBe(
            'The server had a problem. Please try again later.',
        );
    });
});
