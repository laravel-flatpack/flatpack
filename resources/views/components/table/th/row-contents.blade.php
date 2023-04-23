@aware(['component'])

@if ($component->collapsingColumnsAreEnabled() && $component->hasCollapsedColumns())
    @php
        $theme = $component->getTheme();
    @endphp

    <th
        scope="col"
        {{
            $attributes
                ->merge(['class' => 'table-cell dark:bg-gray-800'])
                ->class([
                    'md:hidden' =>
                        (($component->shouldCollapseOnMobile() && $component->shouldCollapseOnTablet()) ||
                        ($component->shouldCollapseOnTablet() && ! $component->shouldCollapseOnMobile()))
                ])
                ->class(['sm:hidden' => $component->shouldCollapseOnMobile() && ! $component->shouldCollapseOnTablet()])
        }}
    ></th>
@endif
