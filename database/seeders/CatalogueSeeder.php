<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarkingTechnique;
use App\Enums\Size;
use App\Models\Article;
use App\Models\ArticleVariant;
use App\Models\Category;
use App\Models\Marking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A realistic catalogue: 13 articles, ~210 variants, 12 markings, ~50 products.
 * Stock levels are pseudo-random but reproducible (fixed seed), and include
 * sold-out and low-stock variants on purpose.
 */
class CatalogueSeeder extends Seeder
{
    private const COLORS = [
        'Noir' => '#111111',
        'Blanc' => '#F7F7F2',
        'Marine' => '#1F2A44',
        'Gris chiné' => '#9CA3AF',
        'Vert forêt' => '#2A5434',
        'Bordeaux' => '#6B1F2A',
        'Moutarde' => '#D9A930',
        'Camel' => '#D4A574',
        'Sable' => '#E8E0D2',
        'Bleu nordique' => '#4A6670',
        'Rose poudré' => '#E8C4C4',
        'Crème' => '#F3EEE3',
        'Vert sauge' => '#9CAF88',
        'Bleu ciel' => '#9EC5E8',
        'Naturel' => '#EDE6D6',
        'Beige' => '#CDBBA0',
    ];

    public function run(): void
    {
        mt_srand(20260917);

        $categories = $this->categories();
        $articles = $this->articles($categories);
        $markings = $this->markings();
        $this->products($articles, $markings);

        mt_srand();
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $rows = [
            'homme' => ['Homme', 'Explorer la collection'],
            'femme' => ['Femme', 'Explorer la collection'],
            'enfant' => ['Enfant', 'Explorer la collection'],
            'accessoires' => ['Accessoires', 'Sacs, casquettes et plus'],
        ];

        $categories = [];
        $position = 0;
        foreach ($rows as $slug => [$name, $tagline]) {
            $categories[$slug] = Category::create([
                'name' => $name,
                'slug' => $slug,
                'tagline' => $tagline,
                'position' => $position++,
            ]);
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<string, Article>
     */
    private function articles(array $categories): array
    {
        $adult = Size::adult();
        $rows = [
            // key => [category, name, material, colours, sizes, description]
            'tshirt-h' => ['homme', 'T-shirt Classique', 'Coton bio 180 g/m²',
                ['Noir', 'Blanc', 'Marine', 'Gris chiné', 'Vert forêt', 'Bordeaux', 'Moutarde'], array_slice($adult, 1),
                'Coupe droite, col rond côtelé, coutures renforcées. Le basique qui encaisse les lavages.'],
            'oversize' => ['homme', 'T-shirt Oversize Unisexe', 'Coton bio 220 g/m²',
                ['Noir', 'Blanc', 'Sable', 'Bleu nordique', 'Vert sauge'], array_slice($adult, 0, 6),
                'Épaules tombantes, tissu épais, tombé ample. Se porte aussi bien en homme qu\'en femme.'],
            'hoodie-h' => ['homme', 'Hoodie', 'Molleton brossé 320 g/m²',
                ['Noir', 'Gris chiné', 'Marine', 'Camel'], array_slice($adult, 1, 5),
                'Capuche doublée, poche kangourou, intérieur gratté pour les soirées fraîches.'],
            'sweat-h' => ['homme', 'Sweat Col Rond', 'Molleton 280 g/m²',
                ['Noir', 'Sable', 'Bleu nordique'], array_slice($adult, 1, 5),
                'Le sweat sans capuche, bords-côtes aux poignets et à la taille.'],
            'ml-h' => ['homme', 'T-shirt Manches Longues', 'Coton bio 190 g/m²',
                ['Blanc', 'Noir', 'Marine'], array_slice($adult, 1, 4),
                'Manches longues avec poignets côtelés, idéal en mi-saison.'],
            'tshirt-f' => ['femme', 'T-shirt Femme Coupe Droite', 'Coton bio 160 g/m²',
                ['Blanc', 'Noir', 'Rose poudré', 'Sable', 'Bleu nordique', 'Bordeaux'], array_slice($adult, 0, 5),
                'Coupe droite légèrement cintrée, col rond fin.'],
            'hoodie-f' => ['femme', 'Hoodie Femme', 'Molleton brossé 300 g/m²',
                ['Noir', 'Crème', 'Vert sauge'], array_slice($adult, 0, 5),
                'Coupe ajustée, capuche doublée, cordons plats.'],
            'debardeur' => ['femme', 'Débardeur', 'Coton bio 150 g/m²',
                ['Blanc', 'Noir'], array_slice($adult, 0, 4),
                'Débardeur à bretelles larges, dos nageur.'],
            'tshirt-e' => ['enfant', 'T-shirt Enfant', 'Coton bio 155 g/m²',
                ['Blanc', 'Moutarde', 'Bleu ciel'], Size::kids(),
                'Coupe confortable, étiquette imprimée pour ne pas gratter.'],
            'sweat-e' => ['enfant', 'Sweat Enfant', 'Molleton 260 g/m²',
                ['Marine', 'Gris chiné'], array_slice(Size::kids(), 1),
                'Sweat col rond pour les récrés d\'hiver.'],
            'body' => ['enfant', 'Body Bébé', 'Coton bio 200 g/m²',
                ['Blanc', 'Crème'], Size::baby(),
                'Pressions à l\'entrejambe, encolure américaine.'],
            'tote' => ['accessoires', 'Tote Bag', 'Toile de coton 340 g/m²',
                ['Naturel', 'Noir'], [Size::OneSize],
                'Anses longues, fond renforcé, porte un pack de six sans broncher.'],
            'casquette' => ['accessoires', 'Casquette', 'Sergé de coton',
                ['Noir', 'Marine', 'Beige'], [Size::OneSize],
                'Six panneaux, visière courbée, fermeture réglable en métal.'],
        ];

        $articles = [];
        foreach ($rows as $key => [$category, $name, $material, $colors, $sizes, $description]) {
            $article = Article::create([
                'category_id' => $categories[$category]->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'material' => $material,
            ]);

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    ArticleVariant::create([
                        'article_id' => $article->id,
                        'color_name' => $color,
                        'color_hex' => self::COLORS[$color],
                        'size' => $size,
                        'sku' => Str::upper(str_replace('-', '', $key).'-'.Str::slug($color, '').'-'.$size->value),
                        'stock' => $this->randomStock(),
                    ]);
                }
            }

            $articles[$key] = $article;
        }

        return $articles;
    }

