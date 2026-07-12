SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_user_permissions (
    user_id INT NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    granted_by INT NOT NULL,
    PRIMARY KEY (user_id, company_id, permission_id),
    CONSTRAINT fk_company_user_permissions_user
        FOREIGN KEY (user_id)
        REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_permissions_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_permissions_permission
        FOREIGN KEY (permission_id)
        REFERENCES permissions (id)
        ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_company_user_permissions_granted_by
        FOREIGN KEY (granted_by)
        REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
