<div class="flex flex-col w-full gap-0">
    <div class="flex flex-row items-center justify-between w-full my-5">
        <h1 class="text-3xl font-bold">{{ Str::ucfirst($entity) }}</h1>
        <div class="flex flex-row items-center justify-end gap-3">
            @include('flatpack::includes.toolbar', [
                'elements' => $toolbar,
                'entity' => $entity,
                'model' => $model,
            ])
        </div>
    </div>
    
    <x-livewire-tables::wrapper :component="$this">
        @if ($this->hasConfigurableAreaFor('before-tools'))
            @include($this->getConfigurableAreaFor('before-tools'), $this->getParametersForConfigurableArea('before-tools'))
        @endif

        <x-livewire-tables::tools>
            <x-flatpack::tools.sorting-pills />
            <x-flatpack::tools.filter-pills />
            <x-flatpack::tools.toolbar :entity="$entity" :model="$model" />
        </x-livewire-tables::tools>

        <x-livewire-tables::table>
            <x-slot name="thead">
                <x-flatpack::table.th.reorder />
                <x-flatpack::table.th.bulk-actions />
                <x-flatpack::table.th.row-contents />

                @foreach($columns as $index => $column)
                    @continue($column->isHidden())
                    @continue($this->columnSelectIsEnabled() && ! $this->columnSelectIsEnabledForColumn($column))
                    @continue($this->currentlyReorderingIsDisabled() && $column->isReorderColumn() && $this->hideReorderColumnUnlessReorderingIsEnabled())

                    <x-flatpack::table.th :column="$column" :index="$index" />
                @endforeach
            </x-slot>

            @if($this->secondaryHeaderIsEnabled() && $this->hasColumnsWithSecondaryHeader())
                <x-flatpack::table.tr.secondary-header :rows="$rows" />
            @endif

            <x-flatpack::table.tr.bulk-actions :rows="$rows" />

            @forelse ($rows as $rowIndex => $row)
                <x-flatpack::table.tr :row="$row" :rowIndex="$rowIndex">
                    <x-flatpack::table.td.reorder />
                    <x-flatpack::table.td.bulk-actions :row="$row" />
                    <x-flatpack::table.td.row-contents :rowIndex="$rowIndex" />

                    @foreach($columns as $colIndex => $column)
                        @continue($column->isHidden())
                        @continue($this->columnSelectIsEnabled() && ! $this->columnSelectIsEnabledForColumn($column))
                        @continue($this->currentlyReorderingIsDisabled() && $column->isReorderColumn() && $this->hideReorderColumnUnlessReorderingIsEnabled())

                        <x-flatpack::table.td :column="$column" :colIndex="$colIndex">
                            {{ $column->renderContents($row) }}
                        </x-flatpack::table.td>
                    @endforeach
                </x-flatpack::table.tr>

                <x-flatpack::table.row-contents :row="$row" :rowIndex="$rowIndex" />
            @empty
                <x-flatpack::table.empty />
            @endforelse

            @if ($this->footerIsEnabled() && $this->hasColumnsWithFooter())
                <x-slot name="tfoot">
                    @if ($this->useHeaderAsFooterIsEnabled())
                        <x-livewire-tables::table.tr.secondary-header :rows="$rows" />
                    @else
                        <x-livewire-tables::table.tr.footer :rows="$rows" />
                    @endif
                </x-slot>
            @endif
        </x-livewire-tables::table>

        <x-livewire-tables::pagination :rows="$rows" />

        @isset($customView)
            @include($customView)
        @endisset
    </x-livewire-tables::wrapper>

</div>
