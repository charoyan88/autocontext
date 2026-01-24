@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 text-slate-900 dark:text-slate-100 focus:border-sky-500 dark:focus:border-sky-400 focus:ring-sky-400 dark:focus:ring-sky-400 rounded-md shadow-sm']) }}>
