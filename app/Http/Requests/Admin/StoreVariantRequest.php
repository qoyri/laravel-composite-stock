<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Size;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && ($this->user()?->can('update', $article) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $article = $this->route('article');
        $articleId = $article instanceof Article ? $article->id : null;

        return [
            'color_name' => ['required', 'string', 'max:40'],
            'color_hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'size' => ['required', Rule::enum(Size::class),
                Rule::unique('article_variants', 'size')
                    ->where('article_id', $articleId)
                    ->where('color_name', $this->string('color_name')->toString())],
            'sku' => ['required', 'string', 'max:40', Rule::unique('article_variants', 'sku')],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['size.unique' => 'Cette couleur existe déjà dans cette taille.'];
    }
}
