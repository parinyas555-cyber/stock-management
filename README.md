Stock Management V11

New in V11:
- Product active/inactive status (soft delete)
- Products with movement history cannot be permanently deleted; they are automatically deactivated instead
- Admin can activate/deactivate products
- Stock IN/OUT only allow active products
- Existing PostgreSQL data is migrated automatically with ALTER TABLE IF NOT EXISTS
