<div class="fixed inset-0 z-50 w-screen overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div @click.away="visible = !visible" class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="flex justify-between px-4 py-3 bg-gray-50">
                <span>{{ $title }}</span>
                <span @click="visible = !visible" class="cursor-pointer">
                    <x-flatpack::icon icon="close" size="small" />
                </span>
            </div>
            <div class="p-4 bg-white">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        {{ $slot }}
                    </div>
                </div>
            </div>
            @if (!empty($footer))
            <footer {{ $footer->attributes->class(['px-4 py-3 bg-gray-50 sm:flex sm:flex-row-reverse']) }}>
                {{ $footer }}
            </footer>
            @endif
        </div>
    </div>
</div>
