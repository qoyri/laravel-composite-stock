<?php

declare(strict_types=1);

namespace App\Stock;

/**
 * One component that cannot cover the requested quantity.
 */
final readonly class Shortage
{
    /**
     * @param  'variant'|'marking'|'product'  $component
     */
    public function __construct(
        public string $component,
        public string $label,
        public int $requested,
        public int $available,
    ) {}

    public static function unavailableProduct(string $label, int $requested): self
    {
        return new self('product', $label, $requested, 0);
    }

    public function message(): string
    {
        if ($this->component === 'product') {
            return "« {$this->label} » n'est plus proposé à la vente.";
        }

        $what = $this->component === 'variant' ? 'Article' : 'Marquage';

        return $this->available === 0
            ? "{$what} « {$this->label} » : épuisé."
            : "{$what} « {$this->label} » : {$this->requested} nécessaire(s), {$this->available} en stock.";
    }
}
