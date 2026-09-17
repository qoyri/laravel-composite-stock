@props([
    'silhouette',         // App\Enums\Silhouette
    'color' => '#F7F7F2', // garment hex
    'ink' => '#111111',   // marking hex
    'text' => '',
    'technique' => null,  // App\Enums\MarkingTechnique|null
])

@php
    use App\Enums\MarkingTechnique;
    use App\Enums\Silhouette;

    // The shop has no photography: the garment is drawn in its variant colour
    // with the marking printed on it, so changing colour visibly changes the product.
    $uid = 'g'.substr(md5(uniqid('', true)), 0, 8);

    $luminance = static function (string $hex): float {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    };
    $garmentLight = $luminance($color) > 0.6;
    $needsHalo = abs($luminance($color) - $luminance($ink)) < 0.25;
    $halo = $garmentLight ? '#1a1a1a' : '#ffffff';

    $shapes = [
        'tshirt' => 'M95 40 L125 28 Q150 52 175 28 L205 40 L272 82 L246 132 L214 116 L214 322 Q150 330 86 322 L86 116 L54 132 L28 82 Z',
        'long_sleeve' => 'M95 40 L125 28 Q150 52 175 28 L205 40 L246 70 L284 256 L252 264 L214 128 L214 322 Q150 330 86 322 L86 128 L48 264 L16 256 L54 70 Z',
        'tank' => 'M108 26 Q116 82 150 84 Q184 82 192 26 L214 30 Q210 96 230 128 L230 322 Q150 330 70 322 L70 128 Q90 96 86 30 Z',
        'hoodie' => 'M92 52 L122 40 Q150 62 178 40 L208 52 L248 80 L286 262 L254 270 L216 136 L216 322 Q150 330 84 322 L84 136 L46 270 L14 262 L52 80 Z',
        'sweatshirt' => 'M94 44 L124 32 Q150 54 176 32 L206 44 L248 74 L284 258 L252 266 L214 132 L214 322 Q150 330 86 322 L86 132 L48 266 L16 258 L52 74 Z',
        'bodysuit' => 'M102 40 L128 30 Q150 50 172 30 L198 40 L242 70 L222 112 L202 104 L202 236 Q202 268 178 286 L168 324 L132 324 L122 286 Q98 268 98 236 L98 104 L78 112 L58 70 Z',
        'tote_bag' => 'M58 118 L242 118 L254 330 L46 330 Z',
        'cap' => 'M52 214 Q52 88 150 88 Q248 88 248 214 Z',
    ];
    $shape = $shapes[$silhouette->value];

    // Where the marking goes, and how big.
    [$cx, $cy, $maxWidth] = match (true) {
        $technique === MarkingTechnique::Embroidery && ! in_array($silhouette, [Silhouette::Cap, Silhouette::ToteBag], true) => [184, 128, 44],
        $silhouette === Silhouette::Cap => [150, 162, 120],
        $silhouette === Silhouette::ToteBag => [150, 222, 150],
        $silhouette === Silhouette::Bodysuit => [150, 170, 90],
        $silhouette === Silhouette::Hoodie => [150, 170, 110],
        default => [150, 176, 120],
    };

    // Greedy word wrap on ~13 characters per line, at most 3 lines.
    $lines = [];
    foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
        $last = array_key_last($lines);
        if ($last !== null && mb_strlen($lines[$last].' '.$word) <= 13) {
            $lines[$last] .= ' '.$word;
        } else {
            $lines[] = $word;
        }
    }
    $lines = array_slice(array_filter($lines), 0, 3);
    $longest = max(1, ...array_map('mb_strlen', $lines ?: ['']));
    $fontSize = min(26, $maxWidth * 1.9 / $longest);
    $lineHeight = $fontSize * 1.1;
    $startY = $cy - ($lineHeight * (count($lines) - 1)) / 2;
@endphp

<svg {{ $attributes->merge(['class' => 'h-full w-full']) }} viewBox="0 0 300 350" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $silhouette->label() }} {{ $text }}">
    <defs>
        <linearGradient id="{{ $uid }}-shade" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.18"/>
            <stop offset="0.55" stop-color="#ffffff" stop-opacity="0"/>
            <stop offset="1" stop-color="#000000" stop-opacity="0.16"/>
        </linearGradient>
    </defs>

    @if ($silhouette === Silhouette::ToteBag)
        <path d="M100 120 Q100 34 150 34 Q200 34 200 120" fill="none" stroke="{{ $color }}" stroke-width="12" style="filter: brightness(0.88)"/>
    @endif
    @if ($silhouette === Silhouette::Hoodie)
        <path d="M104 58 Q104 6 150 6 Q196 6 196 58 Q176 76 150 76 Q124 76 104 58 Z" fill="{{ $color }}" style="filter: brightness(0.9)"/>
    @endif

    <path d="{{ $shape }}" fill="{{ $color }}" stroke="rgba(0,0,0,0.12)" stroke-width="1.5" stroke-linejoin="round"/>
    <path d="{{ $shape }}" fill="url(#{{ $uid }}-shade)"/>

    @switch($silhouette)
        @case(Silhouette::TShirt)
        @case(Silhouette::LongSleeve)
        @case(Silhouette::Sweatshirt)
        @case(Silhouette::Bodysuit)
            <path d="M125 30 Q150 56 175 30" fill="none" stroke="rgba(0,0,0,0.18)" stroke-width="3"/>
            @break
        @case(Silhouette::Hoodie)
            <path d="M92 250 L208 250 L220 300 L80 300 Z" fill="rgba(0,0,0,0.07)"/>
            <path d="M140 64 L138 118 M160 64 L162 118" stroke="rgba(0,0,0,0.25)" stroke-width="2.5" stroke-linecap="round"/>
            @break
        @case(Silhouette::Cap)
            <path d="M38 212 Q150 246 288 206 L292 226 Q150 276 34 234 Z" fill="{{ $color }}" style="filter: brightness(0.85)"/>
            <circle cx="150" cy="90" r="6" fill="{{ $color }}" style="filter: brightness(0.8)"/>
            <path d="M150 90 Q128 150 132 212 M150 90 Q172 150 168 212" fill="none" stroke="rgba(0,0,0,0.12)" stroke-width="1.5"/>
            @break
    @endswitch
    @if (in_array($silhouette, [Silhouette::Sweatshirt, Silhouette::Hoodie], true))
        <path d="M86 312 L214 312" stroke="rgba(0,0,0,0.12)" stroke-width="8"/>
    @endif

    @if ($lines !== [])
        <text x="{{ $cx }}" y="{{ $startY }}" text-anchor="middle" dominant-baseline="middle"
              font-family="Fredoka, var(--font-fredoka), sans-serif" font-weight="600" font-size="{{ round($fontSize, 1) }}"
              fill="{{ $ink }}"
              @if ($needsHalo) stroke="{{ $halo }}" stroke-width="{{ $technique === MarkingTechnique::Embroidery ? 0.5 : 1 }}" stroke-opacity="0.85" paint-order="stroke" @endif
              @if ($technique === MarkingTechnique::Embroidery) letter-spacing="0.3" @endif>
            @foreach ($lines as $i => $line)
                <tspan x="{{ $cx }}" dy="{{ $i === 0 ? 0 : $lineHeight }}">{{ $line }}</tspan>
            @endforeach
        </text>
    @endif
</svg>
