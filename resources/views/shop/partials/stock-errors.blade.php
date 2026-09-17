@if ($errors->has('stock'))
    <div class="mb-8 border border-orange-200 bg-white p-6" role="alert">
        <p class="mb-2 flex items-center text-sm font-medium text-orange-900">
            <x-icon name="alert" class="mr-2 h-4 w-4" /> Le stock a changé depuis l'ajout au panier
        </p>
        <ul class="list-inside list-disc space-y-1 text-sm text-orange-700">
            @foreach ($errors->get('stock') as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        <p class="mt-3 text-xs text-orange-700">Ajustez les quantités ci-dessous, rien n'a été débité.</p>
    </div>
@endif
