CREATE TABLE IF NOT EXISTS `n_auth_catlab` (
  `openid_url` VARCHAR(255) NOT NULL,
  `profile_id` INT NULL,
  `access_token` TEXT NULL,
  `scope` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`openid_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `n_credit_purchases` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `plid` INT NOT NULL,
  `credits` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `action_data` TEXT NOT NULL,
  `status` ENUM('pending','granted','refunded','expired','failed') NOT NULL DEFAULT 'pending',
  `credit_request_id` INT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_request_id` (`credit_request_id`),
  KEY `plid_status` (`plid`, `status`),
  KEY `status_expires_at` (`status`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
