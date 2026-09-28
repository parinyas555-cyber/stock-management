# Stock Management V6

ระบบจัดการสต๊อกสินค้าสำหรับใช้งานผ่าน Web Browser บน Render + PostgreSQL

## V6 แก้ไขอะไร

V5 มีปัญหาเมื่อเปิด Dashboard:

`Failed opening required '/var/www/src/partials.php'`

สาเหตุคือ `partials.php` อยู่ใน `public/` แต่หน้าเว็บเรียกจาก `/var/www/src/partials.php`

V6 แก้โดย:

- ย้าย `partials.php` ไปไว้ใน `src/`
- ใช้ `__DIR__` สำหรับ include/require เพื่อไม่ผูกกับ path แบบ hard-code
- ตรวจสอบไฟล์ `bootstrap.php`, `partials.php`, `schema.sql` และ Dashboard ระหว่าง Docker build
- คงการเชื่อมต่อ PostgreSQL ผ่าน `DATABASE_URL` จาก Render Blueprint
- คง Render PostgreSQL database ชื่อ `stock-management-db`

## Deploy ด้วย Render Blueprint

1. Push โฟลเดอร์ V6 ขึ้น GitHub
2. ใน Render เลือก **New > Blueprint**
3. เลือก Repository ที่มี `render.yaml`
4. Deploy Blueprint
5. Render จะสร้าง Web Service `stock-management` และ PostgreSQL `stock-management-db` ตาม `render.yaml`
6. รอ Build/Deploy ให้เสร็จ
7. เปิด URL ของ Web Service

## Login เริ่มต้น

- Username: `admin`
- Password: `Admin@123`

แนะนำให้เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ

## โครงสร้างสำคัญ

```text
public/              -> ไฟล์เว็บที่ Apache ให้บริการ
src/bootstrap.php    -> Database / session / authentication
src/partials.php     -> Layout / navigation / shared functions
sql/schema.sql       -> PostgreSQL schema
Dockerfile           -> PHP Apache + PostgreSQL extensions
render.yaml          -> Render Web Service + PostgreSQL
```
