@props(['as' => 'h1', 'center' => false])

<div {{ $attributes->class(['text-center' => $center]) }}>
    <{{ $as }} class="eyebrow">{{ $slot }}</{{ $as }}>
    <div @class(['eyebrow-rule', 'mx-auto' => $center])></div>
</div>
