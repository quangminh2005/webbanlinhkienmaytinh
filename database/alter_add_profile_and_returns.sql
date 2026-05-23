ALTER TABLE users
    ADD COLUMN phone VARCHAR(30) DEFAULT NULL AFTER password_hash,
    ADD COLUMN address TEXT AFTER phone;

ALTER TABLE orders
    MODIFY status ENUM('pending', 'processing', 'shipping', 'completed', 'cancelled', 'returned') DEFAULT 'pending';
