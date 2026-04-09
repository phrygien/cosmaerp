@props([
    'label'             => '',
    'placeholder'       => 'Sélectionner...',
    'searchPlaceholder' => 'Rechercher...',
    'options'           => [],
    'optionValue'       => 'id',
    'optionLabel'       => 'name',
    'emptyMessage'      => 'Aucun résultat trouvé',
    'model'             => '',
])

@php
    $optionsJson = collect($options)->map(fn($o) => [
        'value' => (string) data_get($o, $optionValue),
        'label' => (string) data_get($o, $optionLabel),
    ])->values();
@endphp

<div
    {{ $attributes }}
    class="space-y-1.5"
    x-data="{
        search: '',
        open: false,
        dropdownStyle: '',
        selected: @entangle($model),
        options: {{ Js::from($optionsJson) }},
        get filtered() {
            if (!this.search) return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
        },
        get selectedLabel() {
            const found = this.options.find(o => o.value === String(this.selected));
            return found ? found.label : null;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    const rect       = this.$refs.trigger.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom - 8;
                    const spaceAbove = rect.top - 8;

                    if (spaceBelow >= 150 || spaceBelow >= spaceAbove) {
                        this.dropdownStyle = `top: 100%; bottom: auto; max-height: ${spaceBelow}px;`;
                    } else {
                        this.dropdownStyle = `bottom: 100%; top: auto; max-height: ${spaceAbove}px; margin-bottom: 4px;`;
                    }

                    this.$refs.searchInput.focus();
                });
            }
        },
        select(value) {
            this.selected = value;
            this.open = false;
            this.search = '';
        }
    }"
>
    @if($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div class="relative">
        <button
            type="button"
            x-ref="trigger"
            x-on:click="toggle()"
            class="w-full flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm shadow-xs hover:bg-zinc-50 dark:hover:bg-zinc-700/50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="selectedLabel ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400'"
        >
            <span x-text="selectedLabel ?? '{{ $placeholder }}'"></span>
            <svg
                class="size-4 text-zinc-400 transition-transform duration-200"
                :class="open ? 'rotate-180' : ''"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
            >
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            x-show="open"
            x-on:click.outside="open = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :style="dropdownStyle"
            class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-lg flex flex-col"
        >
            <div class="p-2 border-b border-zinc-100 dark:border-zinc-700 shrink-0">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                    <input
                        type="text"
                        x-model="search"
                        x-on:click.stop
                        x-ref="searchInput"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full rounded-md border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 pl-8 pr-3 py-1.5 text-sm text-zinc-700 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>

            <ul class="overflow-y-auto py-1 flex-1">
                <template x-for="option in filtered" :key="option.value">
                    <li>
                        <button
                            type="button"
                            x-on:click="select(option.value)"
                            x-text="option.label"
                            class="w-full text-left px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium': String(selected) === option.value }"
                        ></button>
                    </li>
                </template>

                <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400 italic">
                    {{ $emptyMessage }}
                </li>
            </ul>
        </div>
    </div>

    @if($model)
        @error($model)
        <flux:error>{{ $message }}</flux:error>
        @enderror
    @endif
</div>
