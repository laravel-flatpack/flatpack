<div x-data="{ modalOpen: false }">
@if ($confirm)
    <div x-show="modalOpen">
        <div class="fixed inset-0 z-50 w-screen overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="flex justify-between px-4 py-3 bg-gray-50">
                        <span>{{ $label }}</span>
                        <span @click="modalOpen = !modalOpen" class="cursor-pointer">
                            <x-flatpack::icon icon="close" size="small" />
                        </span>
                    </div>
                    <div class="p-4 bg-white">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <p class="text-gray-800 text-md">
                                    {{ $confirmationMessage }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="{{ $method }}('{{ $action }}', @js($options)); modalOpen = !modalOpen;"
                            class="button sm:ml-3 sm:w-auto {{ $style !== '' ? $style : 'primary' }}"
                            type="button">{{ $label }}</button>
                        <button
                            @click="modalOpen = !modalOpen"
                            class="mt-2 button sm:mt-0 sm:ml-3 sm:w-auto"
                            type="button">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
    <button
        @if ($confirm)
        @click="modalOpen = !modalOpen"
        @else
        wire:loading.attr="disabled"
        wire:click="{{ $method }}('{{ $action }}', @js($options))"
        @endif
        class="flex items-center w-full px-4 py-2 space-x-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900 dark:text-white dark:hover:bg-gray-600"
        type="button"
        role="menuitem"
    >
        <span class="whitespace-nowrap">{{ $label }}</span>
    </button>
</div>
