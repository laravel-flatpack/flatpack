import type { FileUploadStoredFile } from '@/types/form-fields';

/**
 * Browser: turns relative / root-relative paths into an absolute URL for `<img src>`.
 * No `window` (SSR/tests): returns {@param src} unchanged (except empty stays empty).
 */
export function absoluteImageSrcForDisplay(src: string): string {
    if (src === '') {
        return '';
    }
    if (/^https?:\/\//i.test(src)) {
        return src;
    }
    if (typeof window === 'undefined' || window.location?.origin === '') {
        return src;
    }
    try {
        if (src.startsWith('//')) {
            return new URL(src, window.location.origin).href;
        }
        const path = src.startsWith('/') ? src : `/${src.replace(/^\/+/, '')}`;
        return new URL(path, window.location.origin).href;
    } catch {
        return src;
    }
}

/** Public URL or path for `<img src>` from stored upload metadata. */
export function storedFileImageSrc(
    file: FileUploadStoredFile | null | undefined,
): string {
    if (file == null || typeof file !== 'object') {
        return '';
    }
    const url = file.url?.trim();
    if (url) {
        return url;
    }
    const path = file.path?.trim();
    if (!path) {
        return '';
    }
    if (
        path.startsWith('http://') ||
        path.startsWith('https://') ||
        path.startsWith('/')
    ) {
        return path;
    }

    return `/${path.replace(/^\/+/, '')}`;
}

/**
 * Resolves a list/table cell value to an image URL: plain string paths/URLs, or upload metadata objects.
 */
export function listCellImageSrc(value: unknown): string {
    let src = '';
    if (value == null) {
        return '';
    }
    if (typeof value === 'string') {
        const s = value.trim();
        if (s === '') {
            return '';
        }
        if (
            s.startsWith('http://') ||
            s.startsWith('https://') ||
            s.startsWith('/')
        ) {
            src = s;
        } else {
            src = `/${s.replace(/^\/+/, '')}`;
        }
        return absoluteImageSrcForDisplay(src);
    }
    if (typeof value === 'object' && !Array.isArray(value)) {
        src = storedFileImageSrc(value as FileUploadStoredFile);
        return absoluteImageSrcForDisplay(src);
    }
    return '';
}

export function messageFrom422Body(body: unknown): string | null {
    if (typeof body !== 'object' || body === null) {
        return null;
    }
    const record = body as Record<string, unknown>;
    const errors = record.errors;
    if (
        typeof errors === 'object' &&
        errors !== null &&
        !Array.isArray(errors)
    ) {
        const files = (errors as Record<string, unknown>).files;
        if (Array.isArray(files) && files.length > 0) {
            const lines = files.filter(
                (s): s is string => typeof s === 'string',
            );
            if (lines.length > 0) {
                return lines.join(' ');
            }
        }
    }
    const msg = record.message;
    if (typeof msg === 'string' && msg.trim() !== '') {
        return msg;
    }

    return null;
}

export function normalizeFiles(value: unknown): FileUploadStoredFile[] {
    if (Array.isArray(value)) {
        return value.filter(
            (item): item is FileUploadStoredFile =>
                typeof item === 'object' && item !== null,
        );
    }
    if (typeof value === 'object' && value !== null) {
        return [value as FileUploadStoredFile];
    }
    return [];
}

export function coercePositiveInt(value: unknown): number | undefined {
    if (typeof value === 'number' && Number.isFinite(value) && value > 0) {
        return Math.min(Math.floor(value), Number.MAX_SAFE_INTEGER);
    }
    if (typeof value === 'string' && value.trim() !== '') {
        const n = Number(value.trim());
        if (Number.isFinite(n) && n > 0) {
            return Math.min(Math.floor(n), Number.MAX_SAFE_INTEGER);
        }
    }
    return undefined;
}

