@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-sky-500 text-start text-base font-medium text-sky-700 dark:text-sky-300 bg-sky-50/70 dark:bg-sky-900/30 focus:outline-none focus:text-sky-800 dark:focus:text-sky-200 focus:bg-sky-100/70 dark:focus:bg-sky-900/50 focus:border-sky-600 dark:focus:border-sky-300 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50/70 dark:hover:bg-slate-800/60 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:text-slate-800 dark:focus:text-slate-200 focus:bg-slate-50/70 dark:focus:bg-slate-800/60 focus:border-slate-300 dark:focus:border-slate-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
