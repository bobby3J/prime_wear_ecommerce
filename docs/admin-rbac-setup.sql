-- Admin RBAC setup for prime_wear_ecommerce
-- Run against database: ecommerce

START TRANSACTION;

-- 1) Ensure role enum supports admin + superadmin (keeps customer for legacy safety)
ALTER TABLE users
  MODIFY role ENUM('admin','superadmin','customer') NOT NULL DEFAULT 'admin';

-- 2) Ensure status enum is present as expected
ALTER TABLE users
  MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active';

-- 3) Optional: create first superadmin if no admin/superadmin account exists
INSERT INTO users (name, email, password, role, status, created_at, updated_at)
SELECT
  'Super Admin',
  'superadmin@primewear.local',
  '$2y$10$piuJB99OUGcfLf9X20Dzk.KQKZ5hNckio5OnfIm8MPzkJqUmAmNBe',
  'superadmin',
  'active',
  NOW(),
  NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM users WHERE role IN ('admin', 'superadmin')
);

COMMIT;

-- Verify
SELECT id, name, email, role, status, created_at
FROM users
ORDER BY id;

-- Seed login credential (change immediately after first login):
-- email: superadmin@primewear.local
-- password: ChangeMe123!