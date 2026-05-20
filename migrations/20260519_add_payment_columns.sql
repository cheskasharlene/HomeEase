-- Migration: add payment receiver and expected fields
-- Run in MySQL: source migrations/20260519_add_payment_columns.sql

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  user_id INT NOT NULL,
  payment_method ENUM('cash','gcash','bank') NOT NULL DEFAULT 'cash',
  payment_status ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  transaction_id VARCHAR(100) NULL,
  payment_proof_path VARCHAR(512) NULL,
  notes TEXT NULL,
  receiver_provider_id INT NULL,
  expected_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_booking_id (booking_id),
  UNIQUE KEY idx_payment_reference (payment_reference(190)),
  KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS disputes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  payment_id INT NOT NULL,
  provider_id INT NOT NULL,
  reason TEXT NULL,
  matches_system TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extend booking status for online payment flow
ALTER TABLE bookings
  MODIFY COLUMN status ENUM('pending','awaiting_payment','progress','done','cancelled') NOT NULL DEFAULT 'pending';
