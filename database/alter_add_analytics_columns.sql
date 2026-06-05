ALTER TABLE products
    ADD COLUMN cost_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER price;

ALTER TABLE order_items
    ADD COLUMN unit_cost_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit_price;
