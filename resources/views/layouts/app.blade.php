<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-slate-100 dark:bg-slate-900 min-h-screen">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
