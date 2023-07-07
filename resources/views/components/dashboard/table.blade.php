<div class="w-full py-2 col-span-4 lg:col-span-2 antialiased">
    <div class="bg-white rounded shadow w-full">
        @if (!empty($heading))
        <div class="flex w-full py-4 px-5 border-b border-gray-200">
            <h4 class="cursor-default text-2xs uppercase text-gray-400 font-medium leading-tight">{{ $heading }}</h4>
        </div>
        @endif
        <div class="relative h-full soft-scrollbar overflow-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach ($columns as $column)
                        <th scope="col" class="table-cell cursor-default px-3 py-2 md:px-6 md:py-3 text-center md:text-left bg-gray-50">
                            <span class="flex whitespace-nowrap items-center space-x-1 text-left text-2xs leading-4 font-medium text-gray-500 uppercase tracking-wider group focus:outline-none">
                                {{ $column->getTitle() }}
                            </span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($data as $row)
                    <tr onclick="location.href = '{{ isset($getRowAction) ? $getRowAction($row) : '#' }}';" class="cursor-pointer hover:bg-gray-100 {{ ($loop->iteration % 2 == 0) ? 'bg-gray-50' : 'bg-white' }}">
                    @foreach ($columns as $column)
                        <td class="px-6 py-4 w-16 whitespace-nowrap text-sm font-normal">
                            {{ $column->renderContents($row) }}
                        </td>
                    @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}">
                            <div class="flex justify-center items-center space-x-2">
                                <span class="font-medium py-8 text-gray-400 text-lg">No items found.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>