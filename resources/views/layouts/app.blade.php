<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-zinc-100 dark:bg-zinc-900 min-h-screen">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
