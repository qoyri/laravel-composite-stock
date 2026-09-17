# Notes de vérification — stock de la v1 (.NET)

Chaque constat du README a été relu dans le code avant d'être écrit.
Référence : `/qoy/code/WebstormProjects/archiecool/`, commit `c22eaa5`
**plus les modifications non commitées de l'arbre de travail** au 2026-09-17.
Les numéros de ligne sont ceux de l'arbre de travail ; chemins relatifs à
`api/archiecool_api/` sauf mention contraire.

## 1. La disponibilité est stockée et maintenue par triggers

- `ProductImage.CalculatedStock` — `Models/ProductImage.cs:56`
- `Product.TotalStock` — `Models/Product.cs:32`
- Fonction `calculate_product_image_stock` — `Scripts/Step2_Fix_CalculatedStock.sql:3-25`,
  identique dans le dump `docker/init/postgres/01-seed.sql.gz` (ligne 60 du SQL décompressé).
  - Somme de `InventoryItemStocks."QuantityAvailable"` **toutes tailles confondues**
    du textile de base (`Step2_Fix…sql:9-13`).
  - Si cette somme vaut 0, repli sur `InventoryItems."QuantityAvailable"` (`:16-21`).
  - N'utilise ni `QuantityReserved`, ni le stock du marquage (`OverlayVariants`).
- Triggers présents dans le dump (lignes ~1985-2030 du SQL décompressé) :
  - `tr_product_image_calculate_stock` : `BEFORE INSERT OR UPDATE OF "BaseProductId" ON "ProductImages"`
  - `tr_inventory_update_stock` : `AFTER UPDATE OF "QuantityAvailable" ON "InventoryItems"`
  - `tr_product_stats_from_images` : recalcule `Products."TotalStock"`
  - **Aucun trigger sur `InventoryItemStocks`** : modifier le stock d'une taille ne
    rafraîchit pas `CalculatedStock` tant que `InventoryItems."QuantityAvailable"`
    ou `ProductImages."BaseProductId"` ne changent pas.

## 2. La commande réserve un seul composant, au niveau global

`Services/OrderService.cs`
- `CreateOrderAsync` : `:46`, transaction ouverte `:48`.
- Contrôle `item.Quantity > variant.CalculatedStock` → exception (`:97-99`) —
  basé sur la valeur agrégée toutes tailles du §1.
- Réservation uniquement si `variant.BaseProductId.HasValue` (`:103`), sur
  l'`InventoryItem` du textile (`:105`). TODO explicite `:102` :
  « switch to per-variant InventoryItemStock reservation… ».
- `OrderItem` ne persiste que `ProductId` (`:112-120`, `Models/OrderItem.cs:22`) ;
  l'identifiant de variante sert seulement de `referenceId` à la réservation.

`Services/InventoryService.cs`
- `ReserveStockAsync` `:517` : `UPDATE … SET "QuantityReserved" = "QuantityReserved" + q
  WHERE … ("QuantityAvailable" - "QuantityReserved") >= q` (`:524-530`) —
  **atomique et correct pour ce composant**.
- Journal écrit ensuite avec `PreviousQuantity = 0`, `NewQuantity = 0` (`:537-544`).
- `QuantityAvailable` n'est jamais décrémenté par une commande ; le type
  `ORDER_FULFILLMENT` est déclaré (`Models/StockTransaction.cs:83`) mais utilisé nulle part.

## 3. Le stock par taille et le marquage ne sont pas décomptés

- Aucune référence à `OverlayVariant` ni `InventoryItemStock` dans `Services/OrderService.cs`
  (grep vide).
- `InventoryItemStock.QuantityReserved` (`Models/InventoryItemStock.cs:38`) n'est écrit
  que par la mise à jour admin (`Services/InventoryItemStockService.cs:174-175`).
- `OverlayVariant.QuantityReserved` (`Models/OverlayVariant.cs:29`) n'est écrit qu'à la
  création, à 0 (`Services/OverlayVariantService.cs:83`, `:212`).

## 4. Les annulations ne libèrent pas la réservation

- `CancelOrderAsync` `Services/OrderService.cs:503-518` : change le statut, rien d'autre.
- Paiement échoué :
  - `Controllers/Client/ClientOrderController.cs:460-466` → statut `Cancelled`
  - `Services/PaymentStatusPollingService.cs:146-155` → `PaymentStatus.Failed`
  - `Controllers/WebhookController.cs:173-181` → `PaymentStatus.Failed`
  - aucun n'appelle `ReleaseStockReservationAsync`.
- Seul appelant de `ReleaseStockReservationAsync` (`Services/InventoryService.cs:560`) :
  l'endpoint admin `Controllers/InventoryController.cs:483-487`.

## 5. Mises à jour manuelles : lecture puis écriture sans verrou

- `InventoryItemStockService.UpdateStockAsync` `:142` : chargement `:144-147`,
  affectation de la valeur absolue `:156`, `SaveChangesAsync` — ni verrou ni jeton de concurrence.
- `OverlayVariantService.UpdateVariantAsync` `:115` : même schéma (`:136`).
- Aucun `RowVersion`, `[ConcurrencyCheck]`, `IsConcurrencyToken` ni `xmin` dans le code (grep vide).
- À l'inverse, `InventoryService.UpdateStockAsync` `:468` est protégé :
  transaction `Serializable` (`:473`) + `SELECT … FOR UPDATE` (`:478`).
