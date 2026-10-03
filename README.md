Stock Management V12

New in V12:
- Admin-only Factory Reset for starting a new warehouse cycle
- Reset keeps product master data, product active/inactive status, users, and database structure
- Reset clears stock movement history and sets every product current_stock to 0
- Movement sequence is reset to start from 1
- Two-step confirmation: type RESET, then confirm browser dialog
- CSV backup downloads for products and stock movements before reset
- Warehouse reset audit log records the admin, timestamp, counts, and stock total before each reset
- Existing PostgreSQL data is migrated automatically at startup

Default admin on a fresh database:
- Username: admin
- Password: Admin@123

Deployment:
- Push the project files to GitHub and deploy on Render
- DATABASE_URL should be provided by the Render PostgreSQL database
