<?php

declare(strict_types=1);

/*
 * French messages for the validation rules this application uses.
 * Laravel ships English only; without this file APP_LOCALE=fr still
 * shows "The name field is required.".
 */
return [

    'alpha_dash' => [
        'ascii' => 'Le champ :attribute ne peut contenir que des lettres non accentuées, des chiffres, des tirets et des tirets bas.',
    ],
    'between' => [
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'decimal' => 'Le champ :attribute doit comporter au plus :decimal décimales.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'enum' => 'La valeur choisie pour :attribute n\'est pas valide.',
    'exists' => 'La valeur choisie pour :attribute n\'existe pas.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'not_in' => 'La valeur choisie pour :attribute n\'est pas valide.',
    'prohibited' => 'Le champ :attribute ne peut pas être modifié ici.',
    'regex' => 'Le format du champ :attribute n\'est pas valide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_unless' => 'Le champ :attribute est obligatoire.',
    'required_with' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être un texte.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',

    'attributes' => [
        'address_line' => 'adresse',
        'article_id' => 'article',
        'category_id' => 'catégorie',
        'city' => 'localité',
        'color_hex' => 'teinte',
        'color_name' => 'couleur',
        'delta' => 'ajustement',
        'description' => 'description',
        'email' => 'e-mail',
        'first_name' => 'prénom',
        'ink_color' => 'encre',
        'ink_hex' => 'teinte',
        'last_name' => 'nom',
        'marking_id' => 'marquage',
        'material' => 'matière',
        'name' => 'nom',
        'note' => 'motif',
        'password' => 'mot de passe',
        'phone' => 'téléphone',
        'postal_code' => 'NPA',
        'price' => 'prix',
        'product_id' => 'produit',
        'quantity' => 'quantité',
        'silhouette' => 'silhouette',
        'size' => 'taille',
        'sku' => 'SKU',
        'slug' => 'slug',
        'stock' => 'stock',
        'technique' => 'technique',
        'units_per_item' => 'unités par pièce',
        'variant_id' => 'variante',
    ],

];
