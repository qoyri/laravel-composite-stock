<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use App\Catalog\CatalogFilters;
use App\Catalog\CatalogSort;
use App\Enums\MarkingTechnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categorie' => ['nullable', 'string', 'max:64'],
            'marquage' => ['nullable', 'string', 'max:128'],
            'technique' => ['nullable', Rule::enum(MarkingTechnique::class)],
            'q' => ['nullable', 'string', 'max:64'],
            'disponible' => ['nullable', 'boolean'],
            'tri' => ['nullable', Rule::enum(CatalogSort::class)],
        ];
    }

    public function filters(): CatalogFilters
    {
        $search = trim($this->string('q')->toString());

        return new CatalogFilters(
            category: $this->filled('categorie') ? $this->string('categorie')->toString() : null,
            marking: $this->filled('marquage') ? $this->string('marquage')->toString() : null,
            technique: $this->enum('technique', MarkingTechnique::class),
            search: $search === '' ? null : $search,
            availableOnly: $this->boolean('disponible'),
            sort: $this->enum('tri', CatalogSort::class) ?? CatalogSort::Newest,
        );
    }
}
