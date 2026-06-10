-- Optional: create a read-only MySQL user for n8n RAG.
-- Change the password before running.

CREATE USER IF NOT EXISTS 'n8n_rag_reader'@'%' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';

GRANT SELECT ON defaultdb.ai_rag_documents TO 'n8n_rag_reader'@'%';
GRANT SELECT ON defaultdb.categories TO 'n8n_rag_reader'@'%';
GRANT SELECT ON defaultdb.products TO 'n8n_rag_reader'@'%';
GRANT SELECT ON defaultdb.coupons TO 'n8n_rag_reader'@'%';
GRANT SELECT ON defaultdb.combo_promotions TO 'n8n_rag_reader'@'%';
GRANT SELECT ON defaultdb.flash_sales TO 'n8n_rag_reader'@'%';

FLUSH PRIVILEGES;
