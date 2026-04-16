import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const hoisted = vi.hoisted(() => ({
    post: vi.fn(),
    patch: vi.fn(),
    toastSuccess: vi.fn(),
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
            if (name === 'flatpack.entities.row-action') {
                return `/flatpack/${params?.entity ?? 'unknown'}/${params?.record ?? 'missing'}/action`;
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
    useForm: <TData extends Record<string, unknown>>(initialData: TData) => {
        const [data, setDataState] = React.useState<TData>(initialData);
        const [errors, setErrors] = React.useState<Record<string, string>>({});
        const transformerRef = React.useRef<
            ((data: TData) => Record<string, unknown>) | null
        >(null);

        const clearErrors = React.useCallback((field?: string) => {
            if (field === undefined) {
                setErrors({});
                return;
            }
            setErrors((current) => {
                const next = { ...current };
                delete next[field];
                return next;
            });
        }, []);

        const setData = React.useCallback(
            (fieldOrData: keyof TData | TData, maybeValue?: unknown) => {
                if (typeof fieldOrData === 'string') {
                    setDataState((current) => ({
                        ...current,
                        [fieldOrData]: maybeValue,
                    }));
                    return;
                }

                setDataState(fieldOrData as TData);
            },
            [],
        );

        const setError = React.useCallback((next: Record<string, string>) => {
            setErrors((current) => ({ ...current, ...next }));
        }, []);

        const submit = React.useCallback(
            (method: 'post' | 'patch') =>
                (
                    url: string,
                    options?: {
                        onSuccess?: () => void;
                        onError?: (errors: Record<string, unknown>) => void;
                    },
                ) => {
                    const payload = transformerRef.current
                        ? transformerRef.current(data)
                        : data;
                    const callbackOptions = {
                        ...options,
                        onSuccess: () => {
                            clearErrors();
                            options?.onSuccess?.();
                        },
                        onError: (nextErrors: Record<string, unknown>) => {
                            setErrors(nextErrors as Record<string, string>);
                            options?.onError?.(nextErrors);
                        },
                    };

                    if (method === 'post') {
                        hoisted.post(url, payload, callbackOptions);
                        return;
                    }

                    hoisted.patch(url, payload, callbackOptions);
                },
            [clearErrors, data],
        );

        return {
            data,
            errors,
            processing: false,
            setData,
            clearErrors,
            setError,
            transform: React.useCallback(
                (next: (data: TData) => Record<string, unknown>) => {
                    transformerRef.current = next;
                },
                [],
            ),
            post: submit('post'),
            patch: submit('patch'),
        };
    },
}));

vi.mock('@/lib/route', () => ({
    route: hoisted.route,
}));

vi.mock('sonner', () => ({
    toast: {
        error: hoisted.toastError,
        success: hoisted.toastSuccess,
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
        hoisted.toastSuccess.mockReset();
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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                    },
                ]}
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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                    },
                ]}
            />,
        );

        await user.click(
            await screen.findByRole('button', { name: 'update-title' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save' }));

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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                    },
                ]}
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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                    },
                ]}
            />,
        );

        await user.click(await screen.findByRole('button', { name: 'Save' }));

        await waitFor(() => {
            expect(hoisted.toastError).toHaveBeenCalledWith(
                'Save failed badly',
            );
        });
        expect(screen.getByText('Title is required.')).toBeInTheDocument();
    });

    it('blocks submit on client when required YAML field is empty', async () => {
        const user = userEvent.setup();

        render(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record={null}
                mode="create"
                schema={{
                    fields: {
                        slug: {
                            type: 'text',
                            label: 'Slug',
                            placeholder: 'Slug',
                            required: true,
                        },
                    },
                }}
                values={{ slug: '' }}
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                    },
                ]}
            />,
        );

        await user.click(await screen.findByRole('button', { name: 'Save' }));

        expect(hoisted.post).not.toHaveBeenCalled();
        expect(hoisted.patch).not.toHaveBeenCalled();
        expect(hoisted.toastError).toHaveBeenCalledWith('Slug is required.');
        expect(screen.getByText('Slug is required.')).toBeInTheDocument();
    });

    it('does not render a default submit action when yaml actions are absent', () => {
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
                form_actions={[]}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Save' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Create' }),
        ).not.toBeInTheDocument();
    });

    it('renders non-save yaml actions and posts row actions for edit mode', async () => {
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
                values={{ title: 'Existing title' }}
                form_actions={[
                    {
                        id: 'delete',
                        label: 'Delete',
                        action: 'delete',
                        variant: 'destructive',
                    },
                ]}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Delete' }));

        await waitFor(() => {
            expect(hoisted.post).toHaveBeenCalledWith(
                '/flatpack/posts/7/action',
                { action: 'delete' },
                expect.objectContaining({
                    preserveScroll: true,
                    onSuccess: expect.any(Function),
                    onError: expect.any(Function),
                }),
            );
        });
    });

    it('shows success toast after save when success_message is set', async () => {
        const user = userEvent.setup();
        hoisted.post.mockImplementationOnce(
            (
                _url: string,
                _data: unknown,
                options?: { onSuccess?: () => void },
            ) => {
                options?.onSuccess?.();
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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                        success_message: 'Post saved successfully',
                    },
                ]}
            />,
        );

        await user.click(
            await screen.findByRole('button', { name: 'update-title' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save' }));

        await waitFor(() => {
            expect(hoisted.toastSuccess).toHaveBeenCalledWith(
                'Post saved successfully',
            );
        });
    });

    it('shows confirm dialog before named action when confirm is true', async () => {
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
                values={{ title: 'Existing title' }}
                form_actions={[
                    {
                        id: 'delete',
                        label: 'Delete',
                        action: 'delete',
                        variant: 'destructive',
                        confirm: true,
                    },
                ]}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Delete' }));

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(hoisted.post).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() => {
            expect(hoisted.post).toHaveBeenCalledWith(
                '/flatpack/posts/7/action',
                { action: 'delete' },
                expect.objectContaining({
                    preserveScroll: true,
                    onSuccess: expect.any(Function),
                    onError: expect.any(Function),
                }),
            );
        });
    });

    it('shows confirm dialog before save when confirm is true', async () => {
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
                form_actions={[
                    {
                        id: 'save',
                        label: 'Save',
                        action: 'save',
                        variant: 'default',
                        confirm: true,
                    },
                ]}
            />,
        );

        await user.click(
            await screen.findByRole('button', { name: 'update-title' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save' }));

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(hoisted.post).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() => {
            expect(hoisted.post).toHaveBeenCalledTimes(1);
        });
    });
});
