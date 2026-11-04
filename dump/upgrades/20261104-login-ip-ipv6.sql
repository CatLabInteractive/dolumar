-- Login IPs now hold the visitor's real address (from X-Forwarded-For behind Dokku's nginx) instead of the proxy,
-- which can be IPv6: widen l_ip to 45 characters so strict mode does not reject the insert.
-- Safe to run again: MODIFY sets the full column definition.

ALTER TABLE `n_login_log`
  MODIFY COLUMN `l_ip` VARCHAR(45) NOT NULL;

ALTER TABLE `n_login_failures`
  MODIFY COLUMN `l_ip` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
