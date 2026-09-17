<x-mail::message>
# Merci {{ $order->first_name }} !

Votre commande **{{ $order->reference }}** est confirmée.

<x-mail::table>
| Article | Qté | Total |
|:--------|:---:|------:|
@foreach ($order->lines as $line)
| {{ $line->product_name }}<br>{{ $line->variant_label }} | {{ $line->quantity }} | {{ chf($line->line_total_cents) }} |
@endforeach
| Livraison | | {{ $order->shipping_cents === 0 ? 'Offerte' : chf($order->shipping_cents) }} |
| **Total** | | **{{ chf($order->total_cents) }}** |
</x-mail::table>

Livraison à :
{{ $order->customerName() }}, {{ $order->address_line }}, {{ $order->postal_code }} {{ $order->city }}

À très vite,<br>
L'Atelier de l'Humour
</x-mail::message>
