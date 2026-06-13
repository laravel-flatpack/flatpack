import dayjs, { type Dayjs } from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);
dayjs.extend(customParseFormat);

/** Configure dayjs locale for relative-time strings (e.g. after loading a locale pack). */
export function setRelativeTimeLocale(locale: string): void {
    const normalized = locale.trim();
    if (normalized === '') {
        return;
    }

    dayjs.locale(normalized);
}

function parseFlexibleDateString(raw: string): Dayjs | null {
    const normalized = raw.trim();
    if (normalized === '') {
        return null;
    }

    const strictSql = dayjs(normalized, 'YYYY-MM-DD HH:mm:ss', true);
    if (strictSql.isValid()) {
        return strictSql;
    }

    const isoGuess = dayjs(normalized.replace(' ', 'T'));
    if (isoGuess.isValid()) {
        return isoGuess;
    }

    const natural = dayjs(normalized);

    return natural.isValid() ? natural : null;
}

/**
 * Formats a date/time string for display: relative phrases ("4 minutes ago"),
 * "yesterday" when calendar-previous-day, or the raw string if parsing fails.
 */
export function formatAsRelativeTime(
    raw: string | null | undefined,
    now: Date = new Date(),
): string {
    if (typeof raw !== 'string') {
        return '';
    }

    const parsed = parseFlexibleDateString(raw);
    if (parsed === null) {
        return raw;
    }

    const nowDate = dayjs(now);
    if (parsed.isSame(nowDate.subtract(1, 'day'), 'day')) {
        return 'yesterday';
    }

    return parsed.from(nowDate);
}
