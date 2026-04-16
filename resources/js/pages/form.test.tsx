import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const hoisted = vi.hoisted(() => ({
    post: vi.fn(),
    patch: vi.fn(),
    route: vi.fn(
        (
            name: string,
            params?: {
                entity?: string;
                record?: string;
            },
        ) => {
            if (name === 'flatpack.entities.store') {
                return `/flatpack/${params?.entity ?? 'unknown'}`;
            }
            if (name === 'flatpack.entities.save') {
                return `/flatpack/${params?.entity ?? 'unknown'}/${params?.record ?? 'missing'}/save`;
            }

            return '/flatpack';
        },
    ),
    toastError: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    router: {
        post: hoisted.post,
        patch: hoisted.patch,
    },
}));

vi.mock('@/lib/route', () => ({
    route: hoisted.route,
}));

vi.mock('sonner', () => ({
    toast: {
        error: hoisted.toastError,
    },
}));

vi.mock('@/lib/form', () => ({
    loadField: vi.fn((type: string) =>
        React.lazy(() =>
            Promise.resolve({
                default: (props: {
                    id: string;
                    label?: string;
                    value?: unknown;
                    checked?: boolean;
                    onValueChange?: (value: unknown) => void;
                }) => (
                    <div>
                        <div data-testid={`field-${props.id}`}>
                            {props.label}:{' '}
                            {typeof props.value === 'string'
                                ? props.value
                                : props.checked === true
                                  ? 'true'
                                  : props.value == null
                                    ? ''
                                    : JSON.stringify(props.value)}
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                if (type === 'checkbox' || type === 'switch') {
                                    props.onValueChange?.(true);
                                    return;
                                }
                                props.onValueChange?.(`changed-${props.id}`);
                            }}
                        >
                            update-{props.id}
                        </button>
                    </div>
                ),
            }),
        ),
    ),
}));

import FlatpackFormPage from '@/pages/form';

describe('FlatpackFormPage', () => {
    beforeEach(() => {
        hoisted.post.mockReset();
        hoisted.patch.mockReset();
        hoisted.route.mockClear();
        hoisted.toastError.mockReset();
    });

    afterEach(() => {
        cleanup();
    });

    it('renders schema-driven fields with hydrated edit values', async () => {
        render(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record="42"
                mode="edit"
                schema={{
                    fields: {
                        title: {
                            type: 'text',
                            label: 'Title',
                            placeholder: 'Title',
                        },
                        status: {
                            type: 'select',
                            label: 'Status',
                            placeholder: 'Status',
                            options: [
                                { value: 'draft', label: 'Draft' },
                                { value: 'active', label: 'Active' },
                            ],
                        },
                    },
                }}
                values={{
                    title: 'Hydrated title',
                    status: 'active',
                }}
            />,
        );

        expect(
            await screen.findByRole('heading', { name: 'Posts' }),
        ).toBeInTheDocument();
        expect(await screen.findByTestId('field-title')).toHaveTextContent(
            'Title: Hydrated title',
        );
        expect(await screen.findByTestId('field-status')).toHaveTextContent(
            'Status: active',
        );
    });

    it('submits create mode through the store route', async () => {
        const user = userEvent.setup();

        render(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record={null}
                mode="create"
                schema={{
                    fields: {
                        title: {
                            type: 'text',
                            label: 'Title',
                            placeholder: 'Title',
                        },
                    },
                }}
                values={{}}
            />,
        );

        await user.click(
            await screen.findByRole('button', { name: 'update-title' }),
        );
        await user.click(screen.getByRole('button', { name: 'Create' }));

        await waitFor(() => {
            expect(hoisted.post).toHaveBeenCalledTimes(1);
        });

        expect(hoisted.post).toHaveBeenCalledWith(
            '/flatpack/posts',
            { values: { title: 'changed-title' } },
            expect.objectContaining({
                preserveScroll: true,
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        );
    });

    it('submits edit mode through the save route', async () => {
        const user = userEvent.setup();

        render(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record="7"
                mode="edit"
                schema={{
                    fields: {
                        title: {
                            type: 'text',
                            label: 'Title',
                            placeholder: 'Title',
                        },
                    },
                }}
                values={{
                    title: 'Existing title',
                }}
            />,
        );

        await user.click(
            await screen.findByRole('button', { name: 'update-title' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save' }));

        await waitFor(() => {
            expect(hoisted.patch).toHaveBeenCalledTimes(1);
        });

        expect(hoisted.patch).toHaveBeenCalledWith(
            '/flatpack/posts/7/save',
            { values: { title: 'changed-title' } },
            expect.objectContaining({
                preserveScroll: true,
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        );
    });

    it('shows toast and field errors when save fails', async () => {
        const user = userEvent.setup();
        hoisted.post.mockImplementationOnce(
            (
                _url: string,
                _data: unknown,
                options?: {
                    onError?: (errors: Record<string, unknown>) => void;
                },
            ) => {
                options?.onError?.({
                    flatpack: 'Save failed badly',
                    title: 'Title is required.',
                });
            },
        );

        render(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record={null}
                mode="create"
                schema={{
                    fields: {
                        title: {
                            type: 'text',
                            label: 'Title',
                            placeholder: 'Title',
                        },
                    },
                }}
                values={{}}
            />,
        );

        await user.click(await screen.findByRole('button', { name: 'Create' }));

        await waitFor(() => {
            expect(hoisted.toastError).toHaveBeenCalledWith(
                'Save failed badly',
            );
        });
        expect(screen.getByText('Title is required.')).toBeInTheDocument();
    });
});
