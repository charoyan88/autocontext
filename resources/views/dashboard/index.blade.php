<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold">Projects</h3>
                        <a href="{{ route('projects.create') }}"
                            class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                            + New Project
                        </a>
                    </div>

                    @if($projects->isEmpty())
                        <p class="text-slate-500">No projects yet. Create your first project to get started!</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($projects as $project)
                                <div class="border border-slate-200/70 dark:border-slate-800/70 rounded-lg p-4 bg-white/70 dark:bg-slate-900/60 hover:shadow-lg transition">
                                    <h4 class="text-lg font-semibold mb-2">{{ $project->name }}</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $project->slug }}</p>

                                    <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                                        <div>
                                            <span class="text-slate-500">API Keys:</span>
                                            <span class="font-semibold">{{ $project->api_keys_count }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-500">Deployments:</span>
                                            <span class="font-semibold">{{ $project->deployments_count }}</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="{{ route('dashboard.project', $project) }}"
                                            class="flex-1 text-center bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                            Dashboard
                                        </a>
                                        <a href="{{ route('projects.show', $project) }}"
                                            class="flex-1 text-center bg-slate-700 hover:bg-slate-600 text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                            Manage
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
