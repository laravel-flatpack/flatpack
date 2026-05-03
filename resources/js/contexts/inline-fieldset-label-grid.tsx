import { createContext, type ReactNode, useContext } from 'react';

/** When true, horizontal fields participate in a shared 2-column label/control grid (fieldset). */
const InlineFieldsetLabelGridContext = createContext(false);

export function InlineFieldsetLabelGridProvider({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <InlineFieldsetLabelGridContext.Provider value={true}>
            {children}
        </InlineFieldsetLabelGridContext.Provider>
    );
}

export function useInlineFieldsetLabelGrid(): boolean {
    return useContext(InlineFieldsetLabelGridContext);
}
