-- =============================================================================
-- MySQL init script — ShopPilot AI
-- Runs once on first container start (via docker-entrypoint-initdb.d)
-- =============================================================================

-- Create the test database (used by PHPUnit / CI)
CREATE DATABASE IF NOT EXISTS `shoppilot_ai_testing`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Grant the app user access to the test database
GRANT ALL PRIVILEGES ON `shoppilot_ai_testing`.* TO 'shoppilot'@'%';

-- Production database (already created by MYSQL_DATABASE env var)
-- But ensure correct charset
ALTER DATABASE `shoppilot_ai`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

FLUSH PRIVILEGES;
