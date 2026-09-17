<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MarkingTechnique;
use App\Models\Marking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The stock itself is not editable here: it only moves through
 * AdjustStock, orders and cancellations, so every change is logged.
 * It can be set once, when the marking is created.
 */
class MarkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $marking = $this->route('marking');

        return $marking instanceof Marking
            ? $this->user()?->can('update', $marking) ?? false
            : $this->user()?->can('create', Marking::class) ?? false;
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
        $marking = $this->route('marking');
        $creating = ! $marking instanceof Marking;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash:ascii',
                Rule::unique('markings', 'slug')->ignore($creating ? null : $marking->id)],
            'technique' => ['required', Rule::enum(MarkingTechnique::class)],
            'ink_color' => ['required', 'string', 'max:40'],
            'ink_hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_unlimited' => ['boolean'],
            'is_active' => ['boolean'],
            'stock' => $creating ? ['required_unless:is_unlimited,1', 'nullable', 'integer', 'min:0', 'max:100000'] : ['prohibited'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $data = [
            'name' => $this->string('name')->trim()->toString(),
            'slug' => $this->string('slug')->toString(),
            'technique' => $this->enum('technique', MarkingTechnique::class),
            'ink_color' => $this->string('ink_color')->trim()->toString(),
            'ink_hex' => $this->string('ink_hex')->upper()->toString(),
            'is_unlimited' => $this->boolean('is_unlimited'),
            'is_active' => $this->boolean('is_active'),
        ];

        if (! $this->route('marking') instanceof Marking) {
            $data['stock'] = $this->boolean('is_unlimited') ? 0 : $this->integer('stock');
        }

        return $data;
    }
}
