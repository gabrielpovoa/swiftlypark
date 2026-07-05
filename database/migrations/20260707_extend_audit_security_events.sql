ALTER TABLE audit_logs
    DROP CHECK chk_audit_logs_action;

ALTER TABLE audit_logs
    MODIFY COLUMN action VARCHAR(40) NOT NULL,
    ADD CONSTRAINT chk_audit_logs_action
        CHECK (
            action IN (
                'CREATE',
                'UPDATE',
                'DELETE',
                'UNAUTHORIZED_ACCESS_ATTEMPT'
            )
        );
