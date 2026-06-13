import { createContext, useContext } from 'react';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export type FlatpackFormInlineActionsContextValue = {
    formId: string;
    prepareFormSubmit: (
        row: FlatpackListHeaderAction & { action: string },
    ) => void;
    /** YAML confirm path; may show dirty-warning first for non-submit actions. */
    requestConfirmForAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    /** Immediate row action; may show dirty-warning first for non-submit actions. */
    requestRunAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    formProcessing: boolean;
    formIsDirty: boolean;
    mode: 'create' | 'edit';
    fieldsLength: number;
    formValues: Record<string, unknown>;
};

const FlatpackFormInlineActionsContext =
    createContext<FlatpackFormInlineActionsContextValue | null>(null);

export function FlatpackFormInlineActionsProvider({
    value,
    children,
}: {
    value: FlatpackFormInlineActionsContextValue;
    children: React.ReactNode;
}) {
    return (
        <FlatpackFormInlineActionsContext.Provider value={value}>
            {children}
        </FlatpackFormInlineActionsContext.Provider>
    );
}

export function useFlatpackFormInlineActions(): FlatpackFormInlineActionsContextValue | null {
    return useContext(FlatpackFormInlineActionsContext);
}
