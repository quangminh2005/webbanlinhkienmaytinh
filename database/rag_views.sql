DROP VIEW IF EXISTS ai_rag_documents;

CREATE VIEW ai_rag_documents AS
WITH
norm_categories AS (
    SELECT
        id,
        CONVERT(name USING utf8mb4) COLLATE utf8mb4_general_ci AS name,
        CONVERT(slug USING utf8mb4) COLLATE utf8mb4_general_ci AS slug,
        CASE CONVERT(slug USING utf8mb4) COLLATE utf8mb4_general_ci
            WHEN 'cpu' THEN 'cpu, bo xu ly, chip'
            WHEN 'mainboard' THEN 'mainboard, motherboard, bo mach chu, main'
            WHEN 'vga' THEN 'vga, gpu, card man hinh, card do hoa'
            WHEN 'ram' THEN 'ram, bo nho'
            WHEN 'psu' THEN 'psu, nguon, nguon may tinh'
            WHEN 'ssd' THEN 'ssd, o cung ssd, luu tru'
            WHEN 'hdd' THEN 'hdd, o cung hdd, luu tru'
            WHEN 'case' THEN 'case, vo case, vo may tinh'
            WHEN 'cooler' THEN 'cooler, tan nhiet, quat tan nhiet'
            WHEN 'monitor' THEN 'monitor, man hinh, man hinh may tinh'
            WHEN 'keyboard' THEN 'keyboard, ban phim, phim co'
            WHEN 'mouse' THEN 'mouse, chuot, chuot gaming'
            ELSE CONVERT(slug USING utf8mb4) COLLATE utf8mb4_general_ci
        END AS alias_keywords
    FROM categories
),
norm_products AS (
    SELECT
        p.id,
        p.category_id,
        CONVERT(p.name USING utf8mb4) COLLATE utf8mb4_general_ci AS name,
        p.price,
        p.stock_quantity,
        CONVERT(COALESCE(p.description, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS description,
        CONVERT(COALESCE(p.socket, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS socket,
        CONVERT(COALESCE(p.ram_type, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS ram_type,
        p.vram_gb,
        p.wattage,
        p.created_at,
        c.name AS category_name,
        c.slug AS category_slug,
        c.alias_keywords AS category_alias_keywords
    FROM products p
    JOIN norm_categories c ON c.id = p.category_id
),
norm_coupons AS (
    SELECT
        id,
        CONVERT(code USING utf8mb4) COLLATE utf8mb4_general_ci AS code,
        CONVERT(discount_type USING utf8mb4) COLLATE utf8mb4_general_ci AS discount_type,
        discount_value,
        min_order_amount,
        starts_at,
        ends_at,
        active,
        created_at
    FROM coupons
),
norm_combos AS (
    SELECT
        cb.id,
        CONVERT(cb.name USING utf8mb4) COLLATE utf8mb4_general_ci AS name,
        cb.category_a_id,
        cb.category_b_id,
        cb.discount_amount,
        cb.active,
        cb.created_at
    FROM combo_promotions cb
),
norm_flash_sales AS (
    SELECT
        id,
        product_id,
        CONVERT(discount_type USING utf8mb4) COLLATE utf8mb4_general_ci AS discount_type,
        discount_value,
        starts_at,
        ends_at,
        active,
        created_at
    FROM flash_sales
)
SELECT
    CAST('guide_shop_info' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS document_id,
    CAST('guide' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS document_type,
    CAST('Thong tin shop va lien he' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS title,
    CAST('PC Parts Shop ban linh kien may tinh, PC gaming va phu kien. Hotline: 034 969 4556. Gio ho tro: 8:00 - 21:00. Email: quangminhngo41@gmail.com.' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS page_content,
    CAST('/' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci AS url_path,
    JSON_OBJECT('source', 'website', 'section', 'shop') AS metadata_json,
    NULL AS product_id,
    NULL AS category_slug,
    NULL AS price,
    NULL AS stock_quantity,
    NOW() AS updated_at

UNION ALL
SELECT
    CAST('guide_how_to_order' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('guide' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('Huong dan dat hang' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('Cach dat hang: chon san pham, bam Them vao gio hang, vao Gio hang kiem tra, bam Thanh toan, dang nhap neu can, nhap dia chi giao hang, chon phuong thuc thanh toan va dat hang. Khach co the xem lai don trong muc Don hang cua toi.' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('/cart' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'website', 'section', 'ordering'),
    NULL,
    NULL,
    NULL,
    NULL,
    NOW()

UNION ALL
SELECT
    CAST('guide_build_pc' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('guide' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('Nguyen tac Build PC' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('Khi build PC can co du CPU, mainboard, RAM, VGA, nguon, case, SSD va tan nhiet. HDD la tuy chon. Khi tu van cau hinh, can kiem tra socket CPU voi mainboard, RAM type cua mainboard, cong suat nguon va ton kho san pham. Chi de xuat linh kien con hang.' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('/build-pc' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'website', 'section', 'build_pc'),
    NULL,
    NULL,
    NULL,
    NULL,
    NOW()

UNION ALL
SELECT
    CAST('flash_sale_current' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('flash_sale_summary' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CAST('Flash sale dang dien ra' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    IF(
        COUNT(fs.id) > 0,
        CONCAT(
            CAST('Cac san pham flash sale dang co: ' AS CHAR CHARACTER SET utf8mb4),
            GROUP_CONCAT(
                CONCAT(
                    p.name,
                    CAST(' | Gia goc: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(p.price, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
                    CAST(' | Giam: ' AS CHAR CHARACTER SET utf8mb4), fs.discount_type, CAST(' ' AS CHAR CHARACTER SET utf8mb4), FORMAT(fs.discount_value, 0),
                    CAST(' | Gia sau giam: ' AS CHAR CHARACTER SET utf8mb4),
                    FORMAT(
                        IF(fs.discount_type = 'percent', p.price * (100 - fs.discount_value) / 100, GREATEST(p.price - fs.discount_value, 0)),
                        0
                    ),
                    CAST(' VND | Ton kho: ' AS CHAR CHARACTER SET utf8mb4), p.stock_quantity,
                    CAST(' | Link: /product?id=' AS CHAR CHARACTER SET utf8mb4), p.id
                )
                SEPARATOR '; '
            )
        ),
        CAST('Hien khong co san pham flash sale dang bat.' AS CHAR CHARACTER SET utf8mb4)
    ) COLLATE utf8mb4_general_ci,
    CAST('/' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'database', 'section', 'flash_sale_summary'),
    NULL,
    NULL,
    NULL,
    NULL,
    NOW()
FROM norm_flash_sales fs
JOIN norm_products p ON p.id = fs.product_id
WHERE fs.active = 1

UNION ALL
SELECT
    CONCAT(CAST('category_' AS CHAR CHARACTER SET utf8mb4), c.id) COLLATE utf8mb4_general_ci,
    CAST('category' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('Danh muc ' AS CHAR CHARACTER SET utf8mb4), c.name) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('Danh muc ' AS CHAR CHARACTER SET utf8mb4), c.name, CAST(' co slug ' AS CHAR CHARACTER SET utf8mb4), c.slug, CAST('. Tu khoa: ' AS CHAR CHARACTER SET utf8mb4), c.alias_keywords, CAST('. So san pham: ' AS CHAR CHARACTER SET utf8mb4), COUNT(p.id), CAST('.' AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('/?category=' AS CHAR CHARACTER SET utf8mb4), c.slug) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'database', 'section', 'category', 'category_id', c.id, 'category_slug', c.slug, 'alias_keywords', c.alias_keywords),
    NULL,
    c.slug,
    NULL,
    NULL,
    NOW()
FROM norm_categories c
LEFT JOIN products p ON p.category_id = c.id
GROUP BY c.id, c.name, c.slug, c.alias_keywords

UNION ALL
SELECT
    CONCAT(CAST('category_inventory_' AS CHAR CHARACTER SET utf8mb4), c.slug) COLLATE utf8mb4_general_ci,
    CAST('category_inventory' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('San pham con hang danh muc ' AS CHAR CHARACTER SET utf8mb4), c.name) COLLATE utf8mb4_general_ci,
    CONCAT(
        IF(
            c.slug IN ('cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler', 'hdd'),
            CAST('Build PC - ' AS CHAR CHARACTER SET utf8mb4),
            CAST('' AS CHAR CHARACTER SET utf8mb4)
        ),
        CAST('Danh muc ' AS CHAR CHARACTER SET utf8mb4), c.name,
        CAST('. Tu khoa: ' AS CHAR CHARACTER SET utf8mb4), c.alias_keywords,
        IF(
            c.slug IN ('cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler'),
            CAST('. Day la nhom linh kien bat buoc khi build PC.' AS CHAR CHARACTER SET utf8mb4),
            IF(c.slug = 'hdd', CAST('. Day la nhom linh kien tuy chon khi build PC.' AS CHAR CHARACTER SET utf8mb4), CAST('' AS CHAR CHARACTER SET utf8mb4))
        ),
        CAST('. San pham con hang: ' AS CHAR CHARACTER SET utf8mb4),
        COALESCE(
            GROUP_CONCAT(
                IF(
                    p.stock_quantity > 0,
                    CONCAT(
                        p.name,
                        CAST(' | Gia: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(p.price, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
                        CAST(' | Ton kho: ' AS CHAR CHARACTER SET utf8mb4), p.stock_quantity,
                        CAST(' | Link: /product?id=' AS CHAR CHARACTER SET utf8mb4), p.id
                    ),
                    NULL
                )
                ORDER BY p.price DESC
                SEPARATOR '; '
            ),
            CAST('Khong co san pham con hang.' AS CHAR CHARACTER SET utf8mb4)
        )
    ) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('/?category=' AS CHAR CHARACTER SET utf8mb4), c.slug) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'database', 'section', 'category_inventory', 'category_id', c.id, 'category_slug', c.slug, 'alias_keywords', c.alias_keywords),
    NULL,
    c.slug,
    NULL,
    NULL,
    NOW()
FROM norm_categories c
LEFT JOIN norm_products p ON p.category_id = c.id
GROUP BY c.id, c.name, c.slug, c.alias_keywords

UNION ALL
SELECT
    CONCAT(CAST('build_pc_candidates_' AS CHAR CHARACTER SET utf8mb4), c.slug) COLLATE utf8mb4_general_ci,
    CAST('build_pc_candidates' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('Build PC candidates - ' AS CHAR CHARACTER SET utf8mb4), c.name) COLLATE utf8mb4_general_ci,
    CONCAT(
        CAST('Build PC required category: ' AS CHAR CHARACTER SET utf8mb4), c.name,
        CAST('. Slug: ' AS CHAR CHARACTER SET utf8mb4), c.slug,
        CAST('. Keywords: ' AS CHAR CHARACTER SET utf8mb4), c.alias_keywords,
        CAST('. Available products: ' AS CHAR CHARACTER SET utf8mb4),
        COALESCE(
            GROUP_CONCAT(
                IF(
                    p.stock_quantity > 0,
                    CONCAT(
                        p.name,
                        CAST(' | Price: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(p.price, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
                        CAST(' | Stock: ' AS CHAR CHARACTER SET utf8mb4), p.stock_quantity,
                        CAST(' | Socket: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(NULLIF(p.socket, ''), CAST('N/A' AS CHAR CHARACTER SET utf8mb4)),
                        CAST(' | RAM type: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(NULLIF(p.ram_type, ''), CAST('N/A' AS CHAR CHARACTER SET utf8mb4)),
                        CAST(' | Wattage: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(p.wattage, 0), CAST('W' AS CHAR CHARACTER SET utf8mb4),
                        CAST(' | Link: /product?id=' AS CHAR CHARACTER SET utf8mb4), p.id
                    ),
                    NULL
                )
                ORDER BY p.price DESC
                SEPARATOR '; '
            ),
            CAST('No in-stock product in this category.' AS CHAR CHARACTER SET utf8mb4)
        )
    ) COLLATE utf8mb4_general_ci,
    CAST('/build-pc' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'database', 'section', 'build_pc_candidates', 'category_id', c.id, 'category_slug', c.slug, 'alias_keywords', c.alias_keywords),
    NULL,
    c.slug,
    NULL,
    NULL,
    NOW()
FROM norm_categories c
LEFT JOIN norm_products p ON p.category_id = c.id
WHERE c.slug IN ('cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler', 'hdd')
GROUP BY c.id, c.name, c.slug, c.alias_keywords

UNION ALL
SELECT
    CONCAT(CAST('product_' AS CHAR CHARACTER SET utf8mb4), p.id) COLLATE utf8mb4_general_ci,
    CAST('product' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    p.name,
    CONCAT(
        CAST('San pham: ' AS CHAR CHARACTER SET utf8mb4), p.name,
        CAST('. Danh muc: ' AS CHAR CHARACTER SET utf8mb4), p.category_name, CAST(' (' AS CHAR CHARACTER SET utf8mb4), p.category_slug, CAST(')' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Tu khoa danh muc: ' AS CHAR CHARACTER SET utf8mb4), p.category_alias_keywords,
        CAST('. Gia: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(p.price, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Ton kho: ' AS CHAR CHARACTER SET utf8mb4), p.stock_quantity,
        CAST('. Trang thai: ' AS CHAR CHARACTER SET utf8mb4), IF(p.stock_quantity > 0, CAST('con hang' AS CHAR CHARACTER SET utf8mb4), CAST('het hang' AS CHAR CHARACTER SET utf8mb4)),
        CAST('. Mo ta/thong so: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(NULLIF(p.description, ''), CAST('Chua co mo ta' AS CHAR CHARACTER SET utf8mb4)),
        CAST('. Socket: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(NULLIF(p.socket, ''), CAST('N/A' AS CHAR CHARACTER SET utf8mb4)),
        CAST('. RAM type: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(NULLIF(p.ram_type, ''), CAST('N/A' AS CHAR CHARACTER SET utf8mb4)),
        CAST('. VRAM: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(p.vram_gb, 0), CAST(' GB' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Wattage: ' AS CHAR CHARACTER SET utf8mb4), COALESCE(p.wattage, 0), CAST('W' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Link chi tiet: /product?id=' AS CHAR CHARACTER SET utf8mb4), p.id
    ) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('/product?id=' AS CHAR CHARACTER SET utf8mb4), p.id) COLLATE utf8mb4_general_ci,
    JSON_OBJECT(
        'source', 'database',
        'section', 'product',
        'product_id', p.id,
        'category_id', p.category_id,
        'category_name', p.category_name,
        'category_slug', p.category_slug,
        'alias_keywords', p.category_alias_keywords,
        'price', p.price,
        'stock_quantity', p.stock_quantity,
        'in_stock', p.stock_quantity > 0,
        'socket', p.socket,
        'ram_type', p.ram_type,
        'vram_gb', p.vram_gb,
        'wattage', p.wattage
    ),
    p.id,
    p.category_slug,
    p.price,
    p.stock_quantity,
    p.created_at
FROM norm_products p

UNION ALL
SELECT
    CONCAT(CAST('coupon_' AS CHAR CHARACTER SET utf8mb4), cp.id) COLLATE utf8mb4_general_ci,
    CAST('coupon' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('Ma giam gia ' AS CHAR CHARACTER SET utf8mb4), cp.code) COLLATE utf8mb4_general_ci,
    CONCAT(
        CAST('Ma giam gia ' AS CHAR CHARACTER SET utf8mb4), cp.code,
        CAST('. Kieu giam: ' AS CHAR CHARACTER SET utf8mb4), cp.discount_type,
        CAST('. Gia tri: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(cp.discount_value, 0),
        CAST('. Don toi thieu: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(cp.min_order_amount, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Hieu luc tu ' AS CHAR CHARACTER SET utf8mb4), COALESCE(CAST(cp.starts_at AS CHAR CHARACTER SET utf8mb4), CAST('khong gioi han' AS CHAR CHARACTER SET utf8mb4)),
        CAST(' den ' AS CHAR CHARACTER SET utf8mb4), COALESCE(CAST(cp.ends_at AS CHAR CHARACTER SET utf8mb4), CAST('khong gioi han' AS CHAR CHARACTER SET utf8mb4)),
        CAST('. Trang thai: ' AS CHAR CHARACTER SET utf8mb4), IF(cp.active = 1, CAST('dang bat' AS CHAR CHARACTER SET utf8mb4), CAST('dang tat' AS CHAR CHARACTER SET utf8mb4))
    ) COLLATE utf8mb4_general_ci,
    CAST('/cart' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT('source', 'database', 'section', 'coupon', 'coupon_id', cp.id, 'code', cp.code),
    NULL,
    NULL,
    NULL,
    NULL,
    cp.created_at
FROM norm_coupons cp
WHERE cp.active = 1

UNION ALL
SELECT
    CONCAT(CAST('combo_' AS CHAR CHARACTER SET utf8mb4), cb.id) COLLATE utf8mb4_general_ci,
    CAST('combo_promotion' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    cb.name,
    CONCAT(
        CAST('Khuyen mai combo: ' AS CHAR CHARACTER SET utf8mb4), cb.name,
        CAST('. Mua danh muc ' AS CHAR CHARACTER SET utf8mb4), ca.name, CAST(' + ' AS CHAR CHARACTER SET utf8mb4), cbg.name,
        CAST(' giam ' AS CHAR CHARACTER SET utf8mb4), FORMAT(cb.discount_amount, 0), CAST(' VND.' AS CHAR CHARACTER SET utf8mb4),
        CAST(' Trang thai: ' AS CHAR CHARACTER SET utf8mb4), IF(cb.active = 1, CAST('dang bat' AS CHAR CHARACTER SET utf8mb4), CAST('dang tat' AS CHAR CHARACTER SET utf8mb4))
    ) COLLATE utf8mb4_general_ci,
    CAST('/build-pc' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    JSON_OBJECT(
        'source', 'database',
        'section', 'combo_promotion',
        'combo_id', cb.id,
        'category_a_slug', ca.slug,
        'category_b_slug', cbg.slug,
        'discount_amount', cb.discount_amount
    ),
    NULL,
    NULL,
    NULL,
    NULL,
    cb.created_at
FROM norm_combos cb
JOIN norm_categories ca ON ca.id = cb.category_a_id
JOIN norm_categories cbg ON cbg.id = cb.category_b_id
WHERE cb.active = 1

UNION ALL
SELECT
    CONCAT(CAST('flash_sale_' AS CHAR CHARACTER SET utf8mb4), fs.id) COLLATE utf8mb4_general_ci,
    CAST('flash_sale' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('Flash sale ' AS CHAR CHARACTER SET utf8mb4), p.name) COLLATE utf8mb4_general_ci,
    CONCAT(
        CAST('Flash sale san pham ' AS CHAR CHARACTER SET utf8mb4), p.name,
        CAST('. Gia goc: ' AS CHAR CHARACTER SET utf8mb4), FORMAT(p.price, 0), CAST(' VND' AS CHAR CHARACTER SET utf8mb4),
        CAST('. Giam ' AS CHAR CHARACTER SET utf8mb4), fs.discount_type, CAST(' ' AS CHAR CHARACTER SET utf8mb4), FORMAT(fs.discount_value, 0),
        CAST('. Gia sau giam: ' AS CHAR CHARACTER SET utf8mb4),
        FORMAT(
            IF(fs.discount_type = 'percent', p.price * (100 - fs.discount_value) / 100, GREATEST(p.price - fs.discount_value, 0)),
            0
        ),
        CAST(' VND. Thoi gian: ' AS CHAR CHARACTER SET utf8mb4), fs.starts_at, CAST(' den ' AS CHAR CHARACTER SET utf8mb4), fs.ends_at,
        CAST('. Ton kho: ' AS CHAR CHARACTER SET utf8mb4), p.stock_quantity,
        CAST('. Link chi tiet: /product?id=' AS CHAR CHARACTER SET utf8mb4), p.id
    ) COLLATE utf8mb4_general_ci,
    CONCAT(CAST('/product?id=' AS CHAR CHARACTER SET utf8mb4), p.id) COLLATE utf8mb4_general_ci,
    JSON_OBJECT(
        'source', 'database',
        'section', 'flash_sale',
        'flash_sale_id', fs.id,
        'product_id', p.id,
        'discount_type', fs.discount_type,
        'discount_value', fs.discount_value,
        'starts_at', fs.starts_at,
        'ends_at', fs.ends_at
    ),
    p.id,
    p.category_slug,
    p.price,
    p.stock_quantity,
    fs.created_at
FROM norm_flash_sales fs
JOIN norm_products p ON p.id = fs.product_id
WHERE fs.active = 1;
