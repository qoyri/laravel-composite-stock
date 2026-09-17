@props(['available'])

@if ($available === 0)
    <span {{ $attributes->merge(['class' => 'text-red-600']) }}>Épuisé</span>
@elseif ($available <= 3)
    <span {{ $attributes->merge(['class' => 'text-yellow-600']) }}>Plus que {{ $available }} en stock</span>
@else
    <span {{ $attributes->merge(['class' => 'text-green-600']) }}>En stock</span>
@endif
