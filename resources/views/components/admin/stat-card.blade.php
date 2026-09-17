@props(['label', 'value', 'icon', 'hint' => null, 'tone' => null])

<div class="border border-gray-200 bg-white p-6">
    <div class="mb-4 flex items-center justify-between">
        <x-icon :name="$icon" class="h-5 w-5 text-gray-400" />
        @if ($hint)<span @class(['text-xs', 'text-red-600' => $tone === 'bad', 'text-gray-500' => $tone !== 'bad'])>{{ $hint }}</span>@endif
    </div>
    <p class="mb-1 text-xs tracking-wider text-gray-500 uppercase">{{ $label }}</p>
    <p class="text-2xl font-light">{{ $value }}</p>
</div>
