SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @swiftlypark_company_id := (
    SELECT id FROM companies WHERE slug = 'swiftlypark' LIMIT 1
);

DELETE cu
FROM company_user cu
WHERE cu.company_id = @swiftlypark_company_id
  AND cu.role_id IS NULL
  AND EXISTS (
      SELECT 1
      FROM company_user scoped
      WHERE scoped.user_id = cu.user_id
        AND scoped.company_id <> cu.company_id
  );