    /** Mostly healthy stock, with ~8 % sold out and ~12 % running low. */
    private function randomStock(): int
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 8 => 0,
            $roll <= 20 => mt_rand(1, 3),
            default => mt_rand(6, 45),
        };
    }

    /** @return array<string, Marking> */
    private function markings(): array
    {
        $rows = [
            // key => [name, technique, ink, hex, stock (null = unlimited)]
            'chat' => ['J\'habite chez mon chat', MarkingTechnique::ScreenPrinting, 'Blanc', '#FFFFFF', 60],
            'raclette' => ['Je peux pas, j\'ai raclette', MarkingTechnique::ScreenPrinting, 'Noir', '#111111', 45],
            'differences' => ['7 différences', MarkingTechnique::Flocking, 'Or', '#C9A227', null],
            'fondue' => ['Fondue un jour, fondue toujours', MarkingTechnique::ScreenPrinting, 'Crème', '#F3EEE3', 30],
            'suisse' => ['Pas de panique, je suis Suisse', MarkingTechnique::Embroidery, 'Rouge', '#D52B1E', 25],
            'cafe' => ['Café d\'abord', MarkingTechnique::Flocking, 'Noir', '#111111', null],
            'sieste' => ['Team sieste', MarkingTechnique::Embroidery, 'Blanc', '#FFFFFF', 12],
            'lundi' => ['Le lundi, c\'est non', MarkingTechnique::ScreenPrinting, 'Blanc', '#FFFFFF', 3],
            'logo' => ['Logo Archie brodé', MarkingTechnique::Embroidery, 'Noir', '#111111', 80],
            'mini' => ['Mini Archie', MarkingTechnique::Flocking, 'Blanc', '#FFFFFF', null],
            'ours' => ['Ours des montagnes', MarkingTechnique::ScreenPrinting, 'Vert forêt', '#2A5434', 0],
            'grand' => ['Archie grand format (dos)', MarkingTechnique::ScreenPrinting, 'Noir', '#111111', 18],
        ];

        $markings = [];
        foreach ($rows as $key => [$name, $technique, $ink, $hex, $stock]) {
            $markings[$key] = Marking::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'technique' => $technique,
                'ink_color' => $ink,
                'ink_hex' => $hex,
                'is_unlimited' => $stock === null,
                'stock' => $stock ?? 0,
            ]);
        }

        return $markings;
    }

    /**
     * @param  array<string, Article>  $articles
     * @param  array<string, Marking>  $markings
     */
    private function products(array $articles, array $markings): void
    {
        // Base price of each article (cents) before the marking surcharge.
        $base = [
            'tshirt-h' => 2900, 'oversize' => 3400, 'hoodie-h' => 5900, 'sweat-h' => 4900,
            'ml-h' => 3400, 'tshirt-f' => 2900, 'hoodie-f' => 5900, 'debardeur' => 2500,
            'tshirt-e' => 2200, 'sweat-e' => 3600, 'body' => 2400, 'tote' => 1900, 'casquette' => 2800,
        ];
        $surcharge = [
            MarkingTechnique::ScreenPrinting->value => 0,
            MarkingTechnique::Flocking->value => 300,
            MarkingTechnique::Embroidery->value => 800,
        ];

        // Which marking is offered on which article. One marking, many articles —
        // and one article, many markings: that is the whole point.
        $offers = [
            'chat' => ['tshirt-h', 'oversize', 'hoodie-h', 'tshirt-f', 'hoodie-f', 'tshirt-e', 'tote'],
            'raclette' => ['tshirt-h', 'oversize', 'sweat-h', 'tshirt-f', 'debardeur', 'tote'],
            'differences' => ['tshirt-h', 'ml-h', 'tshirt-f', 'tshirt-e'],
            'fondue' => ['tshirt-h', 'hoodie-h', 'sweat-h', 'hoodie-f'],
            'suisse' => ['tshirt-h', 'oversize', 'hoodie-h', 'casquette'],
            'cafe' => ['tshirt-h', 'ml-h', 'tshirt-f', 'debardeur', 'tote'],
            'sieste' => ['sweat-h', 'hoodie-f', 'body', 'casquette'],
            'lundi' => ['tshirt-h', 'oversize', 'tshirt-f'],
            'logo' => ['tshirt-h', 'hoodie-h', 'sweat-h', 'hoodie-f', 'casquette'],
            'mini' => ['tshirt-e', 'sweat-e', 'body'],
            'ours' => ['tshirt-h', 'sweat-e'],
            'grand' => ['oversize', 'hoodie-h'],
        ];

        foreach ($offers as $markingKey => $articleKeys) {
            $marking = $markings[$markingKey];
            foreach ($articleKeys as $articleKey) {
                $article = $articles[$articleKey];
                $article->markings()->attach($marking->id, [
                    'slug' => Str::slug($marking->name.' '.$article->name),
                    'price_cents' => $base[$articleKey] + $surcharge[$marking->technique->value] + ($markingKey === 'grand' ? 600 : 0),
                    // The back print uses two transfers per garment.
                    'units_per_item' => $markingKey === 'grand' ? 2 : 1,
                ]);
            }
        }
    }
}
