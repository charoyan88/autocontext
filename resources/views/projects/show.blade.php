<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
                {{ $project->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.project', $project) }}"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                    View Dashboard
                </a>
                <a href="{{ route('projects.edit', $project) }}"
                    class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-emerald-100/80 border border-emerald-300 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-700/60 dark:text-emerald-200 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Project Info -->
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Project Information</h3>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-slate-500">Slug</dt>
                        <dd class="font-mono">{{ $project->slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd>
                            <span
                                class="px-2 py-1 rounded text-sm {{ $project->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                {{ ucfirst($project->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Default Region</dt>
                        <dd>{{ $project->default_region }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Created</dt>
                        <dd>{{ $project->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- API Keys -->
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">API Keys</h3>
                    <form action="{{ route('projects.api-keys.store', $project) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-1 px-3 rounded-lg text-sm">
                            Generate New Key
                        </button>
                    </form>
                </div>
                @if($project->apiKeys->isEmpty())
                    <p class="text-slate-500">No API keys yet</p>
                @else
                    <div class="space-y-2">
                        @foreach($project->apiKeys as $apiKey)
                            <div class="border border-slate-200/70 dark:border-slate-800/70 rounded-lg p-4 flex justify-between items-center bg-white/70 dark:bg-slate-900/60">
                                <div class="flex-1">
                                    <div class="font-mono text-sm">{{ $apiKey->key }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $apiKey->description ?? 'No description' }} •
                                        Last used:
                                        {{ $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'Never' }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 text-sm font-semibold"
                                        data-copy="{{ $apiKey->key }}">
                                        Copy
                                    </button>
                                    <span
                                        class="px-2 py-1 rounded text-sm {{ $apiKey->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }}">
                                        {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <form action="{{ route('projects.api-keys.update', [$project, $apiKey]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $apiKey->is_active ? 0 : 1 }}">
                                        <button type="submit"
                                            class="text-sky-600 hover:text-sky-500 text-sm font-semibold">
                                            {{ $apiKey->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('projects.api-keys.destroy', [$project, $apiKey]) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to revoke this key? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-400 text-sm font-semibold">
                                            Revoke
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Downstream Configuration -->
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Downstream Configuration</h3>
                @php
                    $endpoint = $project->downstreamEndpoint;
                    $configJson = $endpoint && $endpoint->config ? json_encode($endpoint->config, JSON_PRETTY_PRINT) : '';
                @endphp
                <form action="{{ route('projects.downstream.update', $project) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="type"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300">Type</label>
                            <select name="type" id="type"
                                class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400 sm:text-sm">
                                <option value="file" {{ ($endpoint?->type ?? '') === 'file' ? 'selected' : '' }}>File</option>
                                <option value="http" {{ ($endpoint?->type ?? '') === 'http' ? 'selected' : '' }}>HTTP</option>
                                <option value="sentry" {{ ($endpoint?->type ?? '') === 'sentry' ? 'selected' : '' }}>Sentry</option>
                            </select>
                        </div>

                        <div>
                            <label for="endpoint_url"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300">Endpoint URL / DSN</label>
                            <input type="text" name="endpoint_url" id="endpoint_url"
                                value="{{ old('endpoint_url', $endpoint?->endpoint_url) }}"
                                class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400 sm:text-sm"
                                placeholder="https://example.com/logs or Sentry DSN">
                        </div>
                    </div>

                    <div>
                        <label for="config_json" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Config (JSON)</label>
                        <textarea name="config_json" id="config_json" rows="3"
                            class="mt-1 block w-full rounded-md border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 shadow-sm focus:border-sky-500 focus:ring-sky-400 sm:text-sm font-mono placeholder-slate-500"
                            placeholder='{"headers": {"Authorization": "Bearer token"}}'>{{ old('config_json', $configJson) }}</textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            {{ old('is_active', $endpoint?->is_active ?? false) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-400">
                        <label for="is_active" class="ml-2 block text-sm text-slate-900 dark:text-slate-100">Enable
                            Forwarding</label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-sky-600 hover:bg-sky-500 text-white font-semibold py-2 px-4 rounded-lg shadow-sm">
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Deployments -->
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Recent Deployments</h3>
                @if($project->deployments->isEmpty())
                    <p class="text-slate-500">No deployments yet</p>
                @else
                    <div class="space-y-2">
                        @foreach($project->deployments as $deployment)
                            <div class="border-l-4 border-sky-500 pl-3 py-2">
                                <div class="flex justify-between">
                                    <div>
                                        <span class="font-semibold">{{ $deployment->version }}</span>
                                        <span class="text-sm text-slate-500">• {{ $deployment->environment }}</span>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        {{ $deployment->started_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-copy]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.getAttribute('data-copy');
                    if (!value) {
                        return;
                    }
                    try {
                        await navigator.clipboard.writeText(value);
                        const original = button.textContent;
                        button.textContent = 'Copied';
                        setTimeout(() => {
                            button.textContent = original;
                        }, 1200);
                    } catch (error) {
                        const original = button.textContent;
                        button.textContent = 'Copy failed';
                        setTimeout(() => {
                            button.textContent = original;
                        }, 1200);
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
