-- Migration: 20260915_add_policy_accepted_to_users.sql
-- Description: Add policy_accepted tracking to users table for homeowner onboarding flow

ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `policy_accepted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `policy_accepted_at` TIMESTAMP NULL DEFAULT NULL AFTER `policy_accepted`;

-- Backfill existing homeowners who already accepted the policy so they are not prompted again
UPDATE `users` 
SET `policy_accepted` = 1 
WHERE `policy_accepted_at` IS NOT NULL;
