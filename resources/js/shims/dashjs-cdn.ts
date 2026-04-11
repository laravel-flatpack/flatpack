/**
 * Loads dash.js from CDN instead of bundling (~960kB). Keep the version in sync
 * with the resolved `dashjs` version in package-lock.json (transitive via
 * dash-video-element / react-player).
 *
 * UMD build exposes `window.dashjs` (see dashjs dist/legacy/umd/dash.all.min.js).
 */
const DASHJS_VERSION = '5.1.1';
const DASH_CDN = `https://cdn.jsdelivr.net/npm/dashjs@${DASHJS_VERSION}/dist/legacy/umd/dash.all.min.js`;

type DashGlobal = {
    MediaPlayer: () => { create: () => unknown };
};

function loadDashScript(): Promise<void> {
    const w = window as Window & { dashjs?: DashGlobal };
    if (w.dashjs?.MediaPlayer) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const id = 'flatpack-dashjs-cdn';
        const existing = document.getElementById(
            id,
        ) as HTMLScriptElement | null;
        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener(
                'error',
                () => reject(new Error('dash.js script failed')),
                { once: true },
            );
            return;
        }

        const el = document.createElement('script');
        el.id = id;
        el.src = DASH_CDN;
        el.async = true;
        el.crossOrigin = 'anonymous';
        el.onload = () => resolve();
        el.onerror = () => reject(new Error('dash.js script failed'));
        document.head.appendChild(el);
    });
}

await loadDashScript();

const dash = (window as Window & { dashjs?: DashGlobal }).dashjs;
if (!dash?.MediaPlayer) {
    throw new Error('dash.js CDN did not expose window.dashjs.MediaPlayer');
}
export const MediaPlayer = dash.MediaPlayer;
