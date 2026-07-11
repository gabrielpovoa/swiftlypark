SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_deleted_at_for_onboarding := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuario ADD COLUMN deleted_at DATETIME(6) NULL AFTER senha_hash',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuario'
      AND COLUMN_NAME = 'deleted_at'
);
PREPARE add_deleted_at_for_onboarding_stmt FROM @add_deleted_at_for_onboarding;
EXECUTE add_deleted_at_for_onboarding_stmt;
DEALLOCATE PREPARE add_deleted_at_for_onboarding_stmt;

SET @add_password_reset_required := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE usuario ADD COLUMN password_reset_required TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuario'
      AND COLUMN_NAME = 'password_reset_required'
);
PREPARE add_password_reset_required_stmt FROM @add_password_reset_required;
EXECUTE add_password_reset_required_stmt;
DEALLOCATE PREPARE add_password_reset_required_stmt;
