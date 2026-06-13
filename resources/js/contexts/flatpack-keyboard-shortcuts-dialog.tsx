import {
    createContext,
    type ReactNode,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';

type KeyboardShortcutsDialogContextValue = {
    shortcutsDialogOpen: boolean;
    setShortcutsDialogOpen: (open: boolean) => void;
    toggleShortcutsDialog: () => void;
};

const KeyboardShortcutsDialogContext =
    createContext<KeyboardShortcutsDialogContextValue | null>(null);

export function KeyboardShortcutsDialogProvider({
    children,
}: {
    children: ReactNode;
}) {
    const [shortcutsDialogOpen, setShortcutsDialogOpen] = useState(false);

    const toggleShortcutsDialog = useCallback(() => {
        setShortcutsDialogOpen((open) => !open);
    }, []);

    const value = useMemo(
        (): KeyboardShortcutsDialogContextValue => ({
            shortcutsDialogOpen,
            setShortcutsDialogOpen,
            toggleShortcutsDialog,
        }),
        [shortcutsDialogOpen, toggleShortcutsDialog],
    );

    return (
        <KeyboardShortcutsDialogContext.Provider value={value}>
            {children}
        </KeyboardShortcutsDialogContext.Provider>
    );
}

export function useKeyboardShortcutsDialog(): KeyboardShortcutsDialogContextValue {
    const context = useContext(KeyboardShortcutsDialogContext);
    if (!context) {
        throw new Error(
            'useKeyboardShortcutsDialog must be used within KeyboardShortcutsDialogProvider.',
        );
    }
    return context;
}
