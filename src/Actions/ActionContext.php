<?php

declare(strict_types=1);

namespace Flatpack\Actions;

use Illuminate\Http\Request;

final readonly class ActionContext
{
    /**
     * @param  array<string, mixed>|null  $schema
     */
    public function __construct(
        public Request $request,
        public string $entity,
        public string $actionName,
        public string $modelClass,
        public ?string $record,
        public string $compositionType,
        public ?string $scope = null,
        public ?array $schema = null,
        public ?object $model = null,
    ) {}

    public function withRequest(Request $request): self
    {
        return new self(
            request: $request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withEntity(string $entity): self
    {
        return new self(
            request: $this->request,
            entity: $entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withActionName(string $actionName): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withModelClass(string $modelClass): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withRecord(string $record): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withCompositionType(string $compositionType): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withScope(string $scope): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $scope,
            schema: $this->schema,
            model: $this->model,
        );
    }

    public function withSchema(array $schema): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $schema,
            model: $this->model,
        );
    }

    public function withModel(object $model): self
    {
        return new self(
            request: $this->request,
            entity: $this->entity,
            actionName: $this->actionName,
            modelClass: $this->modelClass,
            record: $this->record,
            compositionType: $this->compositionType,
            scope: $this->scope,
            schema: $this->schema,
            model: $model,
        );
    }
}
