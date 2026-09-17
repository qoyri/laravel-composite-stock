<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Enums\MarkingTechnique;

final readonly class CatalogFilters
{
    public function __construct(
        public ?string $category = null,
        public ?string $marking = null,
        public ?MarkingTechnique $technique = null,
        public ?string $search = null,
        public bool $availableOnly = false,
        public CatalogSort $sort = CatalogSort::Newest,
    ) {}

    public function isFiltered(): bool
    {
        return $this->category !== null || $this->marking !== null || $this->technique !== null
            || $this->search !== null || $this->availableOnly;
    }
}
