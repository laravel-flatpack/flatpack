import {
    createContext,
    type ReactNode,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';

type FlatpackKeyboardShortcutsDialogContextValue = {
    shortcutsDialogOpen: boolean;
    setShortcutsDialogOpen: (open: boolean) => void;
    toggleShortcutsDialog: () => void;
};

const FlatpackKeyboardShortcutsDialogContext =
    createContext<FlatpackKeyboardShortcutsDialogContextValue | null>(null);

export function FlatpackKeyboardShortcutsDialogProvider({
    children,
}: {
    children: ReactNode;
}) {
    const [shortcutsDialogOpen, setShortcutsDialogOpen] = useState(false);

    const toggleShortcutsDialog = useCallback(() => {
        setShortcutsDialogOpen((open) => !open);
    }, []);

    const value = useMemo(
        (): FlatpackKeyboardShortcutsDialogContextValue => ({
            shortcutsDialogOpen,
            setShortcutsDialogOpen,
            toggleShortcutsDialog,
        }),
        [shortcutsDialogOpen, toggleShortcutsDialog],
    );

    return (
        <FlatpackKeyboardShortcutsDialogContext.Provider value={value}>
            {children}
        </FlatpackKeyboardShortcutsDialogContext.Provider>
    );
}

export function useFlatpackKeyboardShortcutsDialog(): FlatpackKeyboardShortcutsDialogContextValue {
    const context = useContext(FlatpackKeyboardShortcutsDialogContext);
    if (!context) {
        throw new Error(
            'useFlatpackKeyboardShortcutsDialog must be used within FlatpackKeyboardShortcutsDialogProvider.',
        );
    }
    return context;
}
