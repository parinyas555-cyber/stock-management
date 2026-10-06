# Stock Management V13

V13 builds on V12 and adds a Factory Reset entry directly in the **Reports Summary** page for administrators.

## Factory Reset
- Admin-only access.
- The Reports Summary page now has a **คืนค่าโรงงาน** button.
- It opens the existing safe Factory Reset page.
- Reset clears stock movement history and resets current stock to 0 while preserving product master data, active/inactive status, users, and database structure.
- The reset page requires typing `RESET` and confirmation.
- CSV backup and reset audit log remain available from the reset page.


## V15
- Products page shows 25 products per page.
- Previous / Next pagination preserves search and status filters.
- Displays current range and total product count.
