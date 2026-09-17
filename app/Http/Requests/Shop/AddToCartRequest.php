<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use App\Cart\Cart;
use App\Cart\CartLine;
use App\Models\ArticleVariant;
use App\Models\Product;
use App\Stock\AvailabilityCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The availability check here is a courtesy to the customer, not a guarantee:
 * stock can still change before checkout, where PlaceOrder decides under lock.
 */
class AddToCartRequest extends FormRequest
{
    private ?Product $product = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'variant_id' => ['required', 'integer', Rule::exists('article_variants', 'id')->where('article_id', $this->product()?->article_id)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config()->integer('shop.cart.max_quantity_per_line')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'Ce produit n\'est plus proposé.',
            'variant_id.required' => 'Choisissez une couleur et une taille.',
            'variant_id.exists' => 'Cette couleur ou cette taille n\'existe pas pour ce produit.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(Cart $cart, AvailabilityCalculator $availability): array
    {
        return [
            function (Validator $validator) use ($cart, $availability) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $product = $this->product();
                $variant = ArticleVariant::find($this->integer('variant_id'));
                if ($product === null || $variant === null) {
                    return;
                }

                $wanted = $this->integer('quantity') + $cart->quantityOf($product->id, $variant->id);
                $available = $availability->quantity($product, $variant);

                if ($wanted > $available) {
                    $validator->errors()->add('quantity', $available === 0
                        ? 'Cette taille est épuisée.'
                        : "Plus que {$available} disponible(s) dans cette taille.");
                }
            },
        ];
    }

    public function line(): CartLine
    {
        return new CartLine($this->integer('product_id'), $this->integer('variant_id'), $this->integer('quantity'));
    }

    private function product(): ?Product
    {
        return $this->product ??= Product::with('marking')->find($this->integer('product_id'));
    }
}
