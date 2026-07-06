SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    label VARCHAR(80) NOT NULL,
    icon_slug VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    display_priority SMALLINT NOT NULL DEFAULT 100,
    is_system BOOLEAN NOT NULL DEFAULT TRUE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(120) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by INT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id)
        REFERENCES roles (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id)
        REFERENCES permissions (id) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_created_by FOREIGN KEY (created_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by INT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id)
        REFERENCES roles (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_user_roles_created_by FOREIGN KEY (created_by)
        REFERENCES usuario (id_usuario) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO roles (
    slug, name, label, icon_slug, description, display_priority
) VALUES
    ('admin', 'ADMIN', 'Administrador', 'shield-check', 'Acesso administrativo.', 10),
    ('auditor', 'AUDITOR', 'Auditor', 'search-check', 'Consulta e auditoria.', 20),
    ('operator', 'OPERATOR', 'Operador', 'clipboard-check', 'Operação do estacionamento.', 30);

INSERT INTO permissions (slug, name) VALUES
    ('dashboard.view', 'Visualizar painel'),
    ('vehicle.view', 'Visualizar vagas'),
    ('vehicle.checkin', 'Registrar entrada'),
    ('vehicle.checkout', 'Finalizar ocupação'),
    ('vacancy.create', 'Criar vagas'),
    ('report.view', 'Visualizar relatórios'),
    ('audit.view', 'Visualizar auditoria'),
    ('profile.password.update', 'Alterar a própria senha'),
    ('profile.photo.update', 'Alterar a própria foto');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.slug = 'admin'
    OR (r.slug = 'operator' AND p.slug IN (
       'dashboard.view',
       'vehicle.view',
       'vehicle.checkin',
       'vehicle.checkout',
       'profile.password.update',
       'profile.photo.update'
   ))
    OR (r.slug = 'auditor' AND p.slug IN (
       'dashboard.view',
       'report.view',
       'audit.view',
       'profile.password.update',
       'profile.photo.update'
   ));

INSERT INTO user_roles (user_id, role_id)
SELECT u.id_usuario, r.id
FROM usuario u
INNER JOIN roles r
    ON r.slug = CASE
        WHEN u.id_usuario = (SELECT MIN(id_usuario) FROM usuario) THEN 'admin'
        ELSE 'operator'
    END;
