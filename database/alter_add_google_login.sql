ALTER TABLE users
    ADD COLUMN google_id VARCHAR(80) DEFAULT NULL UNIQUE AFTER password_hash,
    ADD COLUMN auth_provider VARCHAR(30) NOT NULL DEFAULT 'local' AFTER google_id;
