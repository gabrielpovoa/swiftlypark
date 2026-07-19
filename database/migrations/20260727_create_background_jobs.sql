SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE background_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue_name VARCHAR(80) NOT NULL DEFAULT 'default',
    job_type VARCHAR(120) NOT NULL,
    encrypted_payload LONGTEXT NOT NULL,
    status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED') NOT NULL DEFAULT 'PENDING',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    available_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    reserved_at DATETIME(6) NULL,
    completed_at DATETIME(6) NULL,
    last_error VARCHAR(1000) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_background_jobs_reserve (queue_name, status, available_at, id),
    KEY idx_background_jobs_status_updated (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
