const login = '/flatpack/login';

export const store = {
    url: (): string => login,
    form: (): { action: string; method: 'post' } => ({
        action: login,
        method: 'post',
    }),
};
