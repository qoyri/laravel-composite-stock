<?php

declare(strict_types=1);

use App\Models\User;

it('shows validation errors in French', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), ['name' => '', 'description' => ''])
        ->assertSessionHasErrors([
            'name' => 'Le champ nom est obligatoire.',
            'description' => 'Le champ description est obligatoire.',
        ])
        // The slug is derived from the name: no second error for the same mistake.
        ->assertSessionDoesntHaveErrors('slug');
});

it('summarises a refused stock adjustment at the top of the page', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$product, $variant] = sellable(variantStock: 2);
    $page = route('admin.articles.edit', $product->article);

    $this->from($page)->post(route('admin.stock.variants.adjust', $variant), ['delta' => -5])
        ->assertRedirect($page);

    $html = $this->get($page)->assertOk()->getContent();
    $banner = strpos($html, "Rien n'a été enregistré");
    expect($banner)->not->toBeFalse()
        ->and($banner)->toBeLessThan(strpos($html, 'Fiche article'))
        ->and($html)->toContain('Stock actuel 2 : impossible de retirer 5.');
});
