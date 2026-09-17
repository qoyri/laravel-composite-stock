<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Article;
use App\Models\Marking;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Prices are typed in francs ("34.90") and stored in cents.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()?->can('update', $product) ?? false
            : $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = ! $this->route('product') instanceof Product;

        return [
            'article_id' => $creating ? ['required', 'integer', Rule::exists('articles', 'id')] : ['prohibited'],
            'marking_id' => $creating ? ['required', 'integer', Rule::exists('markings', 'id'),
                Rule::unique('products', 'marking_id')->where('article_id', $this->integer('article_id'))] : ['prohibited'],
            'price' => ['required', 'decimal:0,2', 'min:1', 'max:10000'],
            'units_per_item' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['marking_id.unique' => 'Ce marquage est déjà proposé sur cet article.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = [
            'price_cents' => (int) round($this->float('price') * 100),
            'units_per_item' => $this->integer('units_per_item'),
            'is_active' => $this->boolean('is_active'),
        ];

        if (! $this->route('product') instanceof Product) {
            $article = Article::findOrFail($this->integer('article_id'));
            $marking = Marking::findOrFail($this->integer('marking_id'));
            $data += [
                'article_id' => $article->id,
                'marking_id' => $marking->id,
                'slug' => Str::slug($marking->name.' '.$article->name),
            ];
        }

        return $data;
    }
}
