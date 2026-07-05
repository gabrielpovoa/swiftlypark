CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    request_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    request_window_started_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    reset_token_hash CHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_email (email),
    KEY idx_password_resets_expires_at (expires_at),
    KEY idx_password_resets_reset_token_expires_at (reset_token_expires_at)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
