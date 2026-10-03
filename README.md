# Stock Management V13

V13 builds on V12 and adds a Factory Reset entry directly in the **Reports Summary** page for administrators.

## Factory Reset
- Admin-only access.
- The Reports Summary page now has a **คืนค่าโรงงาน** button.
- It opens the existing safe Factory Reset page.
- Reset clears stock movement history and resets current stock to 0 while preserving product master data, active/inactive status, users, and database structure.
- The reset page requires typing `RESET` and confirmation.
- CSV backup and reset audit log remain available from the reset page.


## V14
- เพิ่มการลบสินค้าแบบถาวรสำหรับ Admin เท่านั้น
- เมื่อลบสินค้า ระบบจะลบประวัติรับเข้า/เบิกออกของสินค้านั้นก่อน แล้วจึงลบสินค้า
- ใช้ Database Transaction เพื่อป้องกันข้อมูลค้างหากเกิดข้อผิดพลาด
- Staff ไม่มีสิทธิ์เข้าถึงหรือเรียกคำสั่งลบสินค้า
