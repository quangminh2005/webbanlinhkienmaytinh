# Trien khai Render free web + database/storage ben ngoai

File dung cho huong free:

```text
render-free.yaml
```

Cau hinh nay chi tao 1 web service PHP/Apache tren Render free. No khong tao MySQL service va khong gan persistent disk.

## 1. Kien truc

- Render free web: chay source PHP.
- MySQL remote ben ngoai: luu database.
- Anh san pham: uu tien URL ngoai trong cot `image_url`.
- n8n/Supabase RAG: giu nhu hien tai, chi cap nhat domain website moi.

Render free co filesystem tam thoi. Moi file upload vao `public/uploads` co the mat khi service restart/redeploy, nen khong nen dung upload local cho production free.

## 2. Chuan bi database remote

Can mot MySQL host cho phep ket noi tu Render qua internet.

Gia tri can lay:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

Sau do import database hien tai:

```bash
mysql -h DB_HOST -P DB_PORT -u DB_USER -p DB_NAME < pc_shop_backup.sql
```

Neu khong import du lieu cu, chay `database/schema.sql` truoc roi chay cac file `alter_*.sql` tuong ung tinh nang da them.

## 3. Xu ly anh san pham

Huong free nen dung Cloudinary hoac URL anh ngoai.

### Cach khuyen dung: Cloudinary

1. Tao tai khoan Cloudinary.
2. Vao Dashboard lay:
   - `Cloud name`
   - `API Key`
   - `API Secret`
3. Tren Render them env:

```text
IMAGE_STORAGE_DRIVER=cloudinary
CLOUDINARY_CLOUD_NAME=cloud-name-cua-ban
CLOUDINARY_API_KEY=api-key-cua-ban
CLOUDINARY_API_SECRET=api-secret-cua-ban
CLOUDINARY_FOLDER=pc-parts-shop/products
```

Sau khi cau hinh xong, nut upload file trong admin san pham se upload anh len Cloudinary va luu URL HTTPS vao cot `image_url`.

### Cach thu cong: dan URL anh

1. Upload anh len Cloudinary, Supabase Storage, imgbb, S3-compatible storage, hoac mot host anh co link public.
2. Copy link HTTPS.
3. Trong admin san pham, dan vao o `Image URL`.

Code hien tai da ho tro `image_url` dang `https://...`.

## 4. Deploy tren Render free

Render mac dinh doc file `render.yaml`. Vi project dang co ca `render.yaml` ban tra phi va `render-free.yaml` ban free, co 2 cach:

### Cach A: Tao Web Service thu cong

1. Render > `New` > `Web Service`.
2. Chon repository.
3. Runtime: `Docker`.
4. Instance type: `Free`.
5. Health check path: `/healthz`.
6. Them environment variables theo muc 5 ben duoi.

Cach nay khong can doi ten file `render.yaml`.

### Cach B: Dung Blueprint free

1. Doi ten `render.yaml` hien tai thanh `render-paid.yaml`.
2. Doi `render-free.yaml` thanh `render.yaml`.
3. Push len GitHub.
4. Render > `New` > `Blueprint`.
5. Chon repository va dien environment variables.

Chi dung Cach B neu ban chac chan muon dung huong free.

## 5. Environment variables can dien

Bat buoc:

```text
DB_HOST=...
DB_PORT=3306
DB_NAME=...
DB_USER=...
DB_PASSWORD=...
DB_CHARSET=utf8mb4
DB_TIMEZONE=+07:00
DB_SSL_MODE=required
DB_SSL_CA_BASE64=...
DB_SSL_VERIFY_SERVER_CERT=true
APP_TIMEZONE=Asia/Saigon
```

Neu MySQL provider khong yeu cau SSL, co the de:

```text
DB_SSL_MODE=
DB_SSL_CA_BASE64=
DB_SSL_VERIFY_SERVER_CERT=false
```

Voi Aiven, tai file CA certificate trong trang connection settings, sau do ma hoa base64 va dan vao `DB_SSL_CA_BASE64`.

AI/n8n:

```text
CHAT_ENABLED=true
N8N_WEBHOOK_URL=https://.../webhook/...
RAG_SYNC_WEBHOOK_URL=https://.../webhook/pc-shop-rag-sync
AI_CONTEXT_TOKEN=chuoi-bi-mat-dai
CHAT_TIMEOUT_SECONDS=25
```

Google login neu dung:

```text
GOOGLE_OAUTH_ENABLED=true
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

Thong tin shop:

```text
SHOP_NAME=PC Parts Shop
SHOP_HOTLINE=034 969 4556
SHOP_HOURS=8:00 - 21:00
SHOP_EMAIL=quangminhngo41@gmail.com
```

Storage anh Cloudinary:

```text
IMAGE_STORAGE_DRIVER=cloudinary
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
CLOUDINARY_FOLDER=pc-parts-shop/products
```

## 6. Google OAuth

Render khong dung `/public` trong URL. Redirect URI phai la:

```text
https://TEN-SERVICE.onrender.com/auth/google/callback
```

Neu dung custom domain, them tiep:

```text
https://tenmiencuaban.com/auth/google/callback
```

## 7. n8n va RAG

Sau khi deploy xong:

1. Doi domain website trong n8n sang URL Render.
2. Cap nhat webhook chat neu can.
3. Vao admin website > `Dong bo AI/RAG`.
4. Sync lai documents vao Supabase Vector Store.
5. Test cau hoi san pham va Build PC.

## 8. Gioi han can chap nhan

- Service free co the bi ngu sau mot thoi gian khong co truy cap, lan dau mo lai se cham.
- Khong co persistent disk, upload local khong on dinh.
- MySQL remote free thuong co gioi han dung luong, toc do va so connection.
- Website PHP van can MySQL remote that su cho phep ket noi tu Render.
