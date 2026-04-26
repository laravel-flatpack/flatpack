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
            if (name === 'flatpack.entities.form.submit') {
                return `/flatpack/${params?.entity ?? 'unknown'}/submit`;
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
    usePage: () => ({
        props: {
            flatpack: {
                showActionShortcutHints: false,
            },
        },
    }),
    useForm: <TData extends Record<string, unknown>>(initialData: TData) => {
        const defaultsRef = React.useRef<TData>(
            JSON.parse(JSON.stringify(initialData)) as TData,
        );
        const [data, setDataState] = React.useState<TData>(
            JSON.parse(JSON.stringify(initialData)) as TData,
        );
        const [errors, setErrors] = React.useState<Record<string, string>>({});
        const [, setDirtyTick] = React.useState(0);
        const transformerRef = React.useRef<
            ((data: TData) => Record<string, unknown>) | null
        >(null);

        const setDefaults = React.useCallback(
            (
                fieldOrData: keyof TData | Record<string, unknown>,
                maybeValue?: unknown,
            ) => {
                if (typeof fieldOrData === 'string') {
                    defaultsRef.current = {
                        ...(defaultsRef.current as object),
                        [fieldOrData]: maybeValue,
                    } as TData;
                } else {
                    defaultsRef.current = {
                        ...(defaultsRef.current as object),
                        ...(fieldOrData as object),
                    } as TData;
                }
                setDirtyTick((n) => n + 1);
            },
            [],
        );

        const reset = React.useCallback(() => {
            setDataState(
                JSON.parse(JSON.stringify(defaultsRef.current)) as TData,
            );
            setDirtyTick((n) => n + 1);
        }, []);

        const isDirty =
            JSON.stringify(data) !== JSON.stringify(defaultsRef.current);

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
            isDirty,
            setData,
            clearErrors,
            setError,
            setDefaults,
            reset,
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
                                if (type === 'date-picker') {
                                    props.onValueChange?.(
                                        new Date(2026, 3, 25),
                                    );
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

import { FlatpackShortcutsProvider } from '@/contexts/flatpack-shortcuts-registry';
import FlatpackFormPage from '@/pages/form';

function renderFlatpackFormPage(page: React.ReactElement) {
    return render(page, {
        wrapper: ({ children }) => (
            <FlatpackShortcutsProvider>{children}</FlatpackShortcutsProvider>
        ),
    });
}

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
        renderFlatpackFormPage(
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
            await screen.findByRole('heading', { name: 'Edit Posts' }),
        ).toBeInTheDocument();
        expect(await screen.findByTestId('field-title')).toHaveTextContent(
            'Title: Hydrated title',
        );
        expect(await screen.findByTestId('field-status')).toHaveTextContent(
            'Status: active',
        );
    });

    it('submits create mode through the form submit route', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
            '/flatpack/posts/submit',
            {
                values: { title: 'changed-title' },
                action: 'save',
                form_action_id: 'save',
            },
            expect.objectContaining({
                preserveScroll: true,
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        );
    });

    it('submits date-picker values as YYYY-MM-DD', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
                        published_at: {
                            type: 'date-picker',
                            label: 'Published At',
                            placeholder: 'Pick a date',
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
        await user.click(
            screen.getByRole('button', { name: 'update-published_at' }),
        );
        await user.click(screen.getByRole('button', { name: 'Save' }));

        await waitFor(() => {
            expect(hoisted.post).toHaveBeenCalledTimes(1);
        });

        expect(hoisted.post).toHaveBeenCalledWith(
            '/flatpack/posts/submit',
            {
                values: {
                    title: 'changed-title',
                    published_at: '2026-04-25',
                },
                action: 'save',
                form_action_id: 'save',
            },
            expect.objectContaining({
                preserveScroll: true,
                onSuccess: expect.any(Function),
                onError: expect.any(Function),
            }),
        );
    });

    it('submits edit mode through the same submit route with record in the body', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
            expect(hoisted.post).toHaveBeenCalledTimes(1);
        });

        expect(hoisted.patch).not.toHaveBeenCalled();

        expect(hoisted.post).toHaveBeenCalledWith(
            '/flatpack/posts/submit',
            {
                values: { title: 'changed-title' },
                action: 'save',
                form_action_id: 'save',
                record: '7',
            },
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

        renderFlatpackFormPage(
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

    it('fills preset destination from source until destination is edited', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
                        url: {
                            type: 'text',
                            label: 'URL',
                            placeholder: 'URL',
                            preset: { field: 'title', type: 'url' },
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

        expect(await screen.findByTestId('field-url')).toHaveTextContent(
            /^URL:\s*$/,
        );

        await user.click(screen.getByRole('button', { name: 'update-title' }));

        await waitFor(() => {
            expect(screen.getByTestId('field-url')).toHaveTextContent(
                'URL: /changed-title',
            );
        });

        await user.click(screen.getByRole('button', { name: 'update-url' }));

        await user.click(screen.getByRole('button', { name: 'update-title' }));

        expect(screen.getByTestId('field-url')).toHaveTextContent(
            'URL: changed-url',
        );
    });

    it('blocks submit on client when required YAML field is empty', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
        renderFlatpackFormPage(
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

    it('submits non-save yaml actions through the form submit route in edit mode', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
                '/flatpack/posts/submit',
                {
                    values: { title: 'Existing title' },
                    action: 'delete',
                    form_action_id: 'delete',
                    record: '7',
                },
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

        renderFlatpackFormPage(
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

    it('enables enabled_if form.dirty save after date-picker value changes', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
            <FlatpackFormPage
                entity="posts"
                name="Posts"
                record={null}
                mode="create"
                schema={{
                    fields: {
                        published_at: {
                            type: 'date-picker',
                            label: 'Published At',
                            placeholder: 'Select a date',
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
                        enabled_if: {
                            all: [{ 'form.dirty': true }],
                        },
                    },
                ]}
            />,
        );

        const saveButton = await screen.findByRole('button', { name: 'Save' });
        expect(saveButton).toBeDisabled();

        await user.click(
            await screen.findByRole('button', {
                name: 'update-published_at',
            }),
        );

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Save' })).toBeEnabled();
        });
    });

    it('shows confirm dialog before named action when confirm is true', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
                '/flatpack/posts/submit',
                {
                    values: { title: 'Existing title' },
                    action: 'delete',
                    form_action_id: 'delete',
                    record: '7',
                },
                expect.objectContaining({
                    preserveScroll: true,
                    onSuccess: expect.any(Function),
                    onError: expect.any(Function),
                }),
            );
        });
    });

    it('hides form actions while visible_if condition is unmet', async () => {
        renderFlatpackFormPage(
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
                        id: 'publish',
                        label: 'Publish',
                        action: 'save',
                        visible_if: {
                            all: [{ 'form.mode_in': ['edit'] }],
                        },
                    },
                ]}
            />,
        );

        await screen.findByRole('heading', { name: 'Create Posts' });
        expect(
            screen.queryByRole('button', { name: 'Publish' }),
        ).not.toBeInTheDocument();
    });

    it('shows confirm dialog before save when confirm is true', async () => {
        const user = userEvent.setup();

        renderFlatpackFormPage(
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
