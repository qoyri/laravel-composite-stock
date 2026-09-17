@props(['action'])

{{-- Signed delta, never an absolute value (see App\Actions\Stock\AdjustStock). --}}
<form method="POST" action="{{ $action }}" class="flex items-center gap-2" x-data="{ delta: '' }">
    @csrf
    <input type="number" name="delta" x-model="delta" step="1" placeholder="±" required aria-label="Quantité à ajouter ou retirer"
           class="w-20 border border-gray-300 px-2 py-1 text-sm focus:border-black focus:outline-none">
    <input type="text" name="note" placeholder="Motif" maxlength="255" aria-label="Motif"
           class="hidden w-32 border border-gray-300 px-2 py-1 text-sm focus:border-black focus:outline-none 2xl:block">
    <button class="border border-gray-300 px-3 py-1 text-xs tracking-wider uppercase hover:border-black disabled:opacity-30" :disabled="! delta || delta == 0">OK</button>
</form>
