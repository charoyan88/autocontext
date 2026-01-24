<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
            Create New Project
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('projects.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Project
                                Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                                class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400">
                            <p class="mt-1 text-sm text-slate-500">Unique identifier for API endpoints</p>
                            @error('slug')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="default_region"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300">Default
                                Region</label>
                            <input type="text" name="default_region" id="default_region"
                                value="{{ old('default_region', 'us-east-1') }}" required
                                class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400">
                            @error('default_region')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('projects.index') }}"
                                class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Cancel</a>
                            <button type="submit"
                                class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                                Create Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
