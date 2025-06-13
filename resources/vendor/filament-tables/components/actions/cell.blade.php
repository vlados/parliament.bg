<x-filament-tables::cell
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-ta-actions-cell'])
    "
>
    <div class="whitespace-nowrap px-2 py-2">
        {{ $slot }}
    </div>
</x-filament-tables::cell>
