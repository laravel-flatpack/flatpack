<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Composition\EntityComposition;
use Flatpack\Schema\Forms\FormCompositionMergeForPersistence;
use Flatpack\Schema\Forms\FormSchemaFields;
use Flatpack\Services\Uploads\FileUploadStorage;
use Flatpack\Services\Uploads\FileUploadUploadValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class FileUploadController
{
    public function __construct(
        private EntityComposition $entityComposition,
        private FileUploadStorage $fileUploadStorage,
        private FileUploadUploadValidator $fileUploadUploadValidator,
    ) {}

    /**
     * @throws ValidationException
     */
    public function upload(Request $request, string $entity): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
        ]);

        $schema = FormCompositionMergeForPersistence::merge($this->entityComposition->formSchema($entity));
        $fieldId = trim((string) ($validated['field'] ?? ''));
        [$fieldDefinition, $responseMode] = $this->resolveUploadFieldDefinition($schema, $fieldId);

        $files = $request->file('files');
        if (! is_array($files) || $files === []) {
            throw ValidationException::withMessages([
                'files' => 'At least one file is required.',
            ]);
        }

        $this->fileUploadUploadValidator->validate($fieldDefinition, $files);

        $uploaded = [];
        foreach ($files as $file) {
            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $uploaded[] = $this->fileUploadStorage->storeFile($fieldDefinition, $file);
        }

        return response()->json([
            'files' => $uploaded,
            'multiple' => ($fieldDefinition['multiple'] ?? false) === true,
            'mode' => $responseMode,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array{0: array<string, mixed>, 1: string}
     *
     * @throws ValidationException
     */
    private function resolveUploadFieldDefinition(?array $schema, string $fieldId): array
    {
        $fieldDefinition = FormSchemaFields::fieldDefinitionById($schema, $fieldId);
        if (! is_array($fieldDefinition)) {
            throw ValidationException::withMessages([
                'field' => 'This field is not an upload-enabled field.',
            ]);
        }

        $type = trim((string) ($fieldDefinition['type'] ?? ''));
        if ($type === 'file-upload') {
            $uploadConfig = $fieldDefinition['upload'] ?? null;
            if (! is_array($uploadConfig) || $uploadConfig === []) {
                throw ValidationException::withMessages([
                    'field' => 'This field does not define upload settings.',
                ]);
            }
            $effective = $this->effectiveUploadDefinition($uploadConfig);

            return [$effective, trim((string) ($fieldDefinition['mode'] ?? 'url'))];
        }

        if ($type !== 'rich-text' && $type !== 'block-editor') {
            throw ValidationException::withMessages([
                'field' => 'This field is not an upload-enabled field.',
            ]);
        }

        $uploadConfig = $fieldDefinition['upload'] ?? null;
        if (! is_array($uploadConfig) || $uploadConfig === []) {
            throw ValidationException::withMessages([
                'field' => 'This editor field does not define upload settings.',
            ]);
        }

        return [$this->effectiveUploadDefinition($uploadConfig), 'url'];
    }

    /**
     * @param  array<string, mixed>  $uploadConfig
     * @return array<string, mixed>
     */
    private function effectiveUploadDefinition(array $uploadConfig): array
    {
        $effective = [
            'type' => 'file-upload',
            'mode' => 'url',
            'multiple' => ($uploadConfig['multiple'] ?? false) === true,
        ];

        foreach ([
            'accept',
            'collection',
            'directory',
            'disk',
            'max_files',
            'max_size_kb',
            'visibility',
        ] as $key) {
            if (array_key_exists($key, $uploadConfig)) {
                $effective[$key] = $uploadConfig[$key];
            }
        }

        return $effective;
    }
}
