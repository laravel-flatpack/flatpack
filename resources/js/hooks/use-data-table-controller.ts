/**
 * Public entry for the table orchestration hook. All behavior, TanStack `useReactTable` wiring, and
 * invariants are implemented in {@link use-data-table-controller.impl.tsx} (JSX in that file only).
 * The UI shell is `DataTable` in `resources/js/components/table/data-table.tsx`.
 *
 * This `.ts` re-export exists because Vite resolves the bare `use-data-table-controller` import with
 * `.ts` before `.tsx`; a physical `.ts` file avoids 404s on the dev `*.ts` module URL.
 */
export {
    type DataTableController,
    useDataTableController,
} from './use-data-table-controller.impl';
