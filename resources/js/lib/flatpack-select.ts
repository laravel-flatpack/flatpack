/**
 * Radix `Select.Item` cannot use `value=""`. Use this on a “clear” row; map it to root `value=""`
 * and `onValueChange(null)` (root must use `""` for empty, not `undefined`, or the first clear
 * click may not apply).
 */
export const CLEAR_SELECT_ITEM_VALUE = '\u2060';
