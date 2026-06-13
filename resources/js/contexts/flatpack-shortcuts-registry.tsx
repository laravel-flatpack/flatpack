import {
    createContext,
    type ReactNode,
    useContext,
    useEffect,
    useMemo,
    useReducer,
} from 'react';
import type { ParsedFlatpackShortcut } from '@/lib/flatpack-action-shortcuts';

export type RegisteredFlatpackShortcut = {
    id: string;
    description: string;
    shortcut: ParsedFlatpackShortcut;
};

type RegistryState = Record<string, RegisteredFlatpackShortcut[]>;

type RegistryAction =
    | { type: 'set'; scope: string; entries: RegisteredFlatpackShortcut[] }
    | { type: 'clear'; scope: string };

function registryReducer(
    state: RegistryState,
    action: RegistryAction,
): RegistryState {
    switch (action.type) {
        case 'set':
            return { ...state, [action.scope]: action.entries };
        case 'clear': {
            const next = { ...state };
            delete next[action.scope];
            return next;
        }
        default:
            return state;
    }
}

const RegistryStateContext = createContext<RegistryState | null>(null);
const RegistryDispatchContext =
    createContext<React.Dispatch<RegistryAction> | null>(null);

export function FlatpackShortcutsProvider({
    children,
}: {
    children: ReactNode;
}) {
    const [state, dispatch] = useReducer(registryReducer, {});

    return (
        <RegistryDispatchContext.Provider value={dispatch}>
            <RegistryStateContext.Provider value={state}>
                {children}
            </RegistryStateContext.Provider>
        </RegistryDispatchContext.Provider>
    );
}

export function useFlatpackShortcutsDispatch(): React.Dispatch<RegistryAction> {
    const dispatch = useContext(RegistryDispatchContext);
    if (!dispatch) {
        throw new Error(
            'useFlatpackShortcutsDispatch must be used within FlatpackShortcutsProvider.',
        );
    }
    return dispatch;
}

export function useFlatpackRegisteredShortcuts(): RegisteredFlatpackShortcut[] {
    const state = useContext(RegistryStateContext);
    if (!state) {
        throw new Error(
            'useFlatpackRegisteredShortcuts must be used within FlatpackShortcutsProvider.',
        );
    }

    return useMemo(() => {
        const merged: RegisteredFlatpackShortcut[] = [];
        for (const rows of Object.values(state)) {
            merged.push(...rows);
        }

        const byId = new Map<string, RegisteredFlatpackShortcut>();
        for (const row of merged) {
            byId.set(row.id, row);
        }

        return [...byId.values()].sort((a, b) => {
            const aShell = a.id.startsWith('shell:');
            const bShell = b.id.startsWith('shell:');
            if (aShell !== bShell) {
                return aShell ? -1 : 1;
            }
            return a.description.localeCompare(b.description);
        });
    }, [state]);
}

/**
 * Registers shortcuts for a scope until unmount or until `entries` is replaced.
 * Pass a memoized `entries` array when it is derived from props to avoid churn.
 */
export function useRegisterFlatpackShortcuts(
    scope: string,
    entries: RegisteredFlatpackShortcut[],
): void {
    const dispatch = useFlatpackShortcutsDispatch();

    useEffect(() => {
        if (entries.length === 0) {
            dispatch({ type: 'clear', scope });
            return () => dispatch({ type: 'clear', scope });
        }

        dispatch({ type: 'set', scope, entries });
        return () => dispatch({ type: 'clear', scope });
    }, [scope, dispatch, entries]);
}