/** e.g. "5MB", "1.5MB", "500KB" for the dropzone hint line. */
export function formatSizeHintKb(maxSizeKb: number): string {
    const mb = maxSizeKb / 1024;
    if (mb >= 1) {
        return Number.isInteger(mb) ? `${mb}MB` : `${mb.toFixed(1)}MB`;
    }
    return `${maxSizeKb}KB`;
}

/**
 * Flatpack private-file URLs use {@code ?path=relative/path/on/disk}; the pathname ends in
 * {@code /serve}, so we must not use the last URL segment as the display name.
 */
function basenameFromUrlQueryPath(rawUrl: string): string | null {
    const q = rawUrl.indexOf('?');
    if (q === -1) {
        return null;
    }
    try {
        const params = new URLSearchParams(rawUrl.slice(q + 1));
        const p = params.get('path');
        if (!p || p.trim() === '') {
            return null;
        }
        const segments = p.split('/').filter(Boolean);
        const base = segments.pop();
        return base && base !== '' ? base : null;
    } catch {
        return null;
    }
}

/**
 * Label shown in the UI: basename of stored {@code path} (matches DB / disk) first, then URL path,
 * then original upload {@code name}. Avoids showing the client filename when the persisted key differs.
 */
export function storedFileDisplayName(file: FileUploadStoredFile): string {
    const fromPath = file.path?.trim();
    if (fromPath && fromPath !== '') {
        const base = fromPath.split('/').pop();
        if (base && base !== '') {
            return base;
        }
    }

    const fromUrl = file.url?.trim();
    if (fromUrl && fromUrl !== '') {
        const fromQuery = basenameFromUrlQueryPath(fromUrl);
        if (fromQuery) {
            return fromQuery;
        }

        if (fromUrl.startsWith('http://') || fromUrl.startsWith('https://')) {
            try {
                const pathname = new URL(fromUrl).pathname;
                const seg = pathname.split('/').filter(Boolean).pop();
                if (seg && seg !== '' && seg !== 'serve') {
                    return seg;
                }
            } catch {
                /* ignore malformed URL */
            }
        } else {
            const seg = fromUrl.split(/[?#]/)[0]?.split('/').pop();
            if (seg && seg !== '') {
                return seg;
            }
        }
    }

    const original = file.name?.trim();
    if (original && original !== '') {
        return original;
    }

    return 'File';
}

/** Human-readable size for file-card preview; returns null when unknown. */
function formatStoredFileSize(bytes: unknown): string | null {
    if (typeof bytes !== 'number' || !Number.isFinite(bytes) || bytes < 0) {
        return null;
    }
    if (bytes < 1024) {
        return `${Math.round(bytes)} B`;
    }
    const kb = bytes / 1024;
    if (kb < 1024) {
        return `${kb < 10 ? kb.toFixed(1) : Math.round(kb)} KB`;
    }
    const mb = kb / 1024;
    return `${mb < 10 ? mb.toFixed(1) : Math.round(mb)} MB`;
}

export function fileModeDetailLine(file: FileUploadStoredFile): string | null {
    const size = formatStoredFileSize(file.size);
    const mime = file.mime_type?.trim();
    const parts = [size, mime && mime !== '' ? mime : null].filter(
        (p): p is string => p !== null && p !== '',
    );
    if (parts.length === 0) {
        return null;
    }
    return parts.join(' · ');
}

export function formatDropzoneLimits(
    maxFiles: number | undefined,
    maxSizeKb: number | undefined,
): string | null {
    const parts: string[] = [];
    if (maxFiles !== undefined && maxFiles > 0) {
        parts.push(`Max ${maxFiles} file${maxFiles === 1 ? '' : 's'}`);
    }
    if (maxSizeKb !== undefined && maxSizeKb > 0) {
        parts.push(`Up to ${formatSizeHintKb(maxSizeKb)}`);
    }
    if (parts.length === 0) {
        return null;
    }
    return parts.join(' ∙ ');
}
