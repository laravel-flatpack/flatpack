import { performAction } from '.'

const shortcutAction = (key, { callback, action, options }) => {
    document.addEventListener(
        'DOMContentLoaded',
        window.addEventListener("keydown", (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === key.toLowerCase()) {
                event.preventDefault();
                return performAction(() => callback(), action, options);
            }
        })
    );
};

export default shortcutAction;
