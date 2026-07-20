SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @swiftlypark_company_id := (
    SELECT id FROM companies WHERE slug = 'swiftlypark' LIMIT 1
);

DELETE cu
FROM company_user cu
INNER JOIN (
    SELECT DISTINCT user_id
    FROM company_user
    WHERE company_id <> @swiftlypark_company_id
) scoped ON scoped.user_id = cu.user_id
WHERE cu.company_id = @swiftlypark_company_id
  AND cu.role_id IS NULL;
