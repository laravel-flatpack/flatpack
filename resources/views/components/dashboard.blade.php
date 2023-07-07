<div class="w-full grid grid-cols-4 gap-4 my-5">
    @forelse ($widgets as $widget)
    <x-dynamic-component component="flatpack-widget-{{ $widget->componentName }}" />
    @empty
        @include('flatpack::components.dashboard.empty')
    @endforelse
</div>
