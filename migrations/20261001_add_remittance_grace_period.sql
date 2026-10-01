-- Add overdue notification and grace period columns to remittances table
ALTER TABLE remittances ADD COLUMN IF NOT EXISTS overdue_notified_at DATETIME NULL AFTER receipt_path;
ALTER TABLE remittances ADD COLUMN IF NOT EXISTS grace_period_expires_at DATETIME NULL AFTER overdue_notified_at;
ALTER TABLE remittances ADD INDEX IF NOT EXISTS idx_remittances_grace (grace_period_expires_at);
