<?php

declare(strict_types=1);

namespace Flatpack\Services\Commands;

final readonly class MakeCompositionToggleSet
{
    public function __construct(
        public bool $basicActions,
        public bool $bulkDelete,
        public bool $autoFields,
        public bool $autoColumns,
        public bool $softDeleteActions,
    ) {}

    public static function defaults(): self
    {
        return new self(
            basicActions: true,
            bulkDelete: true,
            softDeleteActions: false,
            autoFields: true,
            autoColumns: true,
        );
    }

    public function withBasicActions(bool $value): self
    {
        return new self($value, $this->bulkDelete, $this->autoFields, $this->autoColumns, $this->softDeleteActions);
    }

    public function withBulkDelete(bool $value): self
    {
        return new self($this->basicActions, $value, $this->autoFields, $this->autoColumns, $this->softDeleteActions);
    }

    public function withAutoFields(bool $value): self
    {
        return new self($this->basicActions, $this->bulkDelete, $value, $this->autoColumns, $this->softDeleteActions);
    }

    public function withAutoColumns(bool $value): self
    {
        return new self($this->basicActions, $this->bulkDelete, $this->autoFields, $value, $this->softDeleteActions);
    }

    public function withSoftDeleteActions(bool $value): self
    {
        return new self($this->basicActions, $this->bulkDelete, $this->autoFields, $this->autoColumns, $value);
    }

    public function withSoftDeleteGuard(bool $usesSoftDeletes): self
    {
        if ($usesSoftDeletes) {
            return $this;
        }

        return $this->withSoftDeleteActions(false);
    }
}
