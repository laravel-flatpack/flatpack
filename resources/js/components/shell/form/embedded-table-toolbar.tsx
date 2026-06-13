import * as React from 'react';

export type EmbeddedTableToolbarPayload = {
    fieldId: string;
    actionId: string;
};

const EmbeddedTableToolbarContext = React.createContext<
    ((payload: EmbeddedTableToolbarPayload) => void) | undefined
>(undefined);

/**
 * Optional host callback for custom embedded table toolbar buttons (`action` not handled
 * by the built-in draft drawer). Wrap the form (or part of the tree) and pass `value`.
 */
export function EmbeddedTableToolbarProvider({
    value,
    children,
}: {
    value?: (payload: EmbeddedTableToolbarPayload) => void;
    children: React.ReactNode;
}) {
    return (
        <EmbeddedTableToolbarContext.Provider value={value}>
            {children}
        </EmbeddedTableToolbarContext.Provider>
    );
}

export function useEmbeddedTableToolbarAction():
    | ((payload: EmbeddedTableToolbarPayload) => void)
    | undefined {
    return React.useContext(EmbeddedTableToolbarContext);
}
