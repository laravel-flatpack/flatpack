import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import { cn } from '@/lib/utils';

hljs.registerLanguage('json', json);

const hljsPanelLayoutClass =
    'schema-json-hljs max-h-[70vh] overflow-auto rounded-xl border border-border bg-muted/30 p-4 font-mono text-xs leading-relaxed [tab-size:2] dark:bg-muted/50';

const hljsLightTokenColorsClass =
    'not-dark:text-foreground not-dark:**:[.hljs-punctuation]:text-muted-foreground not-dark:**:[.hljs-attr,.hljs-attribute,.hljs-literal,.hljs-meta,.hljs-number,.hljs-operator,.hljs-selector-attr,.hljs-selector-class,.hljs-selector-id,.hljs-variable]:text-[#005cc5] not-dark:**:[.hljs-keyword,.hljs-doctag,.hljs-template-tag,.hljs-template-variable,.hljs-type,.hljs-variable.language\\\\_]:text-[#d73a49] not-dark:**:[.hljs-name,.hljs-quote]:text-[#22863a] not-dark:**:[.hljs-regexp,.hljs-string,.hljs-meta_.hljs-string]:text-[#032f62] not-dark:**:[.hljs-comment,.hljs-code,.hljs-formula]:text-[#6a737d]';

const hljsDarkTokenColorsClass =
    'dark:text-[#9aa5ce] dark:**:[.hljs-meta,.hljs-comment]:text-[#565f89] dark:**:[.hljs-variable,.hljs-template-variable,.hljs-number,.hljs-literal,.hljs-type,.hljs-params,.hljs-link]:text-[#ff9e64] dark:**:[.hljs-tag,.hljs-doctag,.hljs-selector-id,.hljs-selector-class,.hljs-regexp,.hljs-template-tag,.hljs-selector-pseudo,.hljs-selector-attr,.hljs-variable.language\\\\_,.hljs-deletion]:text-[#f7768e] dark:**:[.hljs-built_in,.hljs-attribute]:text-[#e0af68] dark:**:[.hljs-selector-tag]:text-[#73daca] dark:**:[.hljs-title.function\\\\_,.hljs-title,.hljs-title.class\\\\_,.hljs-title.class\\\\_.inherited\\\\_\\\\_,.hljs-subst,.hljs-property]:text-[#7dcfff] dark:**:[.hljs-quote,.hljs-string,.hljs-symbol,.hljs-bullet,.hljs-addition]:text-[#9ece6a] dark:**:[.hljs-code,.hljs-formula,.hljs-section]:text-[#7aa2f7] dark:**:[.hljs-name,.hljs-keyword,.hljs-operator,.hljs-char.escape\\\\_,.hljs-attr]:text-[#bb9af7] dark:**:[.hljs-punctuation]:text-[#c0caf5]';

export function SchemaRawJsonPanel({ raw }: { raw: unknown }) {
    const code = JSON.stringify(raw, null, 2);
    const html = hljs.highlight(code, {
        language: 'json',
        ignoreIllegals: true,
    }).value;

    return (
        <pre
            className={cn(
                hljsPanelLayoutClass,
                hljsLightTokenColorsClass,
                hljsDarkTokenColorsClass,
            )}
        >
            {/* hljs escapes markup; source is JSON.stringify of server payload */}
            {/* biome-ignore lint/security/noDangerouslySetInnerHtml: trusted hljs HTML from normalized schema */}
            <code className="hljs" dangerouslySetInnerHTML={{ __html: html }} />
        </pre>
    );
}
