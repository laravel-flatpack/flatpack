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
        $fieldDefinition = FormSchemaFields::fieldDefinitionById($schema, $fieldId);
        if ($fieldDefinition === null || trim((string) ($fieldDefinition['type'] ?? '')) !== 'file-upload') {
            throw ValidationException::withMessages([
                'field' => 'This field is not a file-upload field.',
            ]);
        }

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
            'mode' => trim((string) ($fieldDefinition['mode'] ?? 'url')),
        ]);
    }
}
