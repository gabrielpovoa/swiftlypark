INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.slug = 'finance.view'
WHERE r.slug = 'admin'
  AND r.is_active = 1
  AND p.is_active = 1;
