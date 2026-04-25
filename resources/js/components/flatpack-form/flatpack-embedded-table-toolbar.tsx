import * as React from 'react';

export type FlatpackEmbeddedTableToolbarPayload = {
    fieldId: string;
    actionId: string;
};

const FlatpackEmbeddedTableToolbarContext = React.createContext<
    ((payload: FlatpackEmbeddedTableToolbarPayload) => void) | undefined
>(undefined);

/**
 * Optional host callback for custom embedded table toolbar buttons (`action` not handled
 * by the built-in draft drawer). Wrap the form (or part of the tree) and pass `value`.
 */
export function FlatpackEmbeddedTableToolbarProvider({
    value,
    children,
}: {
    value?: (payload: FlatpackEmbeddedTableToolbarPayload) => void;
    children: React.ReactNode;
}) {
    return (
        <FlatpackEmbeddedTableToolbarContext.Provider value={value}>
            {children}
        </FlatpackEmbeddedTableToolbarContext.Provider>
    );
}

export function useFlatpackEmbeddedTableToolbarAction():
    | ((payload: FlatpackEmbeddedTableToolbarPayload) => void)
    | undefined {
    return React.useContext(FlatpackEmbeddedTableToolbarContext);
}
