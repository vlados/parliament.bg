<x-filament-tables::cell
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-ta-selection-cell w-1'])
    "
>
    <div class="px-2 py-2">
        {{ $slot }}
    </div>
</x-filament-tables::cell>
