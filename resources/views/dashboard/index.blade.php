<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold">Projects</h3>
                        <a href="{{ route('projects.create') }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            + New Project
                        </a>
                    </div>

                    @if($projects->isEmpty())
                        <p class="text-gray-500">No projects yet. Create your first project to get started!</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($projects as $project)
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition">
                                    <h4 class="text-lg font-semibold mb-2">{{ $project->name }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $project->slug }}</p>

                                    <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                                        <div>
                                            <span class="text-gray-500">API Keys:</span>
                                            <span class="font-semibold">{{ $project->api_keys_count }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Deployments:</span>
                                            <span class="font-semibold">{{ $project->deployments_count }}</span>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="{{ route('dashboard.project', $project) }}"
                                            class="flex-1 text-center bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                            Dashboard
                                        </a>
                                        <a href="{{ route('projects.show', $project) }}"
                                            class="flex-1 text-center bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
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