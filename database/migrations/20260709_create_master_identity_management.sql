SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE usuario
    ADD COLUMN deleted_at DATETIME(6) NULL AFTER senha_hash,
    ADD KEY idx_usuario_deleted_at (deleted_at);

CREATE TABLE user_permissions (
    user_id INT NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    granted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    granted_by INT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_permission FOREIGN KEY (permission_id)
        REFERENCES permissions (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_granted_by FOREIGN KEY (granted_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO roles (
    slug, name, label, icon_slug, description, display_priority
) VALUES (
    'master',
    'MASTER',
    'Master',
    'shield-check',
    'Gestão máxima de identidades e acessos.',
    1
);

INSERT INTO permissions (slug, name) VALUES
    ('identity.view', 'Visualizar usuários'),
    ('identity.manage', 'Gerenciar acessos e permissões'),
    ('financial.view', 'Visualizar financeiro');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.slug = 'master';

INSERT INTO user_roles (user_id, role_id)
SELECT MIN(u.id_usuario), r.id
FROM usuario u
CROSS JOIN roles r
WHERE r.slug = 'master'
GROUP BY r.id;

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
                'USER_PERMISSIONS_UPDATED'
            )
        );
