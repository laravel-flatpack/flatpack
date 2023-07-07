<div class="w-full col-span-2 md:col-span-1 py-2 antialiased">
    <div class="rounded bg-white shadow relative h-36 w-full overflow-hidden">
        <div class="px-4 pt-4 pb-12 text-left relative z-10">
            <div class="flex justify-between items-center cursor-default">
                @if (!empty($heading))
                    <h4 class="text-2xs uppercase text-gray-400 font-medium leading-tight">{{ $heading }}</h4>
                @endif
                @if (!empty($increase) && is_numeric($increase))
                    @if ($increase > 0)
                        <p class="text-xs text-green-500 leading-tight">&#9650; {{ $increase }}%</p>
                    @else
                        <p class="text-xs text-red-500 leading-tight">&#9660; {{ $increase }}%</p>
                    @endif
                @endif
            </div>
            <h3 class="text-3xl text-gray-700 font-bold leading-tight mt-1 mb-2">{{ $value }}</h3>
        </div>
        <div class="absolute bottom-0 inset-x-0">
            <canvas id="chart-{{ $id }}" height="70" style="padding:0; min-width:100%;"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const data = @js($data);
    const ctx = document.getElementById('chart-{{ $id }}').getContext('2d');
    const chart = new Flatpack.charts.create(ctx, {
        type: "line",
        data: {
            labels: data,
            datasets: [
                {
                    fill: true,
                    tension: 0.3,
                    backgroundColor: "rgba(101, 116, 205, 0.1)",
                    borderColor: "rgba(101, 116, 205, 0.8)",
                    borderWidth: 2,
                    data,
                },
            ],
        },
        options: {
            animation: false,
            pointRadius: 0,
            plugins: {
                legend: {
                    display: false,
                },

            },
            scales: {
                x: {
                    display: false,
                },
                y: {
                    display: false,
                }
            }
        }
    });
});
</script>
@endpush