# HOPE ERP V4

V4 adds multi-warehouse inventory operations, purchase orders and receptions, customer delivery notes, inventory counts, automatic document numbering, ERP reports with printable PDF, document email with PDF attachment, and hardened role/permission handling.

Core workflow: Purchase -> Reception -> Stock; Customer -> Sales Order -> Delivery -> Invoice -> Payment.

Taxes remain disabled/zero by business requirement. No debt module.

## Validation
Use Docker: `docker compose exec app php artisan migrate:fresh --seed`, then `docker compose exec app php artisan route:list --path=dashboard`.
