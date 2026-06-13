import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { firstErrorMessage } from '@/lib/form-errors';

type InertiaMutationMethod = 'post' | 'patch';

type InertiaMutationOptions = {
    preserveState?: boolean;
    preserveScroll?: boolean;
    only?: string[];
    successMessage?: string;
    errorMessage: string;
};

async function inertiaMutation(
    method: InertiaMutationMethod,
    url: string,
    payload: Record<string, unknown>,
    options: InertiaMutationOptions,
): Promise<void> {
    return await new Promise<void>((resolve, reject) => {
        const visitOptions = {
            preserveState: options.preserveState ?? true,
            preserveScroll: options.preserveScroll ?? true,
            ...(options.only != null ? { only: options.only } : {}),
            onSuccess: () => {
                if (options.successMessage != null) {
                    toast.success(options.successMessage);
                }
                resolve();
            },
            onError: (errors: Record<string, unknown>) => {
                const message =
                    firstErrorMessage(errors) ?? options.errorMessage;
                reject(new Error(message));
            },
        };

        if (method === 'patch') {
            router.patch(url, payload as never, visitOptions);
            return;
        }

        router.post(url, payload as never, visitOptions);
    });
}

export async function inertiaPatchMutation(
    url: string,
    payload: Record<string, unknown>,
    options: InertiaMutationOptions,
): Promise<void> {
    await inertiaMutation('patch', url, payload, options);
}

export async function inertiaPostMutation(
    url: string,
    payload: Record<string, unknown>,
    options: InertiaMutationOptions,
): Promise<void> {
    await inertiaMutation('post', url, payload, options);
}
