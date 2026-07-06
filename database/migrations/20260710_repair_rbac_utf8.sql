SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE roles
SET description = CASE slug
    WHEN 'admin' THEN 'Acesso administrativo.'
    WHEN 'auditor' THEN 'Consulta e auditoria.'
    WHEN 'operator' THEN 'Operação do estacionamento.'
    WHEN 'master' THEN 'Gestão máxima de identidades e acessos.'
    ELSE description
END
WHERE slug IN ('admin', 'auditor', 'operator', 'master');

UPDATE permissions
SET name = CASE slug
    WHEN 'dashboard.view' THEN 'Visualizar painel'
    WHEN 'vehicle.view' THEN 'Visualizar vagas'
    WHEN 'vehicle.checkin' THEN 'Registrar entrada'
    WHEN 'vehicle.checkout' THEN 'Finalizar ocupação'
    WHEN 'vacancy.create' THEN 'Criar vagas'
    WHEN 'report.view' THEN 'Visualizar relatórios'
    WHEN 'audit.view' THEN 'Visualizar auditoria'
    WHEN 'profile.password.update' THEN 'Alterar a própria senha'
    WHEN 'profile.photo.update' THEN 'Alterar a própria foto'
    WHEN 'identity.view' THEN 'Visualizar usuários'
    WHEN 'identity.manage' THEN 'Gerenciar acessos e permissões'
    WHEN 'financial.view' THEN 'Visualizar financeiro'
    ELSE name
END
WHERE slug IN (
    'dashboard.view',
    'vehicle.view',
    'vehicle.checkin',
    'vehicle.checkout',
    'vacancy.create',
    'report.view',
    'audit.view',
    'profile.password.update',
    'profile.photo.update',
    'identity.view',
    'identity.manage',
    'financial.view'
);
