# Lam lai RAG cho Render + Aiven MySQL + n8n

Muc tieu: website khong gui toan bo du lieu moi lan chat. n8n se sync du lieu tu Aiven MySQL vao Supabase Vector Store, chatbot chi truy van cac document lien quan.

## 1. Tao view RAG trong Aiven MySQL

Mo MySQL Workbench, chon database `defaultdb`, chay:

```sql
SOURCE database/rag_views.sql;
```

Neu khong dung duoc `SOURCE`, copy noi dung file `database/rag_views.sql` va Run.

Kiem tra:

```sql
SELECT document_id, document_type, title
FROM ai_rag_documents
LIMIT 20;
```

## 2. Tao credential MySQL trong n8n

Dung thong tin Aiven:

- Host: host Aiven MySQL
- Port: port Aiven MySQL
- Database: `defaultdb`
- User: nen dung user rieng chi doc, hoac `avnadmin` de test
- Password: password Aiven
- SSL: bat SSL / required

Neu muon user chi doc, sua password trong `database/rag_readonly_user.sql` roi chay tren Aiven.

## 3. Workflow sync RAG

Workflow rieng, vi du `PC Shop RAG Sync`.

Node:

1. `Schedule Trigger` hoac `Manual Trigger`
2. `MySQL`
3. `Set`
4. `Supabase Vector Store`
5. `Embeddings OpenAI`

### MySQL node

Query:

```sql
SELECT
  document_id,
  document_type,
  title,
  page_content,
  url_path,
  metadata_json,
  product_id,
  category_slug,
  price,
  stock_quantity,
  updated_at
FROM ai_rag_documents;
```

### Set node

Tao cac field:

`pageContent`

```js
{{ $json.page_content }}
```

`metadata`

```js
{{
  {
    id: $json.document_id,
    type: $json.document_type,
    title: $json.title,
    url_path: $json.url_path,
    product_id: $json.product_id,
    category_slug: $json.category_slug,
    price: $json.price,
    stock_quantity: $json.stock_quantity,
    mysql_metadata: $json.metadata_json
  }
}}
```

### Supabase Vector Store node

- Operation: `Insert Documents` hoac `Update vector store documents`
- Table: bang vector store cua ban, vi du `pc_shop_documents`
- Document input: dung `Default Data Loader`
- Data field: `pageContent`
- Metadata: dung field `metadata`
- Embeddings: `Embeddings OpenAI`

Chay workflow sync. Neu thanh cong, so documents phai gan voi so dong cua `ai_rag_documents`.

## 4. Workflow chat

Node chinh:

1. `Webhook` POST
2. `AI Agent`
3. `Respond to Webhook`

Node phu noi vao AI Agent:

- `OpenAI Chat Model`
- `Postgres Chat Memory` neu can lich su hoi dap
- `Supabase Vector Store` as Tool
- `Embeddings OpenAI`

### Supabase Vector Store Tool

Chon action:

`Retrieve documents for AI Agent as Tool`

Ten tool:

```text
pc_shop_knowledge
```

Description:

```text
Tim du lieu PC Parts Shop: san pham, danh muc, gia, ton kho, thong so, flash sale, coupon, combo, huong dan dat hang va quy tac build PC.
```

Limit: `8` den `12`.

Bat `Include Metadata`.

### AI Agent System Message

Copy noi dung file:

```text
config/n8n-rag-system-prompt.md
```

### AI Agent User Message

Dung expression:

```text
Cau hoi khach:
{{ $json.body.message }}

Ngu canh trang hien tai:
siteUrl={{ $json.body.siteUrl }}
pageUrl={{ $json.body.pageUrl }}
pagePath={{ $json.body.pagePath }}
quickActionId={{ $json.body.quickActionId }}

San pham hien tai neu co:
{{ JSON.stringify($json.body.currentProduct || {}) }}
```

Khong them `websiteData` vao prompt nua.

## 5. Render environment

Can cac bien:

```env
CHAT_ENABLED=true
N8N_WEBHOOK_URL=https://your-n8n-domain/webhook/pc-shop-chat
CHAT_TIMEOUT_SECONDS=35
```

`RAG_SYNC_WEBHOOK_URL` co the de trong neu dung workflow sync doc MySQL truc tiep.

## 6. Test bat buoc

Hoi cac cau:

- `toi muon build PC tam gia 60 trieu`
- `toi muon mua ban phim va chuot`
- `man hinh Samsung Odyssey OLED co HDMI khong`
- `tu van ve san pham nay` khi dang o trang chi tiet san pham
- `shop co flash sale nao`

Neu AI chi tra loi cach dung Build PC thay vi goi y cau hinh, kiem tra:

- AI Agent da noi dung tool Supabase Vector Store chua
- Tool limit co qua thap khong
- System Message da copy dung chua
- User Message con `websiteData` cu khong
- Sync workflow co nap du product documents khong
