import { afterEach, describe, expect, it, vi } from 'vitest';

const { routerPost } = vi.hoisted(() => ({
    routerPost: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    router: {
        post: routerPost,
        patch: vi.fn(),
    },
}));

import { inertiaPostMutation } from '@/lib/inertia-mutation';

describe('inertiaPostMutation', () => {
    afterEach(() => {
        routerPost.mockReset();
    });

    it('rejects with server flatpack message when backend returns ValidationException-style bag', async () => {
        routerPost.mockImplementationOnce(
            (
                _url: string,
                _data: unknown,
                options?: {
                    onError?: (errors: Record<string, unknown>) => void;
                },
            ) => {
                options?.onError?.({
                    flatpack: 'This change could not be completed.',
                });
            },
        );

        await expect(
            inertiaPostMutation(
                '/flatpack/posts/bulk',
                { action: 'delete', selection: ['1'] },
                {
                    errorMessage: 'Bulk action failed',
                },
            ),
        ).rejects.toThrow('This change could not be completed.');

        expect(routerPost).toHaveBeenCalledTimes(1);
    });

    it('falls back to errorMessage when error bag has no usable strings', async () => {
        routerPost.mockImplementationOnce(
            (
                _url: string,
                _data: unknown,
                options?: {
                    onError?: (errors: Record<string, unknown>) => void;
                },
            ) => {
                options?.onError?.({});
            },
        );

        await expect(
            inertiaPostMutation(
                '/x',
                {},
                { errorMessage: 'Bulk action failed' },
            ),
        ).rejects.toThrow('Bulk action failed');
    });
});
