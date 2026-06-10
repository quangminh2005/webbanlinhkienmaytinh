DROP VIEW IF EXISTS ai_rag_documents;

CREATE VIEW ai_rag_documents AS
SELECT
    'guide_shop_info' AS document_id,
    'guide' AS document_type,
    'Thong tin shop va lien he' AS title,
    'PC Parts Shop ban linh kien may tinh, PC gaming va phu kien. Hotline: 034 969 4556. Gio ho tro: 8:00 - 21:00. Email: quangminhngo41@gmail.com.' AS page_content,
    '/' AS url_path,
    JSON_OBJECT('source', 'website', 'section', 'shop') AS metadata_json,
    NULL AS product_id,
    NULL AS category_slug,
    NULL AS price,
    NULL AS stock_quantity,
    NOW() AS updated_at

UNION ALL
SELECT
    'guide_how_to_order',
    'guide',
    'Huong dan dat hang',
    'Cach dat hang: chon san pham, bam Them vao gio hang, vao Gio hang kiem tra, bam Thanh toan, dang nhap neu can, nhap dia chi giao hang, chon phuong thuc thanh toan va dat hang. Khach co the xem lai don trong muc Don hang cua toi.',
    '/cart',
    JSON_OBJECT('source', 'website', 'section', 'ordering'),
    NULL,
    NULL,
    NULL,
    NULL,
    NOW()

UNION ALL
SELECT
    'guide_build_pc',
    'guide',
    'Nguyen tac Build PC',
    'Khi build PC can co du CPU, mainboard, RAM, VGA, nguon, case, SSD va tan nhiet. HDD la tuy chon. Khi tu van cau hinh, can kiem tra socket CPU voi mainboard, RAM type cua mainboard, cong suat nguon va ton kho san pham. Chi de xuat linh kien con hang.',
    '/build-pc',
    JSON_OBJECT('source', 'website', 'section', 'build_pc'),
    NULL,
    NULL,
    NULL,
    NULL,
    NOW()

UNION ALL
SELECT
    CONCAT('category_', c.id),
    'category',
    CONCAT('Danh muc ', c.name),
    CONCAT('Danh muc ', c.name, ' co slug ', c.slug, '. So san pham: ', COUNT(p.id), '.'),
    CONCAT('/?category=', c.slug),
    JSON_OBJECT('source', 'database', 'section', 'category', 'category_id', c.id, 'category_slug', c.slug),
    NULL,
    c.slug,
    NULL,
    NULL,
    NOW()
FROM categories c
LEFT JOIN products p ON p.category_id = c.id
GROUP BY c.id, c.name, c.slug

UNION ALL
SELECT
    CONCAT('product_', p.id),
    'product',
    p.name,
    CONCAT(
        'San pham: ', p.name,
        '. Danh muc: ', c.name, ' (', c.slug, ')',
        '. Gia: ', FORMAT(p.price, 0), ' VND',
        '. Ton kho: ', p.stock_quantity,
        '. Trang thai: ', IF(p.stock_quantity > 0, 'con hang', 'het hang'),
        '. Mo ta/thong so: ', COALESCE(NULLIF(p.description, ''), 'Chua co mo ta'),
        '. Socket: ', COALESCE(NULLIF(p.socket, ''), 'N/A'),
        '. RAM type: ', COALESCE(NULLIF(p.ram_type, ''), 'N/A'),
        '. VRAM: ', COALESCE(p.vram_gb, 0), ' GB',
        '. Wattage: ', COALESCE(p.wattage, 0), 'W',
        '. Link chi tiet: /product?id=', p.id
    ),
    CONCAT('/product?id=', p.id),
    JSON_OBJECT(
        'source', 'database',
        'section', 'product',
        'product_id', p.id,
        'category_id', c.id,
        'category_name', c.name,
        'category_slug', c.slug,
        'price', p.price,
        'stock_quantity', p.stock_quantity,
        'in_stock', p.stock_quantity > 0,
        'socket', p.socket,
        'ram_type', p.ram_type,
        'vram_gb', p.vram_gb,
        'wattage', p.wattage
    ),
    p.id,
    c.slug,
    p.price,
    p.stock_quantity,
    p.created_at
FROM products p
JOIN categories c ON c.id = p.category_id

UNION ALL
SELECT
    CONCAT('coupon_', cp.id),
    'coupon',
    CONCAT('Ma giam gia ', cp.code),
    CONCAT(
        'Ma giam gia ', cp.code,
        '. Kieu giam: ', cp.discount_type,
        '. Gia tri: ', FORMAT(cp.discount_value, 0),
        '. Don toi thieu: ', FORMAT(cp.min_order_amount, 0), ' VND',
        '. Hieu luc tu ', COALESCE(CAST(cp.starts_at AS CHAR), 'khong gioi han'),
        ' den ', COALESCE(CAST(cp.ends_at AS CHAR), 'khong gioi han'),
        '. Trang thai: ', IF(cp.active = 1, 'dang bat', 'dang tat')
    ),
    '/cart',
    JSON_OBJECT('source', 'database', 'section', 'coupon', 'coupon_id', cp.id, 'code', cp.code),
    NULL,
    NULL,
    NULL,
    NULL,
    cp.created_at
FROM coupons cp
WHERE cp.active = 1

UNION ALL
SELECT
    CONCAT('combo_', cb.id),
    'combo_promotion',
    cb.name,
    CONCAT(
        'Khuyen mai combo: ', cb.name,
        '. Mua danh muc ', ca.name, ' + ', cbg.name,
        ' giam ', FORMAT(cb.discount_amount, 0), ' VND.',
        ' Trang thai: ', IF(cb.active = 1, 'dang bat', 'dang tat')
    ),
    '/build-pc',
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
FROM combo_promotions cb
JOIN categories ca ON ca.id = cb.category_a_id
JOIN categories cbg ON cbg.id = cb.category_b_id
WHERE cb.active = 1

UNION ALL
SELECT
    CONCAT('flash_sale_', fs.id),
    'flash_sale',
    CONCAT('Flash sale ', p.name),
    CONCAT(
        'Flash sale san pham ', p.name,
        '. Gia goc: ', FORMAT(p.price, 0), ' VND',
        '. Giam ', fs.discount_type, ' ', FORMAT(fs.discount_value, 0),
        '. Gia sau giam: ',
        FORMAT(
            IF(fs.discount_type = 'percent', p.price * (100 - fs.discount_value) / 100, GREATEST(p.price - fs.discount_value, 0)),
            0
        ),
        ' VND. Thoi gian: ', fs.starts_at, ' den ', fs.ends_at,
        '. Ton kho: ', p.stock_quantity,
        '. Link chi tiet: /product?id=', p.id
    ),
    CONCAT('/product?id=', p.id),
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
    c.slug,
    p.price,
    p.stock_quantity,
    fs.created_at
FROM flash_sales fs
JOIN products p ON p.id = fs.product_id
JOIN categories c ON c.id = p.category_id
WHERE fs.active = 1;
