


START TRANSACTION;


ALTER TABLE remittances ADD INDEX IF NOT EXISTS idx_remittances_due (due_date);
ALTER TABLE remittances ADD INDEX IF NOT EXISTS idx_remittances_prov_due (provider_id, due_date);


DELETE FROM remittances WHERE status IN ('pending', 'overdue');

COMMIT;
