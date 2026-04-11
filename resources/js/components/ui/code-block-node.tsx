'use client';

import { formatCodeBlock, isLangSupported } from '@platejs/code-block';
import { BracesIcon, Check, CheckIcon, CopyIcon } from 'lucide-react';
import { NodeApi, type TCodeBlockElement, type TCodeSyntaxLeaf } from 'platejs';
import {
    PlateElement,
    type PlateElementProps,
    PlateLeaf,
    type PlateLeafProps,
    useEditorRef,
    useElement,
    useReadOnly,
} from 'platejs/react';
import * as React from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { CODE_BLOCK_LANGUAGE_OPTIONS } from '@/lib/code-block-lowlight';
import { cn } from '@/lib/utils';

export function CodeBlockElement(props: PlateElementProps<TCodeBlockElement>) {
    const { editor, element } = props;

    return (
        <PlateElement
            className="py-1 **:[.hljs-addition]:bg-[#f0fff4] **:[.hljs-addition]:text-[#22863a] dark:**:[.hljs-addition]:bg-[#3c5743] dark:**:[.hljs-addition]:text-[#ceead5] **:[.hljs-attr,.hljs-attribute,.hljs-literal,.hljs-meta,.hljs-number,.hljs-operator,.hljs-selector-attr,.hljs-selector-class,.hljs-selector-id,.hljs-variable]:text-[#005cc5] dark:**:[.hljs-attr,.hljs-attribute,.hljs-literal,.hljs-meta,.hljs-number,.hljs-operator,.hljs-selector-attr,.hljs-selector-class,.hljs-selector-id,.hljs-variable]:text-[#6596cf] **:[.hljs-built\\\\_in,.hljs-symbol]:text-[#e36209] dark:**:[.hljs-built\\\\_in,.hljs-symbol]:text-[#c3854e] **:[.hljs-bullet]:text-[#735c0f] **:[.hljs-comment,.hljs-code,.hljs-formula]:text-[#6a737d] dark:**:[.hljs-comment,.hljs-code,.hljs-formula]:text-[#6a737d] **:[.hljs-deletion]:bg-[#ffeef0] **:[.hljs-deletion]:text-[#b31d28] dark:**:[.hljs-deletion]:bg-[#473235] dark:**:[.hljs-deletion]:text-[#e7c7cb] **:[.hljs-emphasis]:italic **:[.hljs-keyword,.hljs-doctag,.hljs-template-tag,.hljs-template-variable,.hljs-type,.hljs-variable.language\\\\_]:text-[#d73a49] dark:**:[.hljs-keyword,.hljs-doctag,.hljs-template-tag,.hljs-template-variable,.hljs-type,.hljs-variable.language\\\\_]:text-[#ee6960] **:[.hljs-name,.hljs-quote,.hljs-selector-tag,.hljs-selector-pseudo]:text-[#22863a] dark:**:[.hljs-name,.hljs-quote,.hljs-selector-tag,.hljs-selector-pseudo]:text-[#36a84f] **:[.hljs-regexp,.hljs-string,.hljs-meta_.hljs-string]:text-[#032f62] dark:**:[.hljs-regexp,.hljs-string,.hljs-meta_.hljs-string]:text-[#3593ff] **:[.hljs-section]:font-bold **:[.hljs-section]:text-[#005cc5] dark:**:[.hljs-section]:text-[#61a5f2] **:[.hljs-strong]:font-bold **:[.hljs-title,.hljs-title.class\\\\_,.hljs-title.class\\\\_.inherited\\\\_\\\\_,.hljs-title.function\\\\_]:text-[#6f42c1] dark:**:[.hljs-title,.hljs-title.class\\\\_,.hljs-title.class\\\\_.inherited\\\\_\\\\_,.hljs-title.function\\\\_]:text-[#a77bfa]"
            {...props}
        >
            <div className="relative rounded-md bg-muted/50">
                <pre className="overflow-x-auto p-8 pr-4 font-mono text-sm leading-[normal] [tab-size:2] print:break-inside-avoid">
                    <code>{props.children}</code>
                </pre>

                <div
                    className="absolute top-1 right-1 z-10 flex select-none gap-0.5"
                    contentEditable={false}
                >
                    {isLangSupported(element.lang) && (
                        <Button
                            size="icon"
                            variant="ghost"
                            className="size-6 text-xs"
                            onClick={() => formatCodeBlock(editor, { element })}
                            title="Format code"
                        >
                            <BracesIcon className="!size-3.5 text-muted-foreground" />
                        </Button>
                    )}

                    <CodeBlockCombobox />

                    <CopyButton
                        size="icon"
                        variant="ghost"
                        className="size-6 gap-1 text-muted-foreground text-xs"
                        value={() => NodeApi.string(element)}
                    />
                </div>
            </div>
        </PlateElement>
    );
}

function CodeBlockCombobox() {
    const [open, setOpen] = React.useState(false);
    const readOnly = useReadOnly();
    const editor = useEditorRef();
    const element = useElement<TCodeBlockElement>();
    const value = element.lang || 'plaintext';
    const [searchValue, setSearchValue] = React.useState('');

    const items = React.useMemo(
        () =>
            CODE_BLOCK_LANGUAGE_OPTIONS.filter(
                (language) =>
                    !searchValue ||
                    language.label
                        .toLowerCase()
                        .includes(searchValue.toLowerCase()),
            ),
        [searchValue],
    );

    if (readOnly) return null;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    size="sm"
                    variant="ghost"
                    className="h-6 select-none justify-between gap-1 px-2 text-muted-foreground text-xs"
                    aria-expanded={open}
                    role="combobox"
                >
                    {CODE_BLOCK_LANGUAGE_OPTIONS.find(
                        (language) => language.value === value,
                    )?.label ?? 'Plain Text'}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-[200px] p-0"
                onCloseAutoFocus={() => setSearchValue('')}
            >
                <Command shouldFilter={false}>
                    <CommandInput
                        className="h-9"
                        value={searchValue}
                        onValueChange={(value) => setSearchValue(value)}
                        placeholder="Search language..."
                    />
                    <CommandEmpty>No language found.</CommandEmpty>

                    <CommandList className="h-[344px] overflow-y-auto">
                        <CommandGroup>
                            {items.map((language) => (
                                <CommandItem
                                    key={language.label}
                                    className="cursor-pointer"
                                    value={language.value}
                                    onSelect={(value) => {
                                        editor.tf.setNodes<TCodeBlockElement>(
                                            { lang: value },
                                            { at: element },
                                        );
                                        setSearchValue(value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            value === language.value
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    {language.label}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

function CopyButton({
    value,
    ...props
}: { value: (() => string) | string } & Omit<
    React.ComponentProps<typeof Button>,
    'value'
>) {
    const [hasCopied, setHasCopied] = React.useState(false);

    React.useEffect(() => {
        setTimeout(() => {
            setHasCopied(false);
        }, 2000);
    }, []);

    return (
        <Button
            onClick={() => {
                void navigator.clipboard.writeText(
                    typeof value === 'function' ? value() : value,
                );
                setHasCopied(true);
            }}
            {...props}
        >
            <span className="sr-only">Copy</span>
            {hasCopied ? (
                <CheckIcon className="!size-3" />
            ) : (
                <CopyIcon className="!size-3" />
            )}
        </Button>
    );
}

export function CodeLineElement(props: PlateElementProps) {
    return <PlateElement {...props} />;
}

export function CodeSyntaxLeaf(props: PlateLeafProps<TCodeSyntaxLeaf>) {
    const tokenClassName = props.leaf.className as string;

    return <PlateLeaf className={tokenClassName} {...props} />;
}
