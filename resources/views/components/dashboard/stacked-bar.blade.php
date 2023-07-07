<div class="w-full col-span-4 md:col-span-2 py-2 antialiased">
    <div class="bg-white rounded shadow py-4 px-5 h-36 w-full overflow-hidden">
        @if (!empty($heading))
        <div class="flex w-full">
            <h4 class="cursor-default text-2xs uppercase text-gray-400 font-medium leading-tight">{{ $heading }}</h4>
        </div>
        @endif
        <div class="relative h-full overflow-hidden">
            <div class="flex flex-col h-full gap-2">
                <div class="flex w-full py-1">
                    <h3 class="text-3xl text-gray-700 font-bold leading-tight">{{ number_format($total, 0) }}</h3>
                </div>
                <div class="overflow-hidden rounded-full h-2 bg-gray-100 flex transition-all duration-500">
                    @foreach ($data as $item)
                        <div class="h-full chart-color-{{ data_get($item, 'color', 'blue') }}" style="width:{{ $percentage(data_get($item, 'value', 0)) }}%"></div>    
                    @endforeach
                </div>
                <div class="w-full cursor-default soft-scrollbar overflow-x-auto overflow-y-hidden">
                    <div class="flex py-2">
                    @foreach ($data as $item)
                        <div class="pr-4 flex items-center justify-start text-xs gap-1">
                            <span class="inline-block w-2 h-2 rounded-full mr-1 align-middle chart-color-{{ data_get($item, 'color', 'blue') }}">&nbsp;</span>
                            <span class="align-middle whitespace-nowrap mr-1">{{ data_get($item, 'label', '') }}</span>
                            <span class="align-middle text-gray-500">{{ $percentage(data_get($item, 'value', 0)) }}%</span>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>