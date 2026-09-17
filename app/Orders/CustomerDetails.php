<?php

declare(strict_types=1);

namespace App\Orders;

final readonly class CustomerDetails
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $phone,
        public string $addressLine,
        public string $postalCode,
        public string $city,
        public string $country = 'CH',
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toOrderAttributes(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'address_line' => $this->addressLine,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }
}
