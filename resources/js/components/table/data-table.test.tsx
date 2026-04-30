import {
    cleanup,
    fireEvent,
    render,
    screen,
    waitFor,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { DataTable } from '@/components/table/data-table';

describe('DataTable toolbar create flow', () => {
    afterEach(() => {
        cleanup();
    });

    beforeAll(() => {
        if (!('setPointerCapture' in Element.prototype)) {
            Object.defineProperty(Element.prototype, 'setPointerCapture', {
                value: () => {},
            });
        }
        if (!('releasePointerCapture' in Element.prototype)) {
            Object.defineProperty(Element.prototype, 'releasePointerCapture', {
                value: () => {},
            });
        }
    });

    it('does not open detail drawer on row click when openDetailDrawerOnRowClick is false', async () => {
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-no-row-drawer"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[{ id: 1, name: 'Line 1' }]}
                toolbarActions={[
                    { id: 'create', label: 'Create', action: 'create' },
                ]}
                rowDetailDrawer
                openDetailDrawerOnRowClick={false}
            />,
        );

        await user.click(screen.getByText('Line 1'));

        expect(
            screen.queryByRole('button', { name: 'Save changes' }),
        ).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Create' }));

        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();
    });

    it('shows columns visibility dropdown by default', () => {
        render(
            <DataTable
                id="columns-visible-default"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[{ id: 1, name: 'Line 1' }]}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Columns' }),
        ).toBeInTheDocument();
    });

    it('hides columns visibility dropdown when disabled', () => {
        render(
            <DataTable
                id="columns-visible-hidden"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[{ id: 1, name: 'Line 1' }]}
                showColumnsVisibility={false}
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Columns' }),
        ).not.toBeInTheDocument();
    });

    it('opens drawer and appends row only on drawer submit', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines"
                columns={[
                    { id: 'name', label: 'Name', type: 'text', editable: true },
                ]}
                data={[]}
                toolbarActions={[
                    { id: 'create', label: 'Create', action: 'create' },
                ]}
                rowDetailDrawer
                onValueChange={onValueChange}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Create' }));

        expect(onValueChange).not.toHaveBeenCalled();
        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();

        fireEvent.change(screen.getByRole('textbox', { name: 'Name' }), {
            target: { value: 'New row' },
        });
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(onValueChange).toHaveBeenCalledWith([
            expect.objectContaining({ name: 'New row' }),
        ]);
    });

    it('renders attach slot when toolbar action is attach and renderRowDrawerAttachBody is set', async () => {
        const user = userEvent.setup();
        const onValueChange = vi.fn();

        render(
            <DataTable
                id="btm-attach"
                columns={[
                    { id: 'id', label: 'Id', type: 'text', editable: true },
                ]}
                data={[]}
                toolbarActions={[
                    { id: 'a1', label: 'Attach', action: 'attach' },
                ]}
                rowDetailDrawer
                onValueChange={onValueChange}
                renderRowDrawerAttachBody={({ setDraft }) => (
                    <button
                        type="button"
                        onClick={() => setDraft({ id: '99' })}
                    >
                        Pick id 99
                    </button>
                )}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Attach' }));
        expect(onValueChange).not.toHaveBeenCalled();
        await user.click(screen.getByRole('button', { name: 'Pick id 99' }));
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(onValueChange).toHaveBeenCalledWith([
            expect.objectContaining({ id: '99' }),
        ]);
    });

    it('removes row for relation remove action fallback', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-relation-actions"
                columns={[
                    { id: 'name', label: 'Name', type: 'text' },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [{ label: 'Remove', action: 'remove' }],
                    },
                ]}
                data={[{ id: 1, name: 'Line 1' }]}
                onValueChange={onValueChange}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Remove' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenCalledWith([]);
        });
    });

    it('applies bulk remove fallback and notifies parent value', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-relation-bulk"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[
                    { id: '1', name: 'Line 1' },
                    { id: '2', name: 'Line 2' },
                ]}
                bulkActions={[
                    { id: 'remove', label: 'Remove', action: 'remove' },
                ]}
                onValueChange={onValueChange}
            />,
        );

        const [headerCheckbox, firstRowCheckbox] =
            screen.getAllByRole('checkbox');
        expect(headerCheckbox).toBeInTheDocument();
        await user.click(firstRowCheckbox);
        await user.click(screen.getByRole('button', { name: 'Bulk Actions' }));
        await user.click(screen.getByRole('menuitem', { name: 'Remove' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenCalledWith([
                expect.objectContaining({ id: '2', name: 'Line 2' }),
            ]);
        });
    });

    it('disables bulk menu items while enabled_if is unmet', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-relation-bulk-inactive"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[
                    { id: '1', name: 'Line 1' },
                    { id: '2', name: 'Line 2' },
                ]}
                bulkActions={[
                    {
                        id: 'remove',
                        label: 'Remove',
                        action: 'remove',
                        enabled_if: {
                            all: [{ 'list.selection.min': 2 }],
                        },
                    },
                ]}
                onValueChange={onValueChange}
            />,
        );

        const [, firstRowCheckbox] = screen.getAllByRole('checkbox');
        await user.click(firstRowCheckbox);
        await user.click(screen.getByRole('button', { name: 'Bulk Actions' }));

        expect(
            screen.getByRole('menuitem', { name: 'Remove' }),
        ).toHaveAttribute('data-disabled', '');
    });

    it('hides bulk menu items while visible_if is unmet', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-relation-bulk-invisible"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[
                    { id: '1', name: 'Line 1' },
                    { id: '2', name: 'Line 2' },
                ]}
                bulkActions={[
                    {
                        id: 'remove',
                        label: 'Remove',
                        action: 'remove',
                        visible_if: {
                            any: [{ 'list.search_present': true }],
                        },
                    },
                ]}
                onValueChange={onValueChange}
            />,
        );

        const [, firstRowCheckbox] = screen.getAllByRole('checkbox');
        await user.click(firstRowCheckbox);
        await user.click(screen.getByRole('button', { name: 'Bulk Actions' }));

        expect(
            screen.queryByRole('menuitem', { name: 'Remove' }),
        ).not.toBeInTheDocument();
    });

    it('supports create, edit, save, then remove in one flow', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-combined-flow"
                columns={[
                    { id: 'name', label: 'Name', type: 'text', editable: true },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [{ label: 'Remove', action: 'remove' }],
                    },
                ]}
                data={[]}
                toolbarActions={[
                    { id: 'create', label: 'Create', action: 'create' },
                ]}
                rowDetailDrawer
                onValueChange={onValueChange}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Create' }));
        fireEvent.change(screen.getByRole('textbox', { name: 'Name' }), {
            target: { value: 'Integrated row' },
        });
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenNthCalledWith(1, [
                expect.objectContaining({ name: 'Integrated row' }),
            ]);
        });

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Remove' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenNthCalledWith(2, []);
        });
    });

    it('marks rows with validation errors and keeps them interactive', async () => {
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-validation"
                columns={[{ id: 'name', label: 'Name', type: 'text' }]}
                data={[{ id: '1', name: 'Line 1' }]}
                rowDetailDrawer
                rowValidationMessagesById={{
                    '1': ['User id is required.'],
                }}
            />,
        );

        const row = screen.getByText('Line 1').closest('tr');
        expect(row).not.toBeNull();
        expect(row).toHaveClass('bg-destructive/5');
        expect(screen.getByLabelText('Validation errors')).toBeInTheDocument();

        await user.click(screen.getByText('Line 1'));
        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();
    });

    it('supports confirm remove flow with cancel then confirm', async () => {
        const onValueChange = vi.fn();
        const user = userEvent.setup();

        render(
            <DataTable
                id="lines-confirm-remove-flow"
                columns={[
                    { id: 'name', label: 'Name', type: 'text', editable: true },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [
                            {
                                label: 'Remove',
                                action: 'remove',
                                confirm: true,
                            },
                        ],
                    },
                ]}
                data={[]}
                toolbarActions={[
                    { id: 'create', label: 'Create', action: 'create' },
                ]}
                rowDetailDrawer
                onValueChange={onValueChange}
            />,
        );

        await user.click(screen.getByRole('button', { name: 'Create' }));
        fireEvent.change(screen.getByRole('textbox', { name: 'Name' }), {
            target: { value: 'Confirm row' },
        });
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenNthCalledWith(1, [
                expect.objectContaining({ name: 'Confirm row' }),
            ]);
        });

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Remove' }));

        expect(
            screen.getByRole('button', { name: 'Cancel' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onValueChange).toHaveBeenCalledTimes(1);
        expect(
            screen.getByRole('button', { name: 'Open row actions' }),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Remove' }));
        await user.click(screen.getByRole('button', { name: 'Continue' }));

        await waitFor(() => {
            expect(onValueChange).toHaveBeenNthCalledWith(2, []);
        });
    });

    it('opens row drawer from row action edit when rowDetailDrawer is set', async () => {
        const user = userEvent.setup();

        render(
            <DataTable
                id="edit-from-menu"
                columns={[
                    { id: 'name', label: 'Name', type: 'text', editable: true },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [
                            { label: 'Edit', action: 'edit', icon: 'edit' },
                        ],
                    },
                ]}
                data={[{ id: 1, name: 'Row 1' }]}
                rowDetailDrawer
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Edit' }));

        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();
    });

    it('prefers row drawer for edit action even when onRowAction exists', async () => {
        const user = userEvent.setup();
        const onRowAction = vi.fn();

        render(
            <DataTable
                id="edit-with-on-row-action"
                columns={[
                    { id: 'name', label: 'Name', type: 'text', editable: true },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [
                            { label: 'Edit', action: 'edit', icon: 'edit' },
                        ],
                    },
                ]}
                data={[{ id: 1, name: 'Row 1' }]}
                rowDetailDrawer
                onRowAction={onRowAction}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Open row actions' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Edit' }));

        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();
        expect(onRowAction).not.toHaveBeenCalled();
    });

    it('hides row action trigger when actions require id and row id is missing', () => {
        render(
            <DataTable
                id="actions-require-id"
                columns={[
                    { id: 'name', label: 'Name', type: 'text' },
                    {
                        id: 'actions',
                        label: 'Actions',
                        type: 'actions',
                        actions: [{ label: 'Remove', action: 'remove' }],
                    },
                ]}
                data={[{ name: 'Row without id' }]}
                requireRowIdForActions
            />,
        );

        expect(
            screen.queryByRole('button', { name: 'Open row actions' }),
        ).not.toBeInTheDocument();
    });
});
