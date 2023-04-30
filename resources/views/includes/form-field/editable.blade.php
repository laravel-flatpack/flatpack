@if (strtolower($type ?? '') === 'editable')
<div x-cloak x-data="Flatpack.editable.editableInstance()" class="editable">
    <div class="flex items-center justify-start w-full" :class="{ 'is-editing': isEditing }">
        <label 
            for="editable-{{ $key }}" 
            {{ $attributes->class([
                'flex justify-start items-center gap-2 w-full' => true,
                'text-3xl font-bold' => $size === 'large',
                'text-2xl font-bold' => $size === 'base' || $size === 'medium',
                'text-xl font-medium' => $size === 'small',
            ]) }} 
        >
            @if (! empty($label))
            <span class="text-xs font-bold uppercase text-gray-600 pr-1">{{ $label }}</span>
            @endif

            <div class="editable-field-value">
                <span 
                    :class="{ 'text-gray-500': $wire.{{ $binding }}.{{ $key }} == null }"
                    x-text="$wire.{{ $binding }}.{{ $key }} || '{{ $placeholder }}'"
                    x-on:click.stop="toggleEditing"></span>
                
                <button x-on:click.stop="toggleEditing" class="outline-none inline-flex justify-center items-center group transition-all ease-in duration-150 focus:ring-2 focus:ring-offset-2 hover:shadow-sm disabled:opacity-80 disabled:cursor-not-allowed rounded gap-x-2 text-sm px-4 py-2 ring-primary-600 text-primary-600 hover:bg-secondary-100 w-8 h-8">
                    <x-icon name="pencil"  class="w-4 h-4 shrink-0" />
                </button>
            </div>
    
            <div class="editable-field-input">
                <input
                    x-on:change.debounce="Flatpack.form.inputChange($event, '{{ $binding }}.{{ $key }}')"
                    wire:model.defer="{{ $binding }}.{{ $key }}"
                    wire:key="{{ $binding }}-{{ $key }}"
                    x-ref="input"
                    x-on:click.away="toggleEditing"
                    x-on:keydown.enter="disableEditing"
                    x-on:keydown.window.escape="disableEditing"
                    id="editable-{{ $key }}"
                    type="text"
                    name="{{ $key }}"
                    placeholder="{{ $placeholder }}"
                    {{ $attributes->class([
                        'form-field-input',
                        'w-full h-auto',
                    ]) }}
                />

                <button x-on:click.stop="toggleEditing" class="outline-none inline-flex justify-center items-center group transition-all ease-in duration-150 focus:ring-2 focus:ring-offset-2 hover:shadow-sm disabled:opacity-80 disabled:cursor-not-allowed rounded gap-x-2 text-sm px-4 py-2 ring-primary-600 text-primary-600 hover:bg-secondary-100 w-8 h-8">
                    <x-icon name="check" class="w-4 h-4 shrink-0" />
                </button>
            </div>
        </label>
    </div>
</div>


@once
    @push('scripts')
        <script src="{{ asset('flatpack/js/editable.js') }}"></script>
    @endpush
@endonce

@endif
