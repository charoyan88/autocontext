<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
                {{ __('Users') }}
            </h2>
            <div class="text-sm text-slate-500 dark:text-slate-400">
                Total: {{ $users->total() }}
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/80 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-800/70 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-slate-900 dark:text-slate-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-slate-200/70 dark:border-slate-800/70">
                                    <th class="py-2 pr-4 font-semibold">Name</th>
                                    <th class="py-2 pr-4 font-semibold">Email</th>
                                    <th class="py-2 pr-4 font-semibold">Projects</th>
                                    <th class="py-2 pr-4 font-semibold">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr class="border-b border-slate-200/70 dark:border-slate-800/70 last:border-b-0">
                                        <td class="py-3 pr-4 font-medium text-slate-800 dark:text-slate-100">
                                            {{ $user->name }}
                                        </td>
                                        <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">
                                            {{ $user->email }}
                                        </td>
                                        <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">
                                            {{ $user->projects_count }}
                                        </td>
                                        <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">
                                            {{ $user->created_at?->format('Y-m-d') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-slate-500 dark:text-slate-400">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
