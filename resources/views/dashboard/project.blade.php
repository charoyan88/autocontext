<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
                {{ $project->name }} - Dashboard
            </h2>
            <form method="POST" action="{{ route('stats.flush') }}">
                @csrf
                <button type="submit"
                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                    Flush Stats
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Incoming</div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                        {{ number_format($stats['total_incoming']) }}</div>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Outgoing</div>
                    <div class="text-3xl font-bold text-emerald-600">{{ number_format($stats['total_outgoing']) }}</div>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Filtered</div>
                    <div class="text-3xl font-bold text-amber-500">{{ number_format($stats['total_filtered']) }}</div>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Savings</div>
                    <div class="text-3xl font-bold text-sky-600">{{ $stats['savings_percentage'] }}%</div>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Deploy Errors</div>
                    <div class="text-3xl font-bold text-rose-500">
                        {{ number_format($stats['total_deployment_errors']) }}</div>
                </div>
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-slate-500 dark:text-slate-400">Forward Failed</div>
                    <div class="text-3xl font-bold text-rose-500">
                        {{ number_format($stats['total_forward_failed']) }}</div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Last 24 Hours</h3>
                <canvas id="statsChart" width="400" height="100"></canvas>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Deployments -->
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Recent Deployments</h3>
                    @if($recentDeployments->isEmpty())
                        <p class="text-slate-500">No deployments yet</p>
                    @else
                        <div class="space-y-2">
                            @foreach($recentDeployments as $deployment)
                                <div class="border-l-4 border-sky-500 pl-3 py-2">
                                    <div class="font-semibold">{{ $deployment->version }}</div>
                                    <div class="text-sm text-slate-500">{{ $deployment->environment }} -
                                        {{ $deployment->started_at->diffForHumans() }}</div>
                                    <div class="text-sm text-slate-500">
                                        Errors in window: {{ $deploymentErrorCounts[$deployment->id] ?? 0 }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Top Errors -->
                <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Top Errors</h3>
                    @if($topErrors->isEmpty())
                        <p class="text-slate-500">No errors recorded</p>
                    @else
                        <div class="space-y-2">
                            @foreach($topErrors as $error)
                                <div class="border-l-4 border-rose-500 pl-3 py-2">
                                    <div class="font-semibold text-sm">{{ Str::limit($error->last_message, 50) }}</div>
                                    <div class="text-sm text-slate-500">Count: {{ $error->count_total }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('statsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [
                        {
                            label: 'Incoming',
                            data: @json($chartData['incoming']),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        },
                        {
                            label: 'Outgoing',
                            data: @json($chartData['outgoing']),
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1
                        },
                        {
                            label: 'Filtered',
                            data: @json($chartData['filtered']),
                            borderColor: 'rgb(255, 159, 64)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>
