-- Credits: refunds that could not reach Accounts are retried ('refund_pending'),
-- and every purchase remembers the Accounts profile it was started for.
-- Safe to run again: MODIFY sets the full list, the ADD COLUMN is guarded.

ALTER TABLE `n_credit_purchases`
  MODIFY COLUMN `status` ENUM('pending','granted','refunded','expired','failed','refund_pending') NOT NULL DEFAULT 'pending';

SET @dolumar_has_profile_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'n_credit_purchases' AND COLUMN_NAME = 'profile_id'
);
SET @dolumar_sql := IF(@dolumar_has_profile_id = 0,
  'ALTER TABLE `n_credit_purchases` ADD COLUMN `profile_id` INT NULL AFTER `plid`',
  'DO 0');
PREPARE dolumar_stmt FROM @dolumar_sql;
EXECUTE dolumar_stmt;
DEALLOCATE PREPARE dolumar_stmt;
