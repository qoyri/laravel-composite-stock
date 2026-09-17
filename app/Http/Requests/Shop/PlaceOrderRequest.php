<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use App\Orders\CustomerDetails;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:strict', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +().-]+$/'],
            'address_line' => ['required', 'string', 'max:255'],
            // Swiss postcodes: four digits, 1000–9658.
            'postal_code' => ['required', 'string', 'regex:/^[1-9][0-9]{3}$/'],
            'city' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Le NPA doit comporter quatre chiffres.',
        ];
    }

    public function customer(): CustomerDetails
    {
        return new CustomerDetails(
            email: $this->string('email')->lower()->toString(),
            firstName: $this->string('first_name')->trim()->toString(),
            lastName: $this->string('last_name')->trim()->toString(),
            phone: $this->filled('phone') ? $this->string('phone')->trim()->toString() : null,
            addressLine: $this->string('address_line')->trim()->toString(),
            postalCode: $this->string('postal_code')->toString(),
            city: $this->string('city')->trim()->toString(),
        );
    }
}
