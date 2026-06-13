import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import diff from 'highlight.js/lib/languages/diff';
import dockerfile from 'highlight.js/lib/languages/dockerfile';
import go from 'highlight.js/lib/languages/go';
import graphql from 'highlight.js/lib/languages/graphql';
import ini from 'highlight.js/lib/languages/ini';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import markdown from 'highlight.js/lib/languages/markdown';
import php from 'highlight.js/lib/languages/php';
import phpTemplate from 'highlight.js/lib/languages/php-template';
import plaintext from 'highlight.js/lib/languages/plaintext';
import python from 'highlight.js/lib/languages/python';
import scss from 'highlight.js/lib/languages/scss';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';
import { createLowlight, type LanguageFn } from 'lowlight';

/**
 * Subset of highlight.js grammars (vs `lowlight/all`) to keep the editor bundle small.
 * Unknown languages in existing documents fall back to plain text (Plate handles this).
 */
const grammars: Record<string, LanguageFn> = {
    bash,
    css,
    diff,
    dockerfile,
    go,
    graphql,
    ini,
    javascript,
    json,
    markdown,
    php,
    'php-template': phpTemplate,
    plaintext,
    python,
    scss,
    sql,
    typescript,
    xml,
    yaml,
};

export const codeBlockLowlight = createLowlight(grammars);

codeBlockLowlight.registerAlias({
    bash: ['sh', 'zsh'],
    dockerfile: ['docker'],
    javascript: ['js', 'jsx'],
    typescript: ['ts', 'tsx'],
    markdown: ['md', 'mkdn', 'mdwn'],
    plaintext: ['txt', 'text'],
    python: ['py'],
    yaml: ['yml'],
    scss: ['sass'],
});

/** Language picker options (canonical `value`s must match registered lowlight names or aliases). */
export const CODE_BLOCK_LANGUAGE_OPTIONS: { label: string; value: string }[] = [
    { label: 'Auto', value: 'auto' },
    { label: 'Plain Text', value: 'plaintext' },
    { label: 'Bash', value: 'bash' },
    { label: 'CSS', value: 'css' },
    { label: 'Diff', value: 'diff' },
    { label: 'Dockerfile', value: 'dockerfile' },
    { label: 'Go', value: 'go' },
    { label: 'GraphQL', value: 'graphql' },
    { label: 'HTML', value: 'html' },
    { label: 'INI', value: 'ini' },
    { label: 'JavaScript', value: 'javascript' },
    { label: 'JSON', value: 'json' },
    { label: 'Markdown', value: 'markdown' },
    { label: 'PHP', value: 'php' },
    { label: 'PHP (template)', value: 'php-template' },
    { label: 'Python', value: 'python' },
    { label: 'SCSS', value: 'scss' },
    { label: 'SQL', value: 'sql' },
    { label: 'TypeScript', value: 'typescript' },
    { label: 'XML', value: 'xml' },
    { label: 'YAML', value: 'yaml' },
];
