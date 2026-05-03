import { createContext, useContext } from 'react';
import type { FlatpackFormPendingConfirm } from '@/hooks/use-flatpack-form';
import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';

export type FlatpackFormInlineActionsContextValue = {
    formId: string;
    prepareFormSubmit: (
        row: FlatpackListHeaderAction & { action: string },
    ) => void;
    setPendingConfirm: (next: FlatpackFormPendingConfirm | null) => void;
    runAction: (config: FlatpackListHeaderAction & { action: string }) => void;
    formProcessing: boolean;
    formIsDirty: boolean;
    mode: 'create' | 'edit';
    fieldsLength: number;
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
