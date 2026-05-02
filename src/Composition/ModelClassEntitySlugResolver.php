<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionQuery;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Maps an Eloquent model class string to a Flatpack composition entity slug (directory name under `flatpack/`).
 */
final readonly class ModelClassEntitySlugResolver
{
    public function __construct(
        private ConfigRepository $config,
        private CompositionQuery $compositions,
        private CompositionValues $compositionValues,
    ) {}

    /**
     * Returns the first entity slug whose {@code list.yaml} declares the given model class.
     */
    public function firstEntitySlugForModel(string $modelClass): ?string
    {
        $candidate = trim($modelClass);
        if ($candidate === '') {
            return null;
        }

        $basePath = (string) $this->config->get('flatpack.composition.path', base_path('flatpack'));
        if (! is_dir($basePath)) {
            return null;
        }

        foreach (scandir($basePath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $basePath . DIRECTORY_SEPARATOR . $entry;
            if (! is_dir($dir)) {
                continue;
            }
            $list = $this->compositions->optional($entry, 'list');
            $resolvedModel = $this->compositionValues->modelClass($list);
            if (! is_string($resolvedModel) || trim($resolvedModel) === '') {
                continue;
            }
            if (strcasecmp(trim($resolvedModel), $candidate) === 0) {
                return $entry;
            }
        }

        return null;
    }
}
