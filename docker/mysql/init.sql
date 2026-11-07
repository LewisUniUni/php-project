CREATE DATABASE IF NOT EXISTS logistics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS logistics_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'logistics_app'@'%' IDENTIFIED BY 'local-app-password';
CREATE USER IF NOT EXISTS 'logistics_migrator'@'%' IDENTIFIED BY 'local-migrator-password';

GRANT SELECT, INSERT, UPDATE, DELETE ON logistics.* TO 'logistics_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON logistics_test.* TO 'logistics_app'@'%';
GRANT ALL PRIVILEGES ON logistics.* TO 'logistics_migrator'@'%';
GRANT ALL PRIVILEGES ON logistics_test.* TO 'logistics_migrator'@'%';
FLUSH PRIVILEGES;
