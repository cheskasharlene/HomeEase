-- HomeEase schema normalization migration
-- Run: source migrations/20260530_normalize_core_schema.sql

START TRANSACTION;

-- 1) Normalize payment methods
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payment_methods (code, name, is_active)
VALUES
    ('cash', 'Cash', 1),
    ('gcash', 'GCash', 1),
    ('bank', 'Bank Transfer', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active);

ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_method_id INT NULL AFTER user_id;
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payment_method_id (payment_method_id);

UPDATE payments p
JOIN payment_methods pm
    ON (pm.code COLLATE utf8mb4_unicode_ci) = (LOWER(COALESCE(p.payment_method, 'cash')) COLLATE utf8mb4_unicode_ci)
SET p.payment_method_id = pm.id
WHERE p.payment_method_id IS NULL;

-- 2) Normalize bookings -> services and assigned provider
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS service_id INT NULL AFTER user_id;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS provider_id INT NULL AFTER service_id;
ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_service_id (service_id);
ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_provider_id (provider_id);

UPDATE bookings b
JOIN services s
    ON (LOWER(TRIM(s.name)) COLLATE utf8mb4_unicode_ci) = (LOWER(TRIM(COALESCE(b.service, ''))) COLLATE utf8mb4_unicode_ci)
SET b.service_id = s.id
WHERE b.service_id IS NULL;

-- 3) Dynamic key-value booking details
CREATE TABLE IF NOT EXISTS booking_details (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    field_name VARCHAR(120) NOT NULL,
    field_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_details_booking (booking_id),
    INDEX idx_booking_details_field (field_name),
    UNIQUE KEY uq_booking_field (booking_id, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Allow multiple services per provider
CREATE TABLE IF NOT EXISTS service_provider_services (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provider_service (provider_id, service_id),
    INDEX idx_sps_provider (provider_id),
    INDEX idx_sps_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO service_provider_services (provider_id, service_id)
SELECT sp.provider_id, s.id
FROM service_providers sp
JOIN services s
    ON (LOWER(TRIM(s.name)) COLLATE utf8mb4_unicode_ci) = (LOWER(TRIM(COALESCE(sp.service_category, ''))) COLLATE utf8mb4_unicode_ci);

-- 5) Provider document store (normalized)
CREATE TABLE IF NOT EXISTS provider_documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_status ENUM('submitted','approved','rejected') NOT NULL DEFAULT 'submitted',
    verified_at TIMESTAMP NULL,
    verification_notes TEXT NULL,
    INDEX idx_provider_docs_provider (provider_id),
    INDEX idx_provider_docs_type (document_type),
    INDEX idx_provider_docs_status (verified_status),
    UNIQUE KEY uq_provider_doc_type (provider_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'valid_id', valid_id, NOW()
FROM service_providers
WHERE valid_id IS NOT NULL AND TRIM(valid_id) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'selfie_verification', selfie_verification, NOW()
FROM service_providers
WHERE selfie_verification IS NOT NULL AND TRIM(selfie_verification) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'proof_of_address', proof_of_address, NOW()
FROM service_providers
WHERE proof_of_address IS NOT NULL AND TRIM(proof_of_address) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'barangay_clearance', barangay_clearance, NOW()
FROM service_providers
WHERE barangay_clearance IS NOT NULL AND TRIM(barangay_clearance) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'tools_kits', `tools_&_kits`, NOW()
FROM service_providers
WHERE `tools_&_kits` IS NOT NULL AND TRIM(`tools_&_kits`) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'gcash_qr', COALESCE(qr_gcash, gcash_qr), NOW()
FROM service_providers
WHERE COALESCE(qr_gcash, gcash_qr) IS NOT NULL AND TRIM(COALESCE(qr_gcash, gcash_qr)) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

INSERT INTO provider_documents (provider_id, document_type, file_path, uploaded_at)
SELECT provider_id, 'bank_qr', COALESCE(qr_bank, bank_qr), NOW()
FROM service_providers
WHERE COALESCE(qr_bank, bank_qr) IS NOT NULL AND TRIM(COALESCE(qr_bank, bank_qr)) <> ''
ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = VALUES(uploaded_at);

-- 6) Booking status audit log
CREATE TABLE IF NOT EXISTS booking_status_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    changed_by_role ENUM('user','provider','admin','system') NOT NULL DEFAULT 'system',
    changed_by_id INT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_status_logs_booking (booking_id),
    INDEX idx_booking_status_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by_role, changed_by_id, notes, created_at)
SELECT b.id, NULL, b.status, 'system', NULL, 'Initial snapshot during normalization', NOW()
FROM bookings b
WHERE NOT EXISTS (
    SELECT 1 FROM booking_status_logs l WHERE l.booking_id = b.id
);

-- 7) Add foreign keys if missing
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND CONSTRAINT_NAME = 'fk_payments_payment_method'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND CONSTRAINT_NAME = 'fk_bookings_service'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE bookings ADD CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND CONSTRAINT_NAME = 'fk_bookings_provider'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE bookings ADD CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_details' AND CONSTRAINT_NAME = 'fk_booking_details_booking'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE booking_details ADD CONSTRAINT fk_booking_details_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'service_provider_services' AND CONSTRAINT_NAME = 'fk_sps_provider'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE service_provider_services ADD CONSTRAINT fk_sps_provider FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'service_provider_services' AND CONSTRAINT_NAME = 'fk_sps_service'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE service_provider_services ADD CONSTRAINT fk_sps_service FOREIGN KEY (service_id) REFERENCES services(id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'provider_documents' AND CONSTRAINT_NAME = 'fk_provider_documents_provider'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE provider_documents ADD CONSTRAINT fk_provider_documents_provider FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'booking_status_logs' AND CONSTRAINT_NAME = 'fk_booking_status_logs_booking'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE booking_status_logs ADD CONSTRAINT fk_booking_status_logs_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON UPDATE CASCADE ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
