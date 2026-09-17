# Plan d'implémentation — laravel-composite-stock

Stack : Laravel 13, PHP ≥ 8.4, PostgreSQL 18 (Docker), Pest 5, Larastan 3,
Blade + Tailwind 4 + Alpine.js. Pas de Livewire.

Règle de coupe : le back-office et le front peuvent être réduits ; le stock et
ses tests jamais.

## Jour 1 — socle et cœur du stock

### 1. Outillage
- [x] Squelette Laravel, Pest, Larastan
- [x] `compose.yaml` : PostgreSQL (base dev + base de test) et Mailpit
- [x] `phpstan.neon` (niveau max visé), `pint.json`
- [x] `.github/workflows/ci.yml` : jobs Pint, PHPStan, Pest (service Postgres), bloquants

### 2. Schéma (migrations)
- [x] `users.role` (enum `admin|staff`)
- [x] `categories`
- [x] `articles` (FK category restrict)
- [x] `article_variants` — unique(article_id, color_name, size), sku unique,
      CHECK stock >= 0, unique(id, article_id)
- [x] `markings` — CHECK stock >= 0
- [x] `products` (pivot) — unique(article_id, marking_id), CHECK price_cents > 0,
      CHECK units_per_item >= 1, unique(id, article_id)
- [x] `orders` — CHECK total = subtotal + shipping, CHECK status
- [x] `order_lines` — FK composites (product_id, article_id),
      (product_id, marking_id) et (article_variant_id, article_id),
      CHECK quantity > 0, CHECK line_total = unit_price × quantity
- [x] `stock_movements` — morph, CHECK delta <> 0

### 3. Modèles, enums, factories
- [x] Enums : `MarkingTechnique`, `Size`, `OrderStatus`, `UserRole`, `StockMovementReason`
- [x] Modèles + relations (dont `Article::markings()` via pivot `Product`)
- [x] Scopes : `Product::sellable()`, `Article::active()`, `Article::inCategory()`,
      `ArticleVariant::inStock()`, `Order::withStatus()`, `Order::latestFirst()`
- [x] `AppServiceProvider` : `Model::shouldBeStrict()` hors prod, morph map imposée
- [x] Factories pour chaque modèle

### 4. Cœur du stock (TDD)
- [x] `Stock\AvailabilityCalculator` + tests unitaires
- [x] `Stock\StockDemand` (agrégation par composant)
- [x] `Stock\InsufficientStock` (liste des composants manquants)
- [x] `Actions\Orders\PlaceOrder` + tests de feature
- [x] `Actions\Orders\CancelOrder` + tests (restock, idempotence)
- [x] Ligne de commande : `marking_id` + unités réellement consommées figés à la vente (+ 3e FK composite)
- [x] `Actions\Stock\AdjustStock` + tests
- [x] Tests d'intégrité base (stock négatif, FK composite)
- [x] Test de verrouillage déterministe (2 connexions, `lock_timeout`)
- [x] Test de course (`pcntl_fork`) — conservé : 30/30 exécutions vertes, échoue si on retire les verrous

### 5. Seeders
- [x] Catégories, articles, variantes (> 200), marquages, produits
- [x] Comptes admin/staff
- [x] Commandes historiques créées via `PlaceOrder`

## Jour 2 — surfaces

### 6. Vitrine (Blade + Alpine, design repris de la v1)
- [x] Layout : en-tête (sans sélecteur de devise), pied de page, tiroir panier
- [x] Accueil, catalogue, fiche produit (matrice de disponibilité), panier, checkout,
      confirmation (URL signée)
- [x] `Cart` en session, Form Requests, contrôleurs minces
- [x] Composant `<x-garment-preview>` (SVG)
- [x] Livraison 5.– CHF, gratuite dès 50.– CHF

### 7. Job
- [x] `SendOrderConfirmation` (queue database, `afterCommit`) + mailable Markdown (fait au jour 1, dispatché par PlaceOrder)
- [x] Tests : dispatch sur succès uniquement, envoi du mail

### 8. Back-office
- [x] Login (rate limit), middleware `auth`, policies
- [x] Tableau de bord, articles + variantes, marquages, produits, ajustement de stock,
      commandes (liste, détail, annulation)
- [x] Tests d'autorisation (invité, staff, admin)
- [x] Test N+1 (nombre de requêtes constant sur le catalogue et la liste des commandes)

### 9. README
- [x] Constat v1 (sources : `docs/v1-notes.md`)
- [x] Problème du stock croisé, disponibilité calculée
- [x] FK composite mise en avant
- [x] Verrouillage pessimiste : pourquoi, alternatives écartées
- [x] Stratégie de test de concurrence
- [x] Écueils rencontrés, installation, comptes de démo

### Ajouts du jour 2
- [x] Colonne `articles.silhouette` (forme de l'aperçu SVG)
- [x] Scope `Product::available()` (jumeau SQL du calcul) + test de concordance
- [x] Contrôle par mutation de chaque verrou ; sondes corrigées pour `CancelOrder` et `AdjustStock`
