@props(['status'])

<span @class([
    'inline-block border px-3 py-1 text-xs tracking-wider uppercase',
    'border-green-200 bg-green-50 text-green-700' => $status === \App\Enums\OrderStatus::Confirmed,
    'border-gray-200 bg-gray-50 text-gray-500' => $status === \App\Enums\OrderStatus::Cancelled,
])>{{ $status->label() }}</span>
