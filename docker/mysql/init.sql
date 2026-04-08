-- Create database with UTF-8 support
CREATE DATABASE IF NOT EXISTS rnb_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges
GRANT ALL PRIVILEGES ON rnb_db.* TO 'rnb_user'@'%';
FLUSH PRIVILEGES;
