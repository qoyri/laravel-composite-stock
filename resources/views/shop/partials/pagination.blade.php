@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1 text-xs tracking-wider uppercase">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 text-gray-300">Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 hover:text-gray-500">Précédent</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-2 text-gray-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="bg-black px-3 py-2 text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="border border-transparent px-3 py-2 hover:border-gray-300">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 hover:text-gray-500">Suivant</a>
        @else
            <span class="px-3 py-2 text-gray-300">Suivant</span>
        @endif
    </nav>
@endif
