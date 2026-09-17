@props(['name', 'label', 'type' => 'text', 'value' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="label">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" {{ $attributes->except('class') }}
                  class="w-full border border-gray-300 p-3 text-sm focus:border-black focus:outline-none">{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->except('class') }} class="input-box">{{ $slot }}</select>
    @elseif ($type === 'color')
        <input id="{{ $name }}" name="{{ $name }}" type="color" value="{{ old($name, $value) }}" {{ $attributes->except('class') }}
               class="h-10 w-full cursor-pointer border border-gray-300 bg-white p-1 focus:border-black focus:outline-none">
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->except('class') }}
               class="w-full border-0 border-b border-gray-300 px-0 py-2 text-sm focus:border-black focus:ring-0 focus:outline-none">
    @endif
    @error($name)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
