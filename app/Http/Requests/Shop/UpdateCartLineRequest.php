<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartLineRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 0 removes the line.
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config()->integer('shop.cart.max_quantity_per_line')],
        ];
    }
}
