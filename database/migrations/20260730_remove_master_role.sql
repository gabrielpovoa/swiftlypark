SET @admin_role_id = (SELECT id FROM roles WHERE slug = 'admin' LIMIT 1);
SET @master_role_id = (SELECT id FROM roles WHERE slug = 'master' LIMIT 1);

UPDATE company_user
SET role_id = @admin_role_id
WHERE role_id = @master_role_id
  AND @admin_role_id IS NOT NULL;

DELETE FROM user_roles
WHERE role_id = @master_role_id;

DELETE FROM role_permissions
WHERE role_id = @master_role_id;

DELETE FROM roles
WHERE id = @master_role_id;
