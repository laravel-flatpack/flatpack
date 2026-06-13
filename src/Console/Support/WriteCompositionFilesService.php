<?php

declare(strict_types=1);

namespace Flatpack\Console\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

final readonly class WriteCompositionFilesService
{
    public function __construct(
        private Filesystem $files,
        private Repository $config,
    ) {}

    /**
     * @return array{formPath: string, listPath: string}
     */
    public function write(MakeCompositionInput $input, string $formStub, string $listStub): array
    {
        $paths = $this->compositionYamlPaths($input->entities);

        if (! $input->force && ($this->files->exists($paths['formPath']) || $this->files->exists($paths['listPath']))) {
            throw new InvalidArgumentException('Flatpack files for ' . $input->entities . ' already exist. Use --force to overwrite.');
        }

        $this->files->ensureDirectoryExists(dirname($paths['formPath']));
        $this->files->put($paths['formPath'], $formStub);
        $this->files->put($paths['listPath'], $listStub);

        return $paths;
    }

    /**
     * @return array{formPath: string, listPath: string}
     */
    private function compositionYamlPaths(string $entities): array
    {
        $basePath = rtrim((string) $this->config->get('flatpack.composition.path', base_path('flatpack')), DIRECTORY_SEPARATOR);
        $entityPath = $basePath . DIRECTORY_SEPARATOR . $entities;

        return [
            'formPath' => $entityPath . DIRECTORY_SEPARATOR . 'form.yaml',
            'listPath' => $entityPath . DIRECTORY_SEPARATOR . 'list.yaml',
        ];
    }
}
