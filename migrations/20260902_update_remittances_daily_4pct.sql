-- Migration: 20260902_update_remittances_daily_4pct.sql
-- Description: Update remittance system to daily cycle with 4% fee

START TRANSACTION;

-- Add indexes on remittances table if not already present
ALTER TABLE remittances ADD INDEX IF NOT EXISTS idx_remittances_due (due_date);
ALTER TABLE remittances ADD INDEX IF NOT EXISTS idx_remittances_prov_due (provider_id, due_date);

-- Remove unpaid / pending / overdue weekly records (preserve submitted and paid records)
DELETE FROM remittances WHERE status IN ('pending', 'overdue');

COMMIT;
