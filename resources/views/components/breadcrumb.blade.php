@props(['items' => []])

@if ($items !== [])
    <nav aria-label="Fil d'Ariane" class="border-b border-gray-100 bg-gray-50 px-6 py-3">
        <ol class="flex flex-wrap items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-black">Accueil</a></li>
            @foreach ($items as $label => $url)
                <li class="flex items-center">
                    <x-icon name="chevron-right" class="mx-2 h-3 w-3 text-gray-400" />
                    @if ($url)
                        <a href="{{ $url }}" class="text-gray-500 hover:text-black">{{ $label }}</a>
                    @else
                        <span class="font-medium text-black">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
