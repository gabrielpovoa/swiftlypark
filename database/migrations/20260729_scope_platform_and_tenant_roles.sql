-- Papéis globais são exclusivos da administração da plataforma.
-- Para usuários vinculados a empresas, company_user é a fonte dos papéis do tenant.
CREATE TEMPORARY TABLE platform_super_admin_users (
    user_id INT NOT NULL PRIMARY KEY
);

INSERT INTO platform_super_admin_users (user_id)
SELECT DISTINCT platform_role_assignment.user_id
FROM user_roles platform_role_assignment
INNER JOIN roles platform_role ON platform_role.id = platform_role_assignment.role_id
WHERE platform_role.slug = 'super-admin';

DELETE ur
FROM user_roles ur
INNER JOIN roles assigned_role ON assigned_role.id = ur.role_id
INNER JOIN company_user cu ON cu.user_id = ur.user_id
LEFT JOIN platform_super_admin_users platform_user ON platform_user.user_id = ur.user_id
WHERE assigned_role.slug <> 'super-admin'
  AND platform_user.user_id IS NULL;

DROP TEMPORARY TABLE platform_super_admin_users;
