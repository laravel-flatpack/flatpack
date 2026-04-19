<?php

declare(strict_types=1);

namespace Flatpack\Schema\Lists;

use Flatpack\Schema\Generated\CompositionSchemaKeys;
use Flatpack\Support\SuccessRedirect;

final class BulkActions
{
    /**
     * @param  array<string, mixed>|null  $schema
     * @return list<array{id: string, label: string, action: string, icon: string, variant: string, success_message?: string, confirm?: bool, success_redirect?: string}>
     */
    public static function fromSchema(?array $schema): array
    {
        if ($schema === null) {
            return [];
        }

        $raw = $schema['bulk_actions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        $out = [];

        foreach ($raw as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $action = isset($definition['action']) ? trim((string) $definition['action']) : '';
            $icon = isset($definition['icon']) ? trim((string) $definition['icon']) : '';

            if ($label === '' || $action === '') {
                continue;
            }

            if (! self::isConfiguredAction($action)) {
                continue;
            }

            $id = is_string($key) && $key !== '' ? $key : (string) count($out);

            $normalized = [
                'id' => $id,
                'label' => $label,
                'action' => $action,
                'icon' => $icon,
                'variant' => self::normalizeVariant($definition['variant'] ?? null),
            ];
            if (isset($definition['success_message'])) {
                $msg = trim((string) $definition['success_message']);
                if ($msg !== '') {
                    $normalized['success_message'] = $msg;
                }
            }
            if (($definition['confirm'] ?? null) === true) {
                $normalized['confirm'] = true;
            }
            if (isset($definition['success_redirect'])) {
                $sr = SuccessRedirect::normalize($definition['success_redirect']);
                if ($sr !== null) {
                    $normalized['success_redirect'] = $sr;
                }
            }
            $out[] = $normalized;
        }

        return $out;
    }

    private static function isConfiguredAction(string $action): bool
    {
        $handlerClass = config("flatpack.bulk_actions.{$action}");

        return is_string($handlerClass) && trim($handlerClass) !== '';
    }

    private static function normalizeVariant(mixed $raw): string
    {
        if ($raw === null || ! is_string($raw)) {
            return 'outline';
        }

        $variant = trim($raw);
        if ($variant === '') {
            return 'outline';
        }

        if ($variant === 'primary') {
            return 'default';
        }

        if (in_array($variant, CompositionSchemaKeys::BUTTON_VARIANT_UI_VALUES, true)) {
            return $variant;
        }

        return 'outline';
    }
}
