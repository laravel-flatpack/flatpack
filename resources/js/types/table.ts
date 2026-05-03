export type DataTableFooterProps = {
    id: string;
    rowCountLabel: string;
    pageSizeOptions: readonly number[];
    pageSize: number;
    pageIndex: number;
    pageCount: number;
    canPreviousPage: boolean;
    canNextPage: boolean;
    onPageSizeChange: (value: string) => void;
    onFirstPage: () => void;
    onPreviousPage: () => void;
    onNextPage: () => void;
    onLastPage: () => void;
};
