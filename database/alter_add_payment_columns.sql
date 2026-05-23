USE pc_shop;

-- An toan voi DB da import schema cu: chi ADD neu chua co cot.
SET @payment_method_exists :=
    (SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'orders'
       AND column_name = 'payment_method');

SET @payment_status_exists :=
    (SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'orders'
       AND column_name = 'payment_status');

SET @sql := IF(@payment_method_exists = 0,
    'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@payment_status_exists = 0,
    'ALTER TABLE orders ADD COLUMN payment_status ENUM(''unpaid'', ''paid'', ''cancelled'') DEFAULT ''unpaid''',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

