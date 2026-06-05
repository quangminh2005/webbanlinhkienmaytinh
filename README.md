# Web ban linh kien may tinh (MVP)

Du an PHP MVC toi gian cho website ban linh kien may tinh voi cac tinh nang:
- Danh sach san pham + loc theo danh muc
- Chi tiet san pham
- Gio hang session
- Dang ky / Dang nhap
- Trang Build PC co canh bao tuong thich co ban
- Admin quan ly san pham co ban

## 1. Yeu cau
- PHP 8.1+
- MySQL 8+
- Apache (XAMPP)

## 2. Cai dat
1. Tao database `pc_shop`.
2. Chay file SQL: `database/schema.sql`.
3. (Tuy chon) Bo sung nhieu linh kien mau: `database/seed_more_products.sql`.
4. Sua ket noi DB trong `config/database.php` neu can.
5. Dat thu muc project vao `htdocs`.
6. Truy cap: `http://localhost/Webbanlinhkienmaytinh/public`

## 3. Tai khoan mac dinh
- Admin:
  - email: `admin@shop.local`
  - password: `admin123`
  - Luu y: tai khoan mau dung plain password trong seed SQL, dang nhap lan dau nen doi sang co che hash cho production.

## 4. Cau truc
- `public/index.php`: front controller
- `app/controllers`: xu ly request
- `app/models`: truy cap du lieu
- `app/views`: giao dien
- `app/core`: router + base classes

## 5. Import nhieu san pham bang CSV
1. Dang nhap admin: `admin@shop.local / admin123`
2. Vao `Admin` > `Import san pham tu CSV`
3. Upload file mau: `database/products_import_template.csv` (hoac file CSV cua ban)
4. Cot bat buoc trong CSV:
   - `name`
   - `category_slug`
   - `price`
   - `stock_quantity`
5. Cot tuy chon:
   - `category_name`, `description`, `image_url`, `socket`, `ram_type`, `vram_gb`, `wattage`

He thong se tu tao danh muc neu `category_slug` chua ton tai.

## 6. Upload anh san pham
- Trong trang `Admin`, khi them/sua san pham ban co the:
  - Nhap `Image URL`, hoac
  - Upload file anh truc tiep (`JPG/PNG/WEBP/GIF`)
- Anh upload se duoc luu vao `public/uploads`.

## 7. Thanh toan (Checkout)
Website co checkout don hang theo gio hang:
- Vao `Gio hang` -> bam `Thanh toan`
- Dien `Dia chi giao hang` và `Phuong thuc thanh toan` rồi đặt hàng.

Trong database:
- File `database/schema.sql` da bao gom cot `payment_method` va `payment_status`.
- Neu ban da import schema truoc do, chay them file: `database/alter_add_payment_columns.sql`.

## 8. Lich su don hang va Review
### Customer
- Xem danh sach don: `/orders`
- Xem chi tiet don: `/orders/view?id=...`
- Danh gia/Binh luan: chi duoc danh gia khi don co `status = completed`.

### Admin
- Quan ly don: `/admin/orders`
- Cap nhat trang thai: chon trang thai roi bấm `Luu`.

### Database (reviews)
- Bang `reviews` da duoc them vao `database/schema.sql`.
- Neu ban da import schema cu, chay them: `database/alter_add_reviews_table.sql`.

## 9. Thong tin ca nhan va hoan tra don hang
- Bang `users` co them cot `phone`, `address`; bang `orders` co them trang thai `returned`.
- Neu ban da import schema cu, chay them: `database/alter_add_profile_and_returns.sql`.

## 10. AI agent n8n lay du lieu website tren InfinityFree
- InfinityFree khong cho remote MySQL, nen n8n khong ket noi truc tiep database.
- Website cung cap API JSON: `/api/ai-context`.
- PHP tren hosting doc MySQL local, n8n goi API nay qua HTTPS de lay danh muc, san pham, gia, ton kho va huong dan mua hang.
- Doi `ai_context_token` trong `config/chat.php` truoc khi upload hosting.
- Trong n8n, them HTTP Request node:
  - Method: `GET`
  - URL: gia tri `contextApiUrl` tu Webhook payload
  - Header: `X-AI-Context-Token` = gia tri `contextApiToken` tu Webhook payload
  - Query `q` = cau hoi khach, `limit` = 12

## 11. Dang nhap bang Google
- Neu database da co san, chay them: `database/alter_add_google_login.sql`.
- Tao OAuth Client trong Google Cloud Console, lay `Client ID` va `Client Secret`.
- Sua `config/google_oauth.php`:
  - `enabled` => `true`
  - `client_id` => Client ID
  - `client_secret` => Client Secret
- Authorized redirect URI can khai bao tren Google:
  - Local: `http://localhost/Webbanlinhkienmaytinh/public/auth/google/callback`
  - Hosting: `https://ten-mien-cua-ban/public/auth/google/callback`

## 12. Dashboard & Analytics
- Neu database da co san, chay them: `database/alter_add_analytics_columns.sql`.
- Dashboard admin: `/admin/dashboard`.
- Loi nhuan duoc tinh tu `gia ban - gia von`; gia von nhap tai trang quan ly san pham.
- CSV import co them cot tuy chon `cost_price`.

## 13. Trien khai Render
- Project da co `Dockerfile` va `render.yaml` de tao PHP web service + MySQL private service.
- Cau hinh production su dung environment variables; danh sach bien nam trong `.env.example`.
- Apache tren Render tro thang vao thu muc `public`, vi vay URL khong can `/public`.
- Huong dan day du: xem `RENDER_DEPLOY.md`.

