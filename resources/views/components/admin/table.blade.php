@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'overflow-x-auto border border-gray-200 bg-white']) }}>
    {{-- Cells stay on one line (amounts, references); a cell opts out with whitespace-normal. --}}
    <table class="w-full [&_td]:whitespace-nowrap [&_td]:px-4 [&_th]:px-4">
        <thead class="bg-gray-50">
            <tr>
                {{-- A header is a label, or [label, extra classes] (e.g. a column hidden on narrow screens). --}}
                @foreach ($headers as $header)
                    @php [$label, $extra] = is_array($header) ? $header : [$header, '']; @endphp
                    <th class="px-6 py-3 text-left text-xs font-light tracking-wider whitespace-nowrap text-gray-500 uppercase {{ $extra }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>
