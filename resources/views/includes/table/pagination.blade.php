@if ($showPagination)
    <div class="px-6 py-2 md:p-0">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="hidden md:flex justify-center items-center gap-4">
                @include('flatpack::includes.table.per-page')
                <p class="text-sm text-gray-700 leading-5 dark:text-white">
                    <span>{!! __('Showing') !!}</span>
                    <span class="font-medium">{{ $rows->firstItem() }}</span>
                    <span>{!! __('to') !!}</span>
                    <span class="font-medium">{{ $rows->lastItem() }}</span>
                    <span>{!! __('of') !!}</span>
                    <span class="font-medium">{{ $rows->total() }}</span>
                    <span>{!! __('results') !!}</span>
                </p>
            </div>
            <div>
                @if ($paginationEnabled && $rows->lastPage() > 1)
                    {{ $rows->links('flatpack::includes.table.partials.pagination') }}
                @endif
            </div>
        </div>
    </div>
@endif
