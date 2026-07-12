SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE audit_logs
    MODIFY COLUMN action VARCHAR(64) NOT NULL;

ALTER TABLE audit_logs
    DROP CHECK chk_audit_logs_action;

ALTER TABLE audit_logs
    ADD CONSTRAINT chk_audit_logs_action
        CHECK (
            action IN (
                'CREATE',
                'UPDATE',
                'DELETE',
                'UNAUTHORIZED_ACCESS_ATTEMPT',
                'ACCESS_REVOKED',
                'USER_PERMISSIONS_UPDATED',
                'FINANCIAL_ADJUSTMENT',
                'CROSS_TENANT_ACCESS_ATTEMPT',
                'SYSTEMATIC_TENANT_SCAN_DETECTED'
            )
        );
