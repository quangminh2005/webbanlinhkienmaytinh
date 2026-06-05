# Trien khai PC Parts Shop len Render

Project duoc cau hinh de chay tren Render bang Docker:

- `pc-parts-shop`: PHP 8.2 + Apache, document root la `public/`.
- `pc-parts-mysql`: MySQL 8 private service.
- Persistent disk cho MySQL va `public/uploads`.

## 1. Chuan bi Git repository

Push project len GitHub/GitLab. Khong commit mat khau, token hoac file `.env`.

Render se doc file `render.yaml` o thu muc goc de tao hai service.

## 2. Tao Blueprint tren Render

1. Dang nhap Render.
2. Chon `New` > `Blueprint`.
3. Chon repository cua project.
4. Render se phat hien `render.yaml`.
5. Dien cac bien moi truong duoc danh dau `sync: false`:

```text
N8N_WEBHOOK_URL
RAG_SYNC_WEBHOOK_URL
AI_CONTEXT_TOKEN
GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
```

Neu chua dung Google OAuth, giu:

```text
GOOGLE_OAUTH_ENABLED=false
```

Sau khi cau hinh Google OAuth xong, doi thanh:

```text
GOOGLE_OAUTH_ENABLED=true
```

## 3. Database

Lan khoi tao dau tien voi MySQL disk rong, MySQL tu chay:

```text
database/schema.sql
```

File schema chi tao cau truc va du lieu mau. De chuyen toan bo du lieu hien tai:

1. Export database hien tai bang phpMyAdmin hoac `mysqldump`.
2. Import file SQL vao service `pc-parts-mysql` qua Render Shell/SSH.
3. Khong xoa persistent disk sau khi da import.

Lenh export tham khao:

```bash
mysqldump -h HOST_CU -u USER_CU -p --single-transaction --routines --triggers pc_shop > pc_shop_backup.sql
```

Lenh import chay trong MySQL service:

```bash
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < pc_shop_backup.sql
```

Neu import backup day du vao database moi, co the bo qua du lieu mau cua `schema.sql`.

## 4. Google OAuth

Website Render khong con `/public` trong URL. Authorized redirect URI phai co dang:

```text
https://TEN-SERVICE.onrender.com/auth/google/callback
```

Them URI nay trong Google Cloud Console, sau do dat:

```text
GOOGLE_OAUTH_ENABLED=true
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

## 5. n8n va RAG

Cap nhat website URL trong workflow n8n sang domain Render:

```text
https://TEN-SERVICE.onrender.com
```

Kiem tra cac bien:

```text
N8N_WEBHOOK_URL=https://.../webhook/...
RAG_SYNC_WEBHOOK_URL=https://.../webhook/pc-shop-rag-sync
AI_CONTEXT_TOKEN=chuoi-bi-mat-dai
```

Sau khi website Render hoat dong:

1. Dang nhap admin.
2. Vao `Dong bo AI/RAG`.
3. Dong bo lai du lieu.
4. Test workflow chat production.

Tren Render, n8n co the goi API website truc tiep ma khong gap JavaScript challenge cua InfinityFree.

## 6. Anh upload

Thu muc sau duoc gan persistent disk:

```text
/var/www/html/public/uploads
```

Anh upload moi se khong mat khi deploy lai. Anh cu trong InfinityFree can duoc tai ve va dua vao disk upload tren Render, hoac thay bang URL anh ngoai.

## 7. Domain va HTTPS

Render tu cap HTTPS cho domain `onrender.com`. Neu dung custom domain:

1. Them domain trong Render.
2. Cau hinh DNS theo huong dan Render.
3. Them redirect URI Google OAuth cho domain moi.
4. Cap nhat URL website trong n8n.

## 8. Luu y chi phi

Blueprint hien tai dung:

- Web service `starter` vi can persistent disk cho upload.
- Private MySQL service `starter` voi persistent disk.

MySQL va persistent disk khong phu hop voi free instance neu can du lieu on dinh.

## 9. Bien moi truong

Danh sach day du nam trong `.env.example`.

Khong sua truc tiep mat khau/token trong source code khi deploy production.
