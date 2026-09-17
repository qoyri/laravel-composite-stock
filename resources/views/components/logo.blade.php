@props(['size' => 'md'])

@php
    $image = ['sm' => 'h-8 w-8', 'md' => 'h-12 w-12', 'lg' => 'h-16 w-16'][$size];
    $text = ['sm' => 'text-sm', 'md' => 'text-lg', 'lg' => 'text-2xl'][$size];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center space-x-3']) }}>
    <img src="{{ asset('images/logo.png') }}" alt="L'Atelier de l'Humour — logo" class="{{ $image }} object-contain" width="64" height="64">
    <div class="flex flex-col">
        <span class="{{ $text }} leading-tight font-semibold text-gray-800">L'Atelier de l'Humour</span>
        @if ($size !== 'sm')
            <span class="{{ $size === 'md' ? 'text-sm' : 'text-base' }} -mt-1 leading-tight text-gray-600">T-shirts humoristiques</span>
        @endif
    </div>
</div>
