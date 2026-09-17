<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Silhouette;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create and update share the rules; authorisation depends on the route.
 */
class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            ? $this->user()?->can('update', $article) ?? false
            : $this->user()?->can('create', Article::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->string('name')->toString())]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            // Derived from the name when left empty: no separate error when the name is missing.
            'slug' => ['required_with:name', 'nullable', 'string', 'max:140', 'alpha_dash:ascii',
                Rule::unique('articles', 'slug')->ignore($article instanceof Article ? $article->id : null)],
            'description' => ['required', 'string', 'max:2000'],
            'material' => ['nullable', 'string', 'max:120'],
            'silhouette' => ['required', Rule::enum(Silhouette::class)],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        return [
            'category_id' => $this->integer('category_id'),
            'name' => $this->string('name')->trim()->toString(),
            'slug' => $this->string('slug')->toString(),
            'description' => $this->string('description')->trim()->toString(),
            'material' => $this->filled('material') ? $this->string('material')->trim()->toString() : null,
            'silhouette' => $this->enum('silhouette', Silhouette::class),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
