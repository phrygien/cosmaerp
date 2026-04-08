@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand
        wire:navigate
        href="{{ route('dashboard') }}"
        logo="https://tse4.mm.bing.net/th/id/OIP.wFgOFDYtlifJ2yVZfwiCUgHaHa?rs=1&pid=ImgDetMain&o=7&rm=3"
        logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
        name="Cosma parfumeries"
        {{ $attributes }}
    />
@else
    <flux:brand name="Cosma Parfumeries Erp" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
