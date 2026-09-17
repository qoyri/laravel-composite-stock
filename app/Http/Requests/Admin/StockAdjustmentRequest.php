<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ArticleVariant;
use App\Models\Marking;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A signed delta ("+12 received", "-2 damaged"), never an absolute value.
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stockable = $this->stockable();

        return $this->user()?->can('adjustStock', $stockable) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delta' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['delta.not_in' => 'Indiquez une quantité à ajouter (+) ou à retirer (−).'];
    }

    public function stockable(): ArticleVariant|Marking
    {
        $stockable = $this->route('variant') ?? $this->route('marking');

        if (! $stockable instanceof ArticleVariant && ! $stockable instanceof Marking) {
            abort(404);
        }

        return $stockable;
    }
}
